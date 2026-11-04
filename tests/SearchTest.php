<?php

namespace Tests;

use SlothDevGuy\Searches\Search;
use Tests\Fixtures\Models\Location;
use Tests\Fixtures\Models\Person;

class SearchTest extends TestCase
{
    /**
     * this test are design to ensure the query builder syntax
     *
     * @return void
     */
    public function test_joins()
    {

        $search = new Search(new Person, [
            'addresses' => [
                'country' => [
                    'id|>' => 1,
                ],
                'tags' => [
                    'title' => 'foo-tag',
                ],
                'location' => [
                    'name' => 'bar-city',
                ],
            ],
            'tasks' => [
                'attachments' => [],
                'comments' => [
                    'likes|>' => 5,
                ],
                'title|_%' => 'foo-title',
                'complete' => true,
                'created_at|between' => ['2012-01-01', '2022-01-01'],
            ],
        ]);

        $this->assertNotNull($search->get());

        $sql = $search->builder()->toSql();

        // remove the quotes to more simple assertions
        $sql = str_replace(["'", '"', '`'], '', $sql);

        $whereFields = [
            'country.id',
            'tag.title',
            'location.name',
            'comments.likes',
            'tasks.title',
            'tasks.complete',
            'tasks.created_at',
        ];
        foreach ($whereFields as $whereField) {
            $this->assertStringContainsString($whereField, $sql);
        }

        $bindings = $search->builder()->getBindings();
        $wheres = [
            1,
            'foo-tag',
            'bar-city',
            5,
            'foo-title%',
            '2012-01-01',
            '2022-01-01',
        ];
        foreach ($wheres as $where) {
            $this->assertContains($where, $bindings);
        }
    }

    public function test_join_and_pagination()
    {

        $search = new Search(new Person, [
            'tasks' => [
                'comments' => [
                    'likes|>' => 5,
                ],
            ],
        ], null, [
            'max' => 1,
            'page' => 1,
        ]);

        $search->get();
        $pagination = $search->pagination();
        $this->assertNotNull($search->get());

        $this->assertEquals(1, $pagination['max']);
        $this->assertEquals(1, $pagination['page']);
    }

    public function test_join_relation_with_kebab_syntax()
    {

        $search = eloquent_search(new Location, [
            'administrative-division' => [

            ],
        ]);

        $this->assertNotNull($search->get());
        $sql = $search->builder()->toSql();

        $this->assertStringContainsString('administrative_division', $sql);
    }
}
