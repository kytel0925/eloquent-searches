<?php

namespace SlothDevGuy\Searches\Join;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Joins a belongs-to relationship.
 */
class JoinBelongsTo extends JoinRelationship
{
    public function __construct(
        protected Model $from,
        protected BelongsTo $relationship,
        array $options = [],
    ) {
        $this->options = static::defaultOptions($options);
    }

    public function on(): array
    {
        $relationship = $this->relationship;

        $first = $this->getToTableQualifiedField($relationship->getOwnerKeyName());
        $operator = $this->joinOperator();
        $second = $this->getFromTableQualifiedField($relationship->getForeignKeyName());

        return compact('first', 'operator', 'second');
    }

    public static function instanceOf(Relation $relationship): bool
    {
        return $relationship instanceof BelongsTo;
    }
}
