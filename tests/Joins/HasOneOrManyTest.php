<?php

namespace Tests\Joins;

use SlothDevGuy\Searches\Join\JoinHasOneOrMany;
use SlothDevGuy\Searches\Join\SearchJoinRelationshipBuilder;
use Tests\Fixtures\Models\Address;
use Tests\Fixtures\Models\Person;
use Tests\Fixtures\Models\Task;
use Tests\TestCase;

class HasOneOrManyTest extends TestCase
{
    use JoinBuilderAssertions, MockSearcher;

    public function test_joins()
    {

        $joinData = [
            'values' => [],
            'model' => Address::class,
            'table' => 'addresses',
            'relation' => 'addresses',
            'foreign_key' => 'person_id',
            'aliases' => [null, 'foo', 'bar'],
        ];

        $joinTypes = [null, 'join', 'left-join'];

        $from = [
            'model' => $model = new Person,
            'table' => $model->getTable(),
        ];

        foreach ($joinTypes as $index => $joinType) {
            $alias = data_get($joinData, "aliases.$index");

            $join = collect([
                $joinData['relation'].($alias ? "@$alias" : ''),
                $joinType,
            ])->filter()->implode('|');

            /** @var SearchJoinRelationshipBuilder $joinBuilder */
            $joinBuilder = SearchJoinRelationshipBuilder::buildFromKeyAndValue($this->mockSearcher($from), $join, $joinData['values']);

            $this->assertNotNull($joinBuilder);

            $join = $alias ? "{$joinData['table']} as {$alias}" : $joinData['table'];
            $joinContext = $alias ?: $joinData['table'];

            $this->assertJoinBuilder($joinBuilder, [
                'join_instance_of' => JoinHasOneOrMany::class,
                'related_instance_of' => $joinData['model'],
                'method' => $joinType ? data_get(static::$supportedJoins, $joinType, 'join') : 'join',
                'arguments' => [
                    [
                        'table' => $join,
                        'arguments' => [
                            "{$from['table']}.id",
                            '=',
                            "{$joinContext}.{$joinData['foreign_key']}",
                        ],
                    ],
                ],
            ]);
        }
    }

    public function test_join_with_custom_keys()
    {

        $joinData = [
            'values' => [],
            'model' => Task::class,
            'table' => 'tasks',
            'relation' => 'tasks',
            'foreign_key' => 'owner_id',
        ];

        $from = [
            'model' => $model = new Person,
            'table' => $model->getTable(),
        ];

        $join = $joinData['relation'];

        /** @var SearchJoinRelationshipBuilder $joinBuilder */
        $joinBuilder = SearchJoinRelationshipBuilder::buildFromKeyAndValue($this->mockSearcher($from), $join, $joinData['values']);

        $this->assertNotNull($joinBuilder);

        $joinContext = $joinData['table'];

        $this->assertJoinBuilder($joinBuilder, [
            'join_instance_of' => JoinHasOneOrMany::class,
            'related_instance_of' => $joinData['model'],
            'method' => 'join',
            'arguments' => [
                [
                    'table' => $join,
                    'arguments' => [
                        "{$from['table']}.id",
                        '=',
                        "{$joinContext}.{$joinData['foreign_key']}",
                    ],
                ],
            ],
        ]);
    }
}
