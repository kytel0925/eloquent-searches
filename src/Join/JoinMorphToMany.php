<?php

namespace SlothDevGuy\Searches\Join;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;

/**
 * Joins a morph-to-many (or morphed-by-many) relationship through its pivot table.
 */
class JoinMorphToMany extends JoinRelationship
{
    public function __construct(
        protected Model $from,
        protected MorphToMany $relationship,
        array $options = [],
    ) {
        $options['pivot_table'] = $this->relationship->getTable();

        $this->options = static::defaultOptions($options);
    }

    /**
     * @return array[]
     */
    public function joins(): array
    {
        return [
            $this->pivotJoin(),
            $this->baseJoin(),
        ];
    }

    public function pivotJoin(): array
    {
        $pivotAlias = $this->option('pivot_table_alias');
        $pivotJoinContext = $pivotAlias ?
            "{$this->relationship->getTable()} as {$pivotAlias}" :
            $this->relationship->getTable();

        return [
            'table' => $pivotJoinContext,
            'arguments' => [
                $this->pivotJoinClosure(),
            ],
        ];
    }

    /**
     * @return Closure
     */
    public function pivotJoinClosure()
    {
        return function (JoinClause $join) {
            $on = $this->onPivot();
            $join->on(...array_values($on));

            foreach ($this->wherePivot() as $where) {
                $join->where(...array_values($where));
            }
        };
    }

    public function on(): array
    {
        $relationship = $this->relationship;

        $first = $this->getToTableQualifiedField($relationship->getParentKeyName());
        $operator = $this->joinOperator();
        $second = $this->getPivotTableQualifiedField($relationship->getRelatedPivotKeyName());

        return [$first, $operator, $second];
    }

    public function onPivot(): array
    {
        $relationship = $this->relationship;

        $first = $this->getFromTableQualifiedField($relationship->getParentKeyName());
        $operator = $this->joinOperator();
        $second = $this->getPivotTableQualifiedField($relationship->getForeignPivotKeyName());

        return compact('first', 'operator', 'second');
    }

    public function wherePivot(): array
    {
        $relationship = $this->relationship;

        $first = $this->getPivotTableQualifiedField($relationship->getMorphType());
        $operator = $this->joinOperator();
        $second = $this->getModelClass();

        return [
            compact('first', 'operator', 'second'),
        ];
    }

    public function getModelClass(): string
    {
        return morph_model_alias($this->from()) ?: get_class($this->from());
    }

    public function getPivotTableQualifiedField(string $field): string
    {
        return static::getQualifiedField($field, $this->relationship->getTable(), $this->option('pivot_table_alias'));
    }

    public static function instanceOf(Relation $relationship): bool
    {
        return $relationship instanceof MorphToMany;
    }

    public static function defaultOptions(array $options = []): array
    {
        $options = parent::defaultOptions($options);

        $alias = data_get($options, 'to_table_alias');

        if ($alias) {
            $table = data_get($options, 'pivot_table');

            $options['pivot_table_alias'] = "{$table}_{$alias}";
        }

        return $options;
    }
}
