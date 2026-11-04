<?php

namespace Tests\Where;

use SlothDevGuy\Searches\Where\SearchWhereBuilder;
use SlothDevGuy\Searches\Where\WhereFullText;
use Tests\TestCase;

/**
 * Class WhereFullTextTest
 */
class WhereFullTextTest extends TestCase
{
    use MockSearcher, WhereBuilderAssertions;

    public function test_full_text()
    {
        $fields = 'foo_1,foo_2';
        $value = 'some full text value';

        $aliases = ['full-text', 'where-full-text'];

        foreach ($aliases as $alias) {
            $key = implode('|', array_filter([$fields, $alias]));

            /** @var SearchWhereBuilder $whereBuilder */
            $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $value);

            $this->assertNotNull($whereBuilder);

            $this->assertWhereBuilder($whereBuilder, [
                'where_instance_of' => WhereFullText::class,
                'method' => 'whereFullText',
                'arguments' => [
                    array_map(fn ($field) => "test.{$field}", explode(',', $fields)),
                    $value,
                ],
            ]);
        }
    }

    public function test_not_full_text()
    {
        $fields = 'foo_1,foo_2';
        $value = 'some full text value';

        $aliases = ['full-text', 'where-full-text'];
        $negations = collect(['not', '!']);

        foreach ($aliases as $alias) {
            $not = $negations->random();
            $key = implode('|', array_filter([$fields, $not, $alias]));

            /** @var SearchWhereBuilder $whereBuilder */
            $whereBuilder = SearchWhereBuilder::buildFromKeyAndValue($this->mockSearcher(), $key, $value);

            $this->assertNotNull($whereBuilder);

            $this->assertWhereBuilder($whereBuilder, [
                'where_instance_of' => WhereFullText::class,
                'method' => 'whereNot',
                'arguments' => [
                    function () {},
                ],
            ]);
        }
    }
}
