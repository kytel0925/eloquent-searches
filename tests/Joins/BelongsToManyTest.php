<?php

namespace Tests\Joins;

use SlothDevGuy\Searches\Join\JoinBelongsToMany;
use SlothDevGuy\Searches\Join\SearchJoinRelationshipBuilder;
use Tests\Fixtures\Models\Person;
use Tests\Fixtures\Models\Task;
use Tests\TestCase;

class BelongsToManyTest extends TestCase
{
    use JoinBuilderAssertions, MockSearcher;

    public function test_joins()
    {

        $joinData = [
            'values' => [],
            'model' => Task::class,
            'table' => 'tasks',
            'pivot_table' => 'task_follower',
            'relation' => 'tasksFollowed',
            'foreign_key' => 'person_id',
            'related_foreign_key' => 'task_id',
            'aliases' => [null, 'foo', 'bar'],
        ];

        $joinTypes = [null, 'join', 'left-join'];

        $from = [
            'model' => $model = new Person,
            'table' => $model->getTable(),
        ];

        foreach ($joinTypes as $index => $joinType) {
            $alias = data_get($joinData, "aliases.{$index}");

            $join = collect([
                $joinData['relation'].($alias ? "@$alias" : ''),
                $joinType,
            ])->filter()->implode('|');

            /** @var SearchJoinRelationshipBuilder $joinBuilder */
            $joinBuilder = SearchJoinRelationshipBuilder::buildFromKeyAndValue($this->mockSearcher($from), $join, $joinData['values']);

            $this->assertNotNull($joinBuilder);

            $pivotJoinContext = $alias ? "{$joinData['pivot_table']}_{$alias}" : $joinData['pivot_table'];
            $pivotJoin = $alias ? "{$joinData['pivot_table']} as {$pivotJoinContext}" : $joinData['pivot_table'];

            $join = $alias ? "{$joinData['table']} as {$alias}" : $joinData['table'];
            $joinContext = $alias ?: $joinData['table'];

            $this->assertJoinBuilder($joinBuilder, [
                'join_instance_of' => JoinBelongsToMany::class,
                'related_instance_of' => $joinData['model'],
                'method' => $joinType ? data_get(static::$supportedJoins, $joinType, 'join') : 'join',
                'arguments' => [
                    [
                        'table' => $pivotJoin,
                        'arguments' => [
                            "{$from['table']}.id",
                            '=',
                            "{$pivotJoinContext}.{$joinData['foreign_key']}",
                        ],
                    ],
                    [
                        'table' => $join,
                        'arguments' => [
                            "{$joinContext}.id",
                            '=',
                            "{$pivotJoinContext}.{$joinData['related_foreign_key']}",
                        ],
                    ],
                ],
            ]);
        }
    }
}
