<?php

namespace SlothDevGuy\Searches\Join;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Joins a morph-one or morph-many relationship.
 */
class JoinMorphOneOrMany extends JoinRelationship
{
    public function __construct(
        protected Model $from,
        protected MorphOneOrMany $relationship,
        array $options = [],
    ) {
        $this->options = static::defaultOptions($options);
    }

    /**
     * @return array[]
     */
    public function joins(): array
    {
        $baseJoin = [
            'table' => $this->getJoinContext(),
            'arguments' => [$this->joinClosure()],
        ];

        return [$baseJoin];
    }

    public function on(): array
    {
        $relationship = $this->relationship;

        $first = $this->getFromTableQualifiedField($relationship->getLocalKeyName());
        $operator = $this->joinOperator();
        $second = $this->getToTableQualifiedField($relationship->getForeignKeyName());

        return compact('first', 'operator', 'second');
    }

    public function wheres(): array
    {
        $relationship = $this->relationship;

        $first = $this->getToTableQualifiedField($relationship->getMorphType());
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

    public static function instanceOf(Relation $relationship): bool
    {
        return $relationship instanceof MorphOneOrMany;
    }
}
