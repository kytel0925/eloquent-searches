<?php

namespace Tests\Joins;

use SlothDevGuy\Searches\Join\JoinMorphOneOrMany;
use SlothDevGuy\Searches\Join\JoinMorphTo;
use SlothDevGuy\Searches\Join\SearchJoinRelationshipBuilder;
use Tests\Fixtures\Models\Attachment;
use Tests\Fixtures\Models\Comment;
use Tests\TestCase;

class MorphOneOrManyTest extends TestCase
{
    use JoinBuilderAssertions, MockSearcher;

    public function test_joins()
    {

        $joinData = [
            'values' => [],
            'model' => Attachment::class,
            'table' => 'attachments',
            'relation' => 'attachments',
            'foreign_type' => 'owner_type',
            'foreign_key' => 'owner_id',
            'aliases' => [null, 'foo', 'bar'],
        ];

        $joinTypes = [null, 'join', 'left-join'];

        $from = [
            'model' => $model = new Comment,
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

            $join = $alias ? "{$joinData['table']} as {$alias}" : $joinData['table'];
            $joinContext = $alias ?: $joinData['table'];

            $this->assertJoinBuilder($joinBuilder, [
                'join_instance_of' => JoinMorphOneOrMany::class,
                'related_instance_of' => $joinData['model'],
                'method' => $joinType ? data_get(static::$supportedJoins, $joinType, 'join') : 'join',
                'arguments' => [
                    [
                        'table' => $join,
                        'arguments' => [
                            function () {},
                        ],
                    ],
                ],
            ]);

            /** @var JoinMorphOneOrMany $join */
            $join = $joinBuilder->join();

            $first = "{$from['table']}.id";
            $operator = '=';
            $second = "{$joinContext}.{$joinData['foreign_key']}";

            $on = compact('first', 'operator', 'second');
            $this->assertEquals($on, $join->on());

            $first = "{$joinContext}.{$joinData['foreign_type']}";
            $second = get_class($from['model']);

            $where = compact('first', 'operator', 'second');
            $this->assertEquals($where, collect($join->wheres())->first());
        }
    }

    public function test_inverse_join()
    {

        $joinData = [
            'values' => [],
            'model' => Comment::class,
            'table' => 'comments',
            'relation' => 'owner',
            'foreign_type' => 'owner_type',
            'foreign_key' => 'owner_id',
            'aliases' => [null, 'foo', 'bar'],
        ];

        $joinTypes = [null, 'join', 'left-join'];

        $from = [
            'model' => $model = new Attachment,
            'table' => $model->getTable(),
        ];

        foreach ($joinTypes as $index => $joinType) {
            $alias = data_get($joinData, "aliases.{$index}");

            $join = collect([
                $joinData['relation'].($alias ? "@$alias" : '').':'.Comment::class,
                $joinType,
            ])->filter()->implode('|');

            /** @var SearchJoinRelationshipBuilder $joinBuilder */
            $joinBuilder = SearchJoinRelationshipBuilder::buildFromKeyAndValue($this->mockSearcher($from), $join, $joinData['values']);

            $this->assertNotNull($joinBuilder);

            $join = $alias ? "{$joinData['table']} as {$alias}" : $joinData['table'];
            $joinContext = $alias ?: $joinData['table'];

            $this->assertJoinBuilder($joinBuilder, [
                'join_instance_of' => JoinMorphTo::class,
                'related_instance_of' => $joinData['model'],
                'method' => $joinType ? data_get(static::$supportedJoins, $joinType, 'join') : 'join',
                'arguments' => [
                    [
                        'table' => $join,
                        'arguments' => [
                            function () {},
                        ],
                    ],
                ],
            ]);

            /** @var JoinMorphOneOrMany $join */
            $join = $joinBuilder->join();

            $first = "{$joinContext}.id";
            $operator = '=';
            $second = "{$from['table']}.{$joinData['foreign_key']}";

            $expectedOn = compact('first', 'operator', 'second');
            $on = $join->on();
            $this->assertEquals($expectedOn, $on);

            $first = "{$from['table']}.{$joinData['foreign_type']}";
            $second = $joinData['model'];

            $where = compact('first', 'operator', 'second');
            $this->assertEquals($where, collect($join->wheres())->first());
        }
    }
}
