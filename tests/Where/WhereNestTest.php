<?php

namespace Tests\Where;

use SlothDevGuy\Searches\Where\SearchWhereBuilder;
use SlothDevGuy\Searches\Where\WhereNest;
use Tests\TestCase;

class WhereNestTest extends TestCase
{
    use MockSearcher, WhereBuilderAssertions;

    public function test_nest()
    {
        $field = 'foo_key';
        $values = [
            'foo' => 'bar',
            'some' => 'value',
        ];
        $key = "{$field}|nest";

        /** @var SearchWhereBuilder $whereBuilder */
        $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher([
            'get_from_qualified_field' => false,
        ]), $key, $values);

        $this->assertNotNull($whereBuilder);

        $this->assertWhereBuilder($whereBuilder, [
            'where_instance_of' => WhereNest::class,
            'method' => 'where',
            'arguments' => [
                function () {},
            ],
        ]);
    }

    public function test_not_nest()
    {
        $field = 'foo_key';
        $values = [
            'foo' => 'bar',
            'some' => 'value',
        ];
        $key = "{$field}|!|nest";

        /** @var SearchWhereBuilder $whereBuilder */
        $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher([
            'get_from_qualified_field' => false,
        ]), $key, $values);

        $this->assertNotNull($whereBuilder);

        $this->assertWhereBuilder($whereBuilder, [
            'where_instance_of' => WhereNest::class,
            'method' => 'whereNot',
            'arguments' => [
                function () {},
            ],
        ]);
    }
}
