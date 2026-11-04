<?php

namespace Tests\Where;

use SlothDevGuy\Searches\Where\SearchWhereBuilder;
use SlothDevGuy\Searches\Where\Where;
use Tests\TestCase;

/**
 * Class WhereTest
 */
class WhereTest extends TestCase
{
    use MockSearcher, WhereBuilderAssertions;

    /**
     * @return void
     */
    public function test_equals()
    {
        $key = 'foo';
        $value = 'bar';

        /** @var SearchWhereBuilder $whereBuilder */
        $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $value);

        $this->assertNotNull($whereBuilder);

        $this->assertWhereBuilder($whereBuilder, [
            'where_instance_of' => Where::class,
            'method' => 'where',
            'arguments' => [
                "test.{$key}",
                '=',
                $value,
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_operators()
    {
        $operators = ['=', '<>', '>', '>=', '<', '<=', '%_%', '_%', '%_'];
        $field = 'foo';
        $value = 'bar';

        foreach ($operators as $operator) {
            $key = "$field|$operator";

            /** @var SearchWhereBuilder $whereBuilder */
            $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $value);

            $this->assertNotNull($whereBuilder);

            if (in_array($operator, ['%_%', '_%', '%_'])) {
                $value = str_replace('_', $value, $operator);
                $operator = 'like';
            }

            $this->assertWhereBuilder($whereBuilder, [
                'where_instance_of' => Where::class,
                'method' => 'where',
                'arguments' => [
                    "test.{$field}",
                    $operator,
                    $value,
                ],
            ]);
        }
    }

    /**
     * @return void
     */
    public function test_negation()
    {
        $negatedOperators = [
            '=' => '<>',
            '<>' => '=',
            '>' => '<=',
            '>=' => '<',
            '<' => '>=',
            '<=' => '>',
            '_%' => 'not like',
            '%_' => 'not like',
            '%_%' => 'not like',
        ];

        $field = 'foo';
        $value = 'bar';

        foreach ($negatedOperators as $operator => $negatedOperator) {
            $not = collect([true, false])->random() ? 'not' : '!';

            $key = "$field|$not|$operator";

            /** @var SearchWhereBuilder $whereBuilder */
            $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $value);

            $this->assertNotNull($whereBuilder);

            if (in_array($operator, ['%_%', '_%', '%_'])) {
                $value = str_replace('_', $value, $operator);
            }

            $this->assertWhereBuilder($whereBuilder, [
                'where_instance_of' => Where::class,
                'method' => 'where',
                'arguments' => [
                    "test.{$field}",
                    $negatedOperator,
                    $value,
                ],
            ]);
        }
    }

    /**
     * @return void
     */
    protected function test_where_custom_arguments()
    {
        $field = 'foo';
        $operator = 'regexp';
        $value = '\\bar\\b';

        $key = "{$field}|where:{$operator}";

        /** @var SearchWhereBuilder $whereBuilder */
        $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $value);

        $this->assertNotNull($whereBuilder);

        $this->assertWhereBuilder($whereBuilder, [
            'where_instance_of' => Where::class,
            'method' => 'where',
            'arguments' => [
                "test.{$field}",
                $operator,
                $value,
            ],
        ]);
    }
}
