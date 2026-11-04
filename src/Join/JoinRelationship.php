<?php

namespace SlothDevGuy\Searches\Join;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use SlothDevGuy\Searches\JoinRelationshipBuilder;
use SlothDevGuy\Searches\Support\Fields;

/**
 * Base class for the relationship join adapters.
 *
 * @property-read Relation $relationship
 */
abstract class JoinRelationship implements JoinRelationshipBuilder
{
    protected array $options = [];

    protected Model $from;

    protected static function defaultOptions(array $options = []): array
    {
        return array_merge([
            'method' => 'join',
            'join_operator' => '=',
            'from_table_alias' => null,
            'to_table_alias' => null,
            'relation_arguments' => [],
        ], $options);
    }

    public function from(): Model
    {
        return $this->from;
    }

    public function getFromTableQualifiedField(string $field): string
    {
        return static::getQualifiedField($field, $this->from()->getTable(), $this->options['from_table_alias']);
    }

    public function to(): Model
    {
        return $this->relationship->getModel();
    }

    /**
     * @return array[]
     */
    public function joins(): array
    {
        return [
            $this->baseJoin(),
        ];
    }

    /**
     * @return array|Closure[]
     */
    protected function baseJoin()
    {
        $join = [
            'table' => $this->getJoinContext(),
            'arguments' => array_values($this->on()),
        ];

        if (! empty($this->wheres())) {
            $join['arguments'] = $this->joinClosure();
        }

        return $join;
    }

    /**
     * @return Closure
     */
    protected function joinClosure()
    {
        return function (JoinClause $join) {
            $on = $this->on();
            $join->on(...array_values($on));

            foreach ($this->wheres() as $where) {
                $join->where(...array_values($where));
            }
        };
    }

    public function wheres(): array
    {
        return [];
    }

    public function getToTableQualifiedField(string $field): string
    {
        return static::getQualifiedField($field, $this->to()->getTable(), $this->options['to_table_alias']);
    }

    public function joinOperator(): string
    {
        return data_get($this->options, 'join_operator', '=');
    }

    public function pushInBuilder(Builder $builder): Builder
    {
        $joins = $this->joins();

        foreach ($joins as $join) {
            $table = $join['table'];
            $arguments = array_values($join['arguments']);
            $method = $this->options['method'];

            $builder->{$method}($table, ...$arguments);
        }

        return $builder;
    }

    public function isValid(): bool
    {
        return ! empty($this->joins());
    }

    /**
     * @param  mixed  $default
     * @return array|mixed
     */
    public function option(string $key, $default = null)
    {
        return data_get($this->options, $key, $default);
    }

    protected function getJoinContext(): string
    {
        $alias = $this->options['to_table_alias'];

        return $alias ?
            "{$this->to()->getTable()} as {$alias}" :
            $this->to()->getTable();
    }

    public static function getQualifiedField(string $field, string $table, ?string $alias = null): string
    {
        return Fields::qualify($field, $table, $alias);
    }
}
