<?php

namespace Tests\Http;

use SlothDevGuy\Searches\Http\Actions\SearchAction;
use SlothDevGuy\Searches\Http\Requests\SearchRequest;
use Tests\Fixtures\Models\Note;
use Tests\TestCase;

class SearchEndpointTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('notes', fn (SearchAction $action) => $action->handle(Note::class));

        $router->get('categories/{category}/notes', fn (CategoryNotesRequest $request) => (new SearchAction($request))->handle(Note::class));

        $router->get('options', fn (SearchRequest $request) => [
            'conditions' => $request->getConditions(),
            'options' => $request->searchOptions(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([['alpha', 'a'], ['beta', 'a'], ['gamma', 'b']] as [$title, $category]) {
            Note::query()->create(['title' => $title, 'category' => $category, 'secret' => 's3cr3t']);
        }
    }

    public function test_it_returns_the_matching_models(): void
    {
        $this->getJson('notes?category=a&order=title,desc')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.title', 'beta')
            ->assertJsonMissingPath('0.secret');
    }

    public function test_it_paginates(): void
    {
        $this->getJson('notes?order=title&max=2&page=2')
            ->assertOk()
            ->assertExactJson([
                'items' => [Note::query()->where('title', 'gamma')->first()->toArray()],
                'page' => 2,
                'pages' => 2,
                'max' => 2,
                'total' => 3,
            ]);
    }

    public function test_the_pagination_keys_can_be_configured(): void
    {
        config(['searches.responses.pagination_keys.items' => 'data']);

        $this->getJson('notes?max=1&page=1')->assertOk()->assertJsonStructure(['data', 'page', 'pages', 'max', 'total']);
    }

    public function test_the_group_option_accepts_comma_separated_columns(): void
    {
        $this->getJson('options?group=category,title&order=title,desc&max=5&distinct=1&title=foo')
            ->assertOk()
            ->assertExactJson([
                'conditions' => ['title' => 'foo'],
                'options' => [
                    'page' => null,
                    'max' => 5,
                    'order' => ['title,desc'],
                    'group' => ['category', 'title'],
                    'distinct' => true,
                ],
            ]);
    }

    public function test_invalid_options_are_a_validation_error(): void
    {
        $this->getJson('notes?max=1000')->assertUnprocessable()->assertJsonValidationErrors('max');
        $this->getJson('notes?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_invalid_conditions_are_a_validation_error(): void
    {
        $this->getJson('notes?secret=s3cr3t')->assertUnprocessable()->assertJsonValidationErrors('secret');
        $this->getJson('notes?'.http_build_query(['title|where-raw' => 'x']))->assertUnprocessable()->assertJsonValidationErrors('title|where-raw');
        $this->getJson('notes?order=secret')->assertUnprocessable()->assertJsonValidationErrors('order');
    }

    public function test_route_parameters_can_be_merged_into_the_conditions(): void
    {
        $this->getJson('categories/b/notes')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'gamma');
    }
}

class CategoryNotesRequest extends SearchRequest
{
    protected function routeParameterKeys(): array
    {
        return ['category'];
    }
}
