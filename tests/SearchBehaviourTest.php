<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;
use SlothDevGuy\Searches\Search;
use SlothDevGuy\Searches\Where\SearchWhereBuilder;
use SlothDevGuy\Searches\Where\Where;
use SlothDevGuy\Searches\Where\WhereFullText;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Person;

class SearchBehaviourTest extends TestCase
{
    protected function seedNotes(): void
    {
        foreach ([['alpha', 'a'], ['beta', 'a'], ['gamma', 'b'], ['delta', 'b'], ['epsilon', null]] as [$title, $category]) {
            Note::query()->create(['title' => $title, 'category' => $category]);
        }
    }

    public function test_an_unsupported_key_throws_a_search_exception_instead_of_a_fatal_error(): void
    {
        try {
            (new Search(Person::class, ['name|where-raw' => 'x']))->get();
            $this->fail('The unsupported key was accepted.');
        } catch (InvalidSearchCondition $exception) {
            $this->assertSame('name|where-raw', $exception->key());
        }
    }

    public function test_a_search_can_start_from_a_class_name(): void
    {
        $this->assertInstanceOf(Person::class, (new Search(Person::class))->from());
        $this->assertInstanceOf(Person::class, eloquent_search(Person::class)->from());
    }

    public function test_a_search_can_not_start_from_a_class_that_is_not_a_model(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Search(\stdClass::class);
    }

    public function test_pagination_and_count(): void
    {
        $this->seedNotes();

        $search = new Search(Note::class, [], null, ['max' => 2, 'page' => 2, 'order' => 'title']);

        $this->assertSame(['delta', 'epsilon'], $search->get()->pluck('title')->all());
        $this->assertSame(5, $search->count());
        $this->assertSame(['page' => 2, 'pages' => 3, 'max' => 2, 'total' => 5], $search->pagination());
    }

    public function test_count_can_be_called_before_get(): void
    {
        $this->seedNotes();

        $this->assertSame(2, (new Search(Note::class, ['category' => 'a']))->count());
    }

    public function test_count_takes_distinct_and_group_by_into_account(): void
    {
        $this->seedNotes();

        $grouped = new Search(Note::class, ['category|null|!' => true], null, ['group' => 'category', 'select' => 'category']);
        $this->assertCount(2, $grouped->get());
        $this->assertSame(2, $grouped->count());

        $distinct = new Search(Note::class, [], null, ['distinct' => true, 'select' => 'category']);
        $this->assertSame(3, $distinct->count());
    }

    public function test_order_accepts_strings_lists_and_maps(): void
    {
        $this->seedNotes();

        $titles = fn (mixed $order) => (new Search(Note::class, [], null, ['order' => $order]))->get()->pluck('title')->all();

        $this->assertSame(['gamma', 'epsilon', 'delta', 'beta', 'alpha'], $titles('title,desc'));
        $this->assertSame(['epsilon', 'beta', 'alpha', 'gamma', 'delta'], $titles(['category,asc', 'title,desc']));
        $this->assertSame(['alpha', 'beta', 'delta', 'epsilon', 'gamma'], $titles(['title' => 'asc']));
    }

    public function test_an_invalid_order_direction_is_rejected(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Note::class, [], null, ['order' => 'title,sideways']))->get();
    }

    public function test_group_accepts_comma_separated_columns(): void
    {
        $sql = (new Search(Note::class, [], null, ['group' => 'category, title', 'select' => ['category', 'title']]))
            ->getLastBuilder()
            ->toSql();

        $this->assertStringContainsString('group by "notes"."category", "notes"."title"', $sql);
    }

    public function test_the_in_alias_with_a_single_value_is_a_plain_where(): void
    {
        $builder = SearchWhereBuilder::buildFromKeyAndValue(new Search(Note::class), 'category|in', 'a');

        $this->assertInstanceOf(Where::class, $builder->where());
        $this->assertSame(
            ['method' => 'where', 'arguments' => ['field' => 'notes.category', 'operator' => '=', 'value' => 'a']],
            $builder->where()->where(),
        );
    }

    public function test_full_text_values_with_the_minimum_length_are_searched(): void
    {
        $where = fn (string $value) => SearchWhereBuilder::buildFromKeyAndValue(new Search(Note::class), 'title|full-text', $value)
            ->where();

        $this->assertInstanceOf(WhereFullText::class, $where('abc'));
        $this->assertSame('whereFullText', $where('abc')->where()['method']);
        $this->assertNull($where('ab')->where()['method']);
    }

    public function test_between_needs_exactly_two_values(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Note::class, ['created_at|between' => ['2020-01-01']]))->builder();
    }

    public function test_relationship_conditions_must_be_an_array(): void
    {
        $this->expectException(InvalidSearchCondition::class);

        (new Search(Person::class, ['tasks' => 'foo']))->builder();
    }

    public function test_a_null_condition_skips_falsy_values(): void
    {
        $this->seedNotes();

        $this->assertSame(1, (new Search(Note::class, ['category|null' => 'true']))->count());
        $this->assertSame(5, (new Search(Note::class, ['category|null' => 'false']))->count());
    }

    public function test_nested_or_conditions(): void
    {
        $this->seedNotes();

        $search = new Search(Note::class, [
            'category' => 'a',
            'any|nest' => ['title' => 'alpha', 'title|or' => 'gamma'],
        ]);

        $this->assertSame(['alpha'], $search->get()->pluck('title')->all());
    }

    public function test_joins_filter_by_related_models(): void
    {
        DB::table('persons')->insert([
            ['id' => 1, 'name' => 'Ada', 'last_name' => 'Lovelace', 'birth_date' => '1815-12-10', 'sex' => 'F'],
            ['id' => 2, 'name' => 'Alan', 'last_name' => 'Turing', 'birth_date' => '1912-06-23', 'sex' => 'M'],
        ]);
        DB::table('tasks')->insert([
            ['owner_id' => 1, 'title' => 'Engine notes', 'description' => '-'],
            ['owner_id' => 2, 'title' => 'Enigma', 'description' => '-'],
        ]);

        $search = new Search(Person::class, ['tasks' => ['title|_%' => 'Engine']]);

        $this->assertSame(['Ada'], $search->get()->pluck('name')->all());
        $this->assertSame(1, $search->count());
    }

    public function test_curly_brackets_mark_the_field(): void
    {
        $sql = (new Search(Note::class, ['!|{category}' => 'a']))->builder()->toSql();

        $this->assertStringContainsString('"notes"."category" <> ?', $sql);
    }

    public function test_nested_groups_can_be_negated(): void
    {
        $this->seedNotes();

        $search = new Search(Note::class, ['group|nest|!' => ['category' => 'a', 'title|or' => 'gamma']]);

        // epsilon has a null category, so "not (null = 'a' or ...)" is null and the row is excluded
        $this->assertSame(['delta'], $search->get()->pluck('title')->all());
    }
}
