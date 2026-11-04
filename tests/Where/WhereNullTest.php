<?php

namespace Tests\Where;

use SlothDevGuy\Searches\Where\SearchWhereBuilder;
use SlothDevGuy\Searches\Where\WhereNull;
use Tests\TestCase;

class WhereNullTest extends TestCase
{
    use MockSearcher, WhereBuilderAssertions;

    public function test_null()
    {
        $field = 'foo';
        $key = "{$field}|null";

        /** @var SearchWhereBuilder $whereBuilder */
        $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, true);

        $this->assertNotNull($whereBuilder);

        $this->assertWhereBuilder($whereBuilder, [
            'where_instance_of' => WhereNull::class,
            'method' => 'whereNull',
            'arguments' => [
                "test.{$field}",
            ],
        ]);
    }

    public function test_not_null()
    {
        $field = 'foo';
        $key = "{$field}|!|null";

        /** @var SearchWhereBuilder $whereBuilder */
        $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, true);

        $this->assertNotNull($whereBuilder);

        $this->assertWhereBuilder($whereBuilder, [
            'where_instance_of' => WhereNull::class,
            'method' => 'whereNotNull',
            'arguments' => [
                "test.{$field}",
            ],
        ]);
    }
}
