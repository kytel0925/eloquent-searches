<?php

namespace Tests\Where;

use SlothDevGuy\Searches\Where\SearchWhereBuilder;
use SlothDevGuy\Searches\Where\WhereIn;
use Tests\TestCase;

/**
 * Class WhereInTest
 */
class WhereInTest extends TestCase
{
    use MockSearcher, WhereBuilderAssertions;

    public function test_in()
    {
        $field = 'foo';
        $values = ['bar', 'some', 'data'];

        $aliases = [null, 'in', 'where-in'];

        foreach ($aliases as $alias) {
            $key = implode('|', array_filter([$field, $alias]));

            /** @var SearchWhereBuilder $whereBuilder */
            $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $values);

            $this->assertNotNull($whereBuilder);

            $this->assertWhereBuilder($whereBuilder, [
                'where_instance_of' => WhereIn::class,
                'method' => 'whereIn',
                'arguments' => [
                    "test.{$field}",
                    $values,
                ],
            ]);
        }
    }

    public function test_not_in()
    {
        $field = 'foo';
        $values = ['bar', 'some', 'data'];

        $aliases = [null, 'in', 'where-in'];

        $negations = collect(['not', '!']);

        foreach ($aliases as $alias) {
            $not = $negations->random();
            $key = implode('|', array_filter([$field, $not, $alias]));

            /** @var SearchWhereBuilder $whereBuilder */
            $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $values);

            $this->assertNotNull($whereBuilder);

            $this->assertWhereBuilder($whereBuilder, [
                'where_instance_of' => WhereIn::class,
                'method' => 'whereNotIn',
                'arguments' => [
                    "test.{$field}",
                    $values,
                ],
            ]);
        }
    }
}
