<?php

namespace SlothDevGuy\Searches\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;

/**
 * Decides which fields, relationships and morph types a search is allowed to touch.
 *
 * Search conditions usually come from untrusted input (a query string), so every
 * column and relationship name is checked here before it reaches the query builder.
 *
 * A model can declare explicit allow lists through these optional methods (the
 * EloquentSearch trait implements them from the $searchable, $searchableRelations
 * and $sortable properties):
 *
 *  - searchableFields(): ?array     columns usable in conditions
 *  - sortableFields(): ?array       columns usable in order/group
 *  - searchableRelations(): ?array  relationship methods that can be joined
 */
class SearchGuard
{
    /**
     * A column name, optionally prefixed with a table (or alias) and followed by a JSON path.
     */
    protected const FIELD_PATTERN = '/^(?:(?<prefix>[A-Za-z_][A-Za-z0-9_]*)\.)?(?<column>[A-Za-z_][A-Za-z0-9_]*)(?<path>(?:->[A-Za-z0-9_\-]+)*)$/';

    protected const IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /**
     * @param  bool  $strict  when true, models without an allow list can not be searched at all
     */
    public function __construct(
        protected bool $strict = false,
    ) {}

    public function strict(): bool
    {
        return $this->strict;
    }

    /**
     * Assert that a field can be used in a search condition.
     *
     * @throws InvalidSearchCondition
     */
    public function assertSearchable(Model $model, string $field, ?string $alias = null): void
    {
        $this->assertAllowed($model, $field, $alias, $this->allowList($model, 'searchableFields'));
    }

    /**
     * Assert that a field can be used to sort or group the results.
     *
     * @throws InvalidSearchCondition
     */
    public function assertSortable(Model $model, string $field, ?string $alias = null): void
    {
        $allowed = $this->allowList($model, 'sortableFields') ?? $this->allowList($model, 'searchableFields');

        $this->assertAllowed($model, $field, $alias, $allowed);
    }

    /**
     * Assert that a string is a valid table alias.
     *
     * @throws InvalidSearchCondition
     */
    public function assertAlias(string $alias): void
    {
        if (! preg_match(self::IDENTIFIER_PATTERN, $alias)) {
            throw InvalidSearchCondition::invalidAlias($alias);
        }
    }

    /**
     * Resolve a relationship by name, without ever calling a method that is not a relationship.
     *
     * A method is treated as a relationship when it is listed in searchableRelations(),
     * or (when the model has no such list) when it declares a Relation return type.
     */
    public function relation(Model $model, string $name): ?Relation
    {
        if (! preg_match(self::IDENTIFIER_PATTERN, $name) || ! method_exists($model, $name)) {
            return null;
        }

        $method = new ReflectionMethod($model, $name);

        if (! $method->isPublic()
            || $method->isStatic()
            || $method->getNumberOfRequiredParameters() > 0
            || $method->getDeclaringClass()->getName() === Model::class) {
            return null;
        }

        $allowed = $this->allowList($model, 'searchableRelations');

        if ($allowed !== null) {
            if (! in_array($name, $allowed, true)) {
                return null;
            }
        } elseif ($this->strict || ! $this->declaresRelation($method)) {
            return null;
        }

        $relation = $model->{$name}();

        return $relation instanceof Relation ? $relation : null;
    }

    /**
     * Resolve a morph type (a morph map alias or a model class) into a model instance.
     *
     * @throws InvalidSearchCondition
     */
    public function morphModel(string $type): Model
    {
        $class = Relation::getMorphedModel($type) ?? $type;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            throw InvalidSearchCondition::invalidMorphType($type);
        }

        return new $class;
    }

    /**
     * @param  array<int, string>|null  $allowed
     *
     * @throws InvalidSearchCondition
     */
    protected function assertAllowed(Model $model, string $field, ?string $alias, ?array $allowed): void
    {
        if (! preg_match(self::FIELD_PATTERN, $field, $matches)) {
            throw InvalidSearchCondition::invalidField($field);
        }

        $prefix = $matches['prefix'];
        $column = $matches['column'];

        if ($prefix !== '' && ! in_array($prefix, array_filter([(string) $model->getTable(), (string) $alias]), true)) {
            throw InvalidSearchCondition::forbiddenField($field);
        }

        if ($allowed !== null) {
            if (! in_array($column, $allowed, true)) {
                throw InvalidSearchCondition::forbiddenField($field);
            }

            return;
        }

        if ($this->strict || in_array($column, (array) $model->getHidden(), true)) {
            throw InvalidSearchCondition::forbiddenField($field);
        }
    }

    /**
     * @return array<int, string>|null
     */
    protected function allowList(Model $model, string $method): ?array
    {
        if (! method_exists($model, $method)) {
            return null;
        }

        $allowed = $model->{$method}();

        return is_array($allowed) ? array_values($allowed) : null;
    }

    protected function declaresRelation(ReflectionMethod $method): bool
    {
        $type = $method->getReturnType();

        $types = match (true) {
            $type instanceof ReflectionNamedType => [$type],
            $type instanceof ReflectionUnionType => $type->getTypes(),
            default => [],
        };

        if ($types === []) {
            return false;
        }

        foreach ($types as $named) {
            if (! $named instanceof ReflectionNamedType
                || $named->isBuiltin()
                || ! is_a($named->getName(), Relation::class, true)) {
                return false;
            }
        }

        return true;
    }
}
