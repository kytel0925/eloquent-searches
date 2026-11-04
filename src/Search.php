<?php

namespace SlothDevGuy\Searches;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use InvalidArgumentException;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\Join\SearchJoinRelationshipBuilder;
use SlothDevGuy\Searches\Support\Fields;
use SlothDevGuy\Searches\Support\SearchGuard;
use SlothDevGuy\Searches\Where\SearchWhereBuilder;

/**
 * Default searcher: every condition key is translated into a join (when it names a
 * relationship) or into a where clause.
 *
 * Supported options: select, distinct, max, page, order, group, from_alias, qualified_fields.
 */
class Search implements Searcher
{
    use BuilderOptions;

    protected Model $from;

    protected Builder $fromBuilder;

    protected ?Builder $builder = null;

    protected ?Builder $lastBuilder = null;

    /**
     * @var array<array-key, mixed>
     */
    protected array $rawConditions;

    /**
     * @var BaseCollection<array-key, SearchBuilder>|null
     */
    protected ?BaseCollection $conditions = null;

    /**
     * @var array<int, string>|string
     */
    protected array|string $select;

    /**
     * @var array{page: int|null, pages: int|null, max: int|null, total: int|null}
     */
    protected array $pagination = [
        'page' => null,
        'pages' => null,
        'max' => null,
        'total' => null,
    ];

    /**
     * The builders tried, in order, for every condition key.
     *
     * @var array<int, class-string<SearchBuilder>>
     */
    protected array $builders = [
        SearchJoinRelationshipBuilder::class,
        SearchWhereBuilder::class,
    ];

    /**
     * @param  Model|class-string<Model>  $from
     * @param  array<array-key, mixed>|Arrayable<array-key, mixed>  $conditions
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        Model|string $from,
        array|Arrayable $conditions = [],
        ?Builder $builder = null,
        array $options = [],
    ) {
        $this->from = static::resolveModel($from);
        $this->rawConditions = $conditions instanceof Arrayable ? $conditions->toArray() : $conditions;
        $this->options = static::defaultOptions($options);
        $this->fromBuilder = $builder ?? $this->from->newQuery();

        $this->select = $this->option('select') ?? $this->getFromQualifiedField('*');

        $this->pagination['max'] = static::positiveInteger($this->option('max'));
        $this->pagination['page'] = static::positiveInteger($this->option('page'));
    }

    public function from(): Model
    {
        return $this->from;
    }

    public function select(array|string|null $select = null): array|string
    {
        if (! is_null($select)) {
            $this->select = $select;
        }

        return $this->select;
    }

    public function distinct(?bool $distinct = null): bool
    {
        if (! is_null($distinct)) {
            $this->options['distinct'] = $distinct;
        }

        return (bool) $this->option('distinct', false);
    }

    public function get(): Collection
    {
        $builder = $this->prepareBuilder();

        $this->lastBuilder = $builder->clone();

        return $this->paginateIn($builder)->get();
    }

    public function count(bool $reCount = false): int
    {
        if (is_null($this->pagination['total']) || $reCount) {
            $this->pagination['total'] = $this->countResults($this->lastBuilder ?? $this->prepareBuilder());

            if ($this->pagination['max']) {
                $this->pagination['pages'] = (int) ceil($this->pagination['total'] / $this->pagination['max']);
            }
        }

        return $this->pagination['total'];
    }

    public function pagination(): array
    {
        return $this->pagination;
    }

    public function builder(): Builder
    {
        // the conditions are applied only once: applying them twice would, for example, negate a negation
        if (is_null($this->builder)) {
            $builder = $this->fromBuilder->clone();

            foreach ($this->conditions() as $condition) {
                $builder = $condition->pushInQueryBuilder($builder);
            }

            $this->builder = $builder;
        }

        return $this->builder;
    }

    public function getLastBuilder(): Builder
    {
        return $this->lastBuilder ?? $this->prepareBuilder();
    }

    public function getFromQualifiedField(string $field): string
    {
        if (! $this->option('qualified_fields', true)) {
            return $field;
        }

        return Fields::qualify($field, $this->from()->getTable(), $this->option('from_alias'));
    }

    /**
     * Translate the raw conditions into search builders.
     *
     * @return BaseCollection<array-key, SearchBuilder>
     *
     * @throws InvalidSearchCondition
     */
    public function conditions(): BaseCollection
    {
        return $this->conditions ??= collect($this->rawConditions)
            ->map(fn (mixed $values, int|string $key) => $this->makeSearchBuilder((string) $key, $values));
    }

    /**
     * The builder with conditions, select, distinct, order and group applied (no pagination).
     */
    protected function prepareBuilder(): Builder
    {
        $builder = $this->builder()->clone();

        $this->selectIn($builder);
        $this->distinctIn($builder);
        $this->orderByIn($builder);
        $this->groupByIn($builder);

        return $builder;
    }

