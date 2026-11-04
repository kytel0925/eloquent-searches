<?php

namespace SlothDevGuy\Searches\Join;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use SlothDevGuy\Searches\BuilderOptions;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\JoinRelationshipBuilder;
use SlothDevGuy\Searches\SearchBuilder;
use SlothDevGuy\Searches\Searcher;
use SlothDevGuy\Searches\Support\SearchGuard;

/**
 * Translates a "relationship@alias:arguments|join-type" key into a join; its value holds
 * the conditions applied to the related model.
 *
 * @phpstan-consistent-constructor
 */
class SearchJoinRelationshipBuilder implements SearchBuilder
{
    use BuilderOptions;

    /**
     * Used when the "searches.join_types" configuration is not available.
     *
     * @var array<string, string>
     */
    protected static array $supportedJoins = [
        'join' => 'join',
        'inner-join' => 'join',
        'left-join' => 'leftJoin',
        'right-join' => 'rightJoin',
        'cross-join' => 'crossJoin',
    ];

    /**
     * Used when the "searches.relationships" configuration is not available.
     *
     * @var array<string, class-string<JoinRelationshipBuilder>>
     */
    protected static array $supportedRelationships = [
        'belongs-to' => JoinBelongsTo::class,
        'belongs-to-many' => JoinBelongsToMany::class,
        'has-one' => JoinHasOneOrMany::class,
        'has-many' => JoinHasOneOrMany::class,
        'has-one-or-many' => JoinHasOneOrMany::class,
        'morph-to' => JoinMorphTo::class,
        'morph-one-or-many' => JoinMorphOneOrMany::class,
        'morph-one' => JoinMorphOneOrMany::class,
        'morph-many' => JoinMorphOneOrMany::class,
        'morph-to-many' => JoinMorphToMany::class,
    ];

    /**
     * @var array<array-key, mixed>
     */
    protected array $nestedConditions;

    protected ?JoinRelationshipBuilder $join = null;

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidSearchCondition
     */
    protected function __construct(
        protected Searcher $search,
        protected string $relationshipWithAggregations,
        mixed $nestedConditions,
        array $options = [],
    ) {
        $this->options = static::defaultOptions(array_merge([
            'from_alias' => $this->search->option('from_alias'),
        ], $options));

        $this->buildJoinArguments($nestedConditions);
    }

    public function search(): Searcher
    {
        return $this->search;
    }

    public function join(): ?JoinRelationshipBuilder
    {
        return $this->join;
    }

    public function pushInQueryBuilder(Builder $builder): Builder
    {
        if (! $this->join) {
            return $builder;
        }

        $builder = $this->join->pushInBuilder($builder);

        // the nested conditions are handled by another searcher over the related model
        $search = $this->search;

        $nested = new $search($this->join->to(), $this->nestedConditions, $builder, [
            'from_alias' => $this->join->option('to_table_alias'),
        ]);

        return $nested->builder();
    }

    /**
     * @throws InvalidSearchCondition
     */
    protected function buildJoinArguments(mixed $nestedConditions): void
    {
        $arguments = collect(explode('|', $this->relationshipWithAggregations))
            ->map(fn (string $argument) => trim($argument));

        // the relationship is the argument between curly brackets, or the first one
        $index = $arguments->search(fn (string $argument) => (bool) preg_match('/^\{(.*)}$/', $argument));

        if ($index !== false) {
            $relation = substr($arguments->pull($index), 1, -1);
        } else {
            $relation = (string) $arguments->shift();
        }

        [$relation, $relationArguments] = array_pad(explode(':', $relation, 2), 2, null);
        [$relation, $alias] = array_pad(explode('@', $relation, 2), 2, null);

        $from = $this->search->from();
        $name = method_exists($from, Str::camel($relation)) ? Str::camel($relation) : $relation;

        $relationship = $this->guard()->relation($from, $name);

        // not a (joinable) relationship: the key is handled by another search builder
        if (! $relationship) {
            return;
        }

        $this->options['relation'] = $name;
        $this->options['alias'] = $alias;

        $joinTypes = static::supportedJoins();

        foreach ($arguments as $argument) {
            if (in_array($argument, $joinTypes, true)) {
                $this->options['method'] = $argument;
            } elseif (isset($joinTypes[$argument])) {
                $this->options['method'] = $joinTypes[$argument];
            } else {
                throw InvalidSearchCondition::unknownArgument($argument);
            }
        }

        if ($alias !== null) {
            $this->guard()->assertAlias($alias);
        }

        $this->nestedConditions = $this->normalizeNestedConditions($nestedConditions);

        $relationshipKey = Str::kebab(class_basename($relationship));
        $this->options['relationship'] = $relationshipKey;

        $joinAdapter = static::supportedRelationships()[$relationshipKey] ?? null;

        if (! $joinAdapter || ! $joinAdapter::instanceOf($relationship)) {
            throw InvalidSearchCondition::unsupportedRelation($name, class_basename($relationship));
        }

        $this->join = new $joinAdapter($from, $relationship, [
            'method' => $this->options['method'],
            'from_table_alias' => $this->search->option('from_alias'),
            'to_table_alias' => $alias ?: null,
            'relation_arguments' => is_null($relationArguments) ? [] : array_values(array_filter(
                array_map('trim', explode(',', $relationArguments)),
                fn (string $argument) => $argument !== '',
            )),
        ]);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws InvalidSearchCondition
     */
    protected function normalizeNestedConditions(mixed $conditions): array
    {
        if (is_null($conditions) || $conditions === '') {
            return [];
        }

        if (! is_array($conditions)) {
            throw InvalidSearchCondition::invalidValue('relationship conditions must be an array');
        }

        return $conditions;
    }

    protected function guard(): SearchGuard
    {
        return app(SearchGuard::class);
    }

    /**
     * @return array<string, string>
     */
    public static function supportedJoins(): array
    {
        return config('searches.join_types') ?: static::$supportedJoins;
    }

    /**
     * @return array<string, class-string<JoinRelationshipBuilder>>
     */
    public static function supportedRelationships(): array
    {
        return config('searches.relationships') ?: static::$supportedRelationships;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidSearchCondition
     */
    public static function buildFromKeyAndValue(Searcher $search, string $key, mixed $value, array $options = []): ?SearchBuilder
    {
        $builder = new static($search, $key, $value, $options);

        return $builder->join() ? $builder : null;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function defaultOptions(array $options = []): array
    {
        return array_merge([
            'relation' => null,
            'method' => 'join',
        ], $options);
    }
}
