<?php

namespace Tests\Joins;

use SlothDevGuy\Searches\Join\SearchJoinRelationshipBuilder;

trait JoinBuilderAssertions
{
    /**
     * @var string[]
     */
    protected static $supportedJoins = [
        'join' => 'join',
        'inner-join' => 'join',
        'left-join' => 'leftJoin',
        'right-join' => 'rightJoin',
        'cross-join' => 'crossJoin',
    ];

    protected function assertJoinBuilder(SearchJoinRelationshipBuilder $builder, array $expected)
    {
        $join = $builder->join();

        $this->assertInstanceOf(data_get($expected, 'join_instance_of'), $join);

        $this->assertInstanceOf(data_get($expected, 'related_instance_of'), $join->to());

        $this->assertEquals(data_get($expected, 'method'), $join->option('method'));

        $joins = $join->joins();

        foreach ($joins as $indexPayload => $joinPayload) {
            $expectedJoin = data_get($expected, "arguments.{$indexPayload}", []);

            $joinArguments = array_values(data_get($joinPayload, 'arguments', []));

            // table
            $table = data_get($joinPayload, 'table');
            $expectedTable = data_get($expectedJoin, 'table');
            $this->assertEquals($expectedTable, $table);

            $expectedJoinArguments = data_get($expectedJoin, 'arguments');
            $this->assertSameSize($expectedJoinArguments, $joinArguments);

            foreach ($joinArguments as $index => $value) {
                if ($value instanceof \Closure) {
                    $this->assertInstanceOf(\Closure::class, $expectedJoinArguments[$index]);

                    continue;
                }

                $this->assertEquals($value, $expectedJoinArguments[$index]);
            }
        }
    }
}
