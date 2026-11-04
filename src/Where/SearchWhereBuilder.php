<?php

namespace SlothDevGuy\Searches\Where;

use Illuminate\Database\Eloquent\Builder;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\SearchBuilder;
use SlothDevGuy\Searches\Searcher;
use SlothDevGuy\Searches\Support\SearchGuard;
use SlothDevGuy\Searches\WhereBuilder;

/**
 * Translates a "field|arguments" key into a where clause.
 *
 * @phpstan-consistent-constructor
 */
class SearchWhereBuilder implements SearchBuilder
{
    /**
     * Used when the "searches.wheres" configuration is not available.
     *
     * @var array<int, class-string<WhereBuilder>>
     */
    protected static array $supportedWheres = [
        Where::class,
        WhereNull::class,
        WhereColumn::class,
        WhereIn::class,
        WhereBetween::class,
        WhereFullText::class,
    ];

    protected WhereArguments $arguments;

    protected ?WhereBuilder $where = null;

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidSearchCondition
     */
    protected function __construct(
        protected Searcher $searcher,
        protected string $fieldWithArguments,
        mixed $values,
        array $options = [],
    ) {
        $this->arguments = new WhereArguments($this->fieldWithArguments, $values, $options);

        $this->pickWhereBuilder();

        if ($this->where) {
            $this->guardFields();
            $this->setValuesInWhere($this->castValuesIfFieldRequires($values));
        }
    }

    public function searcher(): Searcher
    {
        return $this->searcher;
    }

    public function arguments(): WhereArguments
    {
        return $this->arguments;
    }

    public function where(): ?WhereBuilder
    {
        return $this->where;
    }

    public function pushInQueryBuilder(Builder $builder): Builder
    {
        return $this->where ? $this->where->pushInBuilder($builder) : $builder;
    }

    /**
     * Cast the values with the model casts when the "cast" argument is present.
     *
     * Returns null when no cast is required.
     */
    public function castValuesIfFieldRequires(mixed $values): mixed
    {
        if (! $this->where) {
            return null;
        }

        $arguments = $this->where->arguments();
        $cast = $arguments->option('cast');

        if (! $cast) {
            return null;
        }

        $field = $cast === true ? $arguments->field() : data_get($cast, 0);

        if (! is_string($field)) {
            throw InvalidSearchCondition::invalidValue('the cast argument requires a single field');
        }

        $this->guard()->assertSearchable($this->searcher->from(), $field, $this->searcher->option('from_alias'));

        return $this->castValue($field, $values);
    }

    protected function castValue(string $field, mixed $values): mixed
    {
        if (is_array($values)) {
            return array_map(fn ($value) => $this->castValue($field, $value), $values);
        }

        if (! is_string($values)) {
            return $values;
        }

        $model = $this->searcher->from()->newInstance();
        $model->setAttribute($field, $values);
        $castValue = $model->getAttributeValue($field);

        if (is_object($castValue) && method_exists($castValue, 'cast')) {
            $castValue = $castValue->cast();
        }

        return $castValue;
    }

    protected function pickWhereBuilder(): static
    {
        $method = $this->arguments->method();

        $where = collect(static::supportedWheres())
            ->first(fn (string $whereBuilder) => $whereBuilder::supportsMethod($method));

        if ($where) {
            // the selected where can redirect to a better suited one (for example where -> whereIn)
            $this->where = (new $where($this))->redirect();
        }

        return $this;
    }

    /**
     * Make sure every column referenced by the where can be searched.
     *
     * @throws InvalidSearchCondition
     */
    protected function guardFields(): void
    {
        $from = $this->searcher->from();
        $alias = $this->searcher->option('from_alias');

        foreach ($this->where->columns() as $column) {
            $this->guard()->assertSearchable($from, $column, $alias);
        }
    }

    protected function setValuesInWhere(mixed $values): void
    {
        if (! is_null($values) && $this->where) {
            $this->where->arguments()->value($values);
        }
    }

    protected function guard(): SearchGuard
    {
        return app(SearchGuard::class);
    }

    /**
     * @return array<int, class-string<WhereBuilder>>
     */
    public static function supportedWheres(): array
    {
        return config('searches.wheres') ?: static::$supportedWheres;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidSearchCondition
     */
    public static function buildFromKeyAndValue(Searcher $search, string $key, mixed $value, array $options = []): ?SearchBuilder
    {
        $builder = new static($search, $key, $value, $options);

        return $builder->where() ? $builder : null;
    }
}
