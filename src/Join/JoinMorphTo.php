<?php

namespace SlothDevGuy\Searches\Join;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\Support\SearchGuard;

/**
 * Joins a morph-to relationship; the related type is given as "relation:type".
 */
class JoinMorphTo extends JoinRelationship
{
    public function __construct(
        protected Model $from,
        protected MorphTo $relationship,
        array $options = []
    ) {
        $this->options = static::defaultOptions($options);
    }

    public function to(): Model
    {
        return $this->option('to') ?: parent::to();
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

        $first = $this->getToTableQualifiedField($relationship->getOwnerKeyName() ?: $this->to()->getKeyName());
        $operator = $this->joinOperator();
        $second = $this->getFromTableQualifiedField($relationship->getForeignKeyName());

        return compact('first', 'operator', 'second');
    }

    public function wheres(): array
    {
        $relationship = $this->relationship;

        $first = $this->getFromTableQualifiedField($relationship->getMorphType());
        $operator = $this->joinOperator();
        $second = $this->getModelClass();

        return [
            compact('first', 'operator', 'second'),
        ];
    }

    public function getModelClass(): string
    {
        return morph_model_alias($this->to()) ?: get_class($this->to());
    }

    public static function instanceOf(Relation $relationship): bool
    {
        return $relationship instanceof MorphTo;
    }

    protected static function defaultOptions(array $options = []): array
    {
        $options = parent::defaultOptions($options);

        // the related model of a morph-to can not be guessed: it is given as "relation:Type",
        // where Type is a morph map alias or a model class
        if ($to = collect(data_get($options, 'relation_arguments'))->first()) {
            $options['to'] = app(SearchGuard::class)->morphModel($to);
        }

        if (empty($options['to'])) {
            throw InvalidSearchCondition::invalidValue('morph-to relationships need the related type, as "relation:type"');
        }

        return $options;
    }
}
