<?php

namespace SlothDevGuy\Searches\Join;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Joins a has-one or has-many relationship.
 */
class JoinHasOneOrMany extends JoinRelationship
{
    public function __construct(
        protected Model $from,
        protected HasOneOrMany $relationship,
        array $options = [],
    ) {
        $this->options = static::defaultOptions($options);
    }

    /**
     * @return array[]
     */
    public function on(): array
    {
        $relationship = $this->relationship;

        $first = $this->getFromTableQualifiedField($relationship->getLocalKeyName());
        $operator = $this->joinOperator();
        $second = $this->getToTableQualifiedField($relationship->getForeignKeyName());

        return [$first, $operator, $second];
    }

    public static function instanceOf(Relation $relationship): bool
    {
        return $relationship instanceof HasOneOrMany;
    }
}
