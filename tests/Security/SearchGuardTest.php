<?php

namespace Tests\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\Search;
use SlothDevGuy\Searches\Support\SearchGuard;
use Tests\Fixtures\Models\Attachment;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Person;
use Tests\Fixtures\Models\RestrictedPerson;
use Tests\TestCase;

class SearchGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Person::$calls = [];
    }

    public function test_a_key_matching_a_non_relationship_method_is_never_called(): void
    {
        $search = new Search(Person::class, ['archive' => 1, 'save' => 1, 'delete' => 1]);

        $sql = $search->builder()->toSql();

        $this->assertSame([], Person::$calls);
        $this->assertSame(0, Person::query()->count());
        $this->assertStringNotContainsString('join', strtolower($sql));
    }

    public function test_relationships_without_a_return_type_are_not_joined(): void
    {
        $sql = (new Search(Person::class, ['legacyTasks' => 1]))->builder()->toSql();

        $this->assertStringNotContainsString('join', strtolower($sql));
    }

    public function test_relationships_with_a_return_type_are_joined(): void
    {
        $sql = (new Search(Person::class, ['tasks' => ['title' => 'foo']]))->builder()->toSql();

        $this->assertStringContainsString('inner join "tasks"', $sql);
    }

    public function test_the_relationship_allow_list_is_enforced(): void
    {
        $allowed = (new Search(RestrictedPerson::class, ['legacy-tasks' => ['title' => 'foo']]))->builder()->toSql();
        $this->assertStringContainsString('inner join "tasks"', $allowed);

        // tasks() has a Relation return type but is not in the allow list, so it is treated as a column
        $this->expectException(InvalidSearchCondition::class);
        $this->expectExceptionMessage('The field [tasks] can not be used in searches.');

        (new Search(RestrictedPerson::class, ['tasks' => ['title' => 'foo']]))->builder();
    }

    public function test_hidden_columns_can_not_be_searched(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Note::class, ['secret|_%' => 'a']))->builder();
    }

    public function test_hidden_columns_can_not_be_sorted(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Note::class, [], null, ['order' => 'secret,desc']))->get();
    }

    public function test_the_field_allow_list_is_enforced(): void
    {
        (new Search(RestrictedPerson::class, ['name' => 'foo', 'sex' => 'M']))->builder();

        try {
            (new Search(RestrictedPerson::class, ['created_at|>' => '2020-01-01']))->builder();
            $this->fail('A field outside the allow list was accepted.');
        } catch (InvalidSearchCondition $exception) {
            $this->assertSame('created_at|>', $exception->key());
        }
    }

    public function test_the_sortable_allow_list_is_enforced(): void
    {
        (new Search(RestrictedPerson::class, [], null, ['order' => 'name,desc']))->get();

        $this->expectException(InvalidSearchCondition::class);

        (new Search(RestrictedPerson::class, [], null, ['order' => 'last_name']))->get();
    }

    public function test_columns_of_other_tables_can_not_be_referenced(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Person::class, ['tasks.title' => 'foo']))->builder();
    }

    public function test_the_column_compared_by_where_column_is_also_checked(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Note::class, ['title|column' => 'secret']))->builder();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidKeys(): array
    {
        return [
            'sql in the field' => ['name) or (1=1'],
            'sql in the curly field' => ['{name = 1 or 1}'],
            'unknown argument' => ['name|method:whereRaw'],
            'field override' => ['name|field:password'],
            'unsupported where method' => ['name|where-raw'],
            'invalid alias' => ['tasks@t x|join'],
            'unknown join type' => ['tasks|full-outer-join'],
        ];
    }

    #[DataProvider('invalidKeys')]
    public function test_invalid_keys_are_rejected(string $key): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Person::class, [$key => ['foo' => 'bar']]))->builder();
    }

    public function test_morph_to_types_must_be_models(): void
    {
        $this->expectException(InvalidSearchCondition::class);
        $this->expectExceptionMessage('The morph type [stdClass] is not a known model.');

        (new Search(Attachment::class, ['owner:stdClass' => []]))->builder();
    }

    public function test_morph_to_needs_a_type(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Attachment::class, ['owner' => []]))->builder();
    }

    public function test_strict_mode_requires_allow_lists(): void
    {
        $this->app->instance(SearchGuard::class, new SearchGuard(strict: true));

        (new Search(RestrictedPerson::class, ['name' => 'foo', 'legacy-tasks' => []]))->builder();

        $this->expectException(InvalidSearchCondition::class);

        (new Search(Note::class, ['title' => 'foo']))->builder();
    }

    public function test_strict_mode_is_read_from_the_configuration(): void
    {
        $this->app['config']->set('searches.strict', true);
        $this->app->forgetInstance(SearchGuard::class);

        $this->assertTrue(app(SearchGuard::class)->strict());
    }
}