    protected function selectIn(Builder $builder): Builder
    {
        return $builder->select($this->select());
    }

    protected function distinctIn(Builder $builder): Builder
    {
        return $this->distinct() ? $builder->distinct() : $builder;
    }

    protected function orderByIn(Builder $builder): Builder
    {
        foreach ($this->orders() as [$column, $direction]) {
            $builder->orderBy($this->getFromQualifiedField($column), $direction);
        }

        return $builder;
    }

    protected function groupByIn(Builder $builder): Builder
    {
        $groups = array_map(function (string $column) {
            $this->guard()->assertSortable($this->from(), $column, $this->option('from_alias'));

            return $this->getFromQualifiedField($column);
        }, static::commaSeparated($this->option('group')));

        return $groups ? $builder->groupBy(...$groups) : $builder;
    }

    protected function paginateIn(Builder $builder): Builder
    {
        $max = $this->pagination['max'];

        if ($max) {
            $builder->take($max);

            if ($page = $this->pagination['page']) {
                $builder->skip(($page - 1) * $max);
            }
        }

        return $builder;
    }

    /**
     * Count the results of a builder, taking distinct and group by clauses into account.
     */
    protected function countResults(Builder $builder): int
    {
        $query = $builder->toBase()
            ->cloneWithout(['orders', 'limit', 'offset'])
            ->cloneWithoutBindings(['order']);

        if ($query->distinct || ! empty($query->groups) || ! empty($query->havings)) {
            return $query->newQuery()->fromSub($query, 'searches_count')->count();
        }

        return $query->count();
    }

    /**
     * Normalize the "order" option into [column, direction] pairs.
     *
     * Accepts "column", "column,desc", a list of those, or a [column => direction] map.
     *
     * @return array<int, array{0: string, 1: string}>
     *
     * @throws InvalidSearchCondition
     */
    protected function orders(): array
    {
        $order = $this->option('order');

        if (is_array($order) && ! array_is_list($order)) {
            $order = array_map(
                fn ($direction, $column) => "{$column},{$direction}",
                $order,
                array_keys($order),
            );
        }

        return array_map(function (string $order) {
            $parts = array_map('trim', explode(',', $order));

            if (count($parts) > 2) {
                throw InvalidSearchCondition::invalidOrder($order);
            }

            $column = $parts[0];
            $direction = strtolower($parts[1] ?? 'asc');

            if (! in_array($direction, ['asc', 'desc'], true)) {
                throw InvalidSearchCondition::invalidOrder($order);
            }

            try {
                $this->guard()->assertSortable($this->from(), $column, $this->option('from_alias'));
            } catch (InvalidSearchCondition $exception) {
                throw $exception->withKey('order');
            }

            return [$column, $direction];
        }, static::listOption($order));
    }

    /**
     * @throws InvalidSearchCondition
     */
    protected function makeSearchBuilder(string $key, mixed $values): SearchBuilder
    {
        try {
            foreach ($this->builders as $searchBuilder) {
                if ($builder = $searchBuilder::buildFromKeyAndValue($this, $key, $values)) {
                    return $builder;
                }
            }
        } catch (InvalidSearchCondition $exception) {
            throw $exception->withKey($key);
        }

        throw InvalidSearchCondition::unsupportedKey($key);
    }

    protected function guard(): SearchGuard
    {
        return app(SearchGuard::class);
    }

    /**
     * Qualify a field with a table name (or alias).
     */
    public static function getQualifiedField(string $field, string $table, ?string $alias = null): string
    {
        return Fields::qualify($field, $table, $alias);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function defaultOptions(array $options = []): array
    {
        return array_merge([
            'distinct' => false,
            'max' => null,
            'page' => null,
            'order' => null,
            'group' => null,
            'from_alias' => null,
            'qualified_fields' => true,
            'select' => null,
        ], $options);
    }

    /**
     * @param  Model|class-string<Model>  $from
     */
    protected static function resolveModel(Model|string $from): Model
    {
        if ($from instanceof Model) {
            return $from;
        }

        if (! is_subclass_of($from, Model::class)) {
            throw new InvalidArgumentException("[{$from}] is not an Eloquent model.");
        }

        return new $from;
    }

    /**
     * Normalize a "comma separated string or list" option into a list of strings.
     *
     * @return array<int, string>
     */
    protected static function listOption(mixed $value): array
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(fn ($item) => trim((string) $item), $values),
            fn (string $item) => $item !== '',
        ));
    }

    /**
     * Like listOption(), but every item can also hold comma separated values.
     *
     * @return array<int, string>
     */
    protected static function commaSeparated(mixed $value): array
    {
        return static::listOption(array_merge(...array_map(
            fn (string $item) => explode(',', $item),
            static::listOption($value),
        ) ?: [[]]));
    }

    protected static function positiveInteger(mixed $value): ?int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $value === false ? null : $value;
    }
}
