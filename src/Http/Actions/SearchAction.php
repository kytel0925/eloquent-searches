<?php

namespace SlothDevGuy\Searches\Http\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LogicException;
use SlothDevGuy\Searches\Exceptions\SearchException;
use SlothDevGuy\Searches\Http\Requests\SearchRequest;
use SlothDevGuy\Searches\Interfaces\ItemResponseSchemaInterface as ItemResponse;
use SlothDevGuy\Searches\Interfaces\SearchResponseSchemaInterface as SearchResponse;
use SlothDevGuy\Searches\ResponseModels\ItemResponseSchema;
use SlothDevGuy\Searches\ResponseModels\SearchResponseSchema;
use SlothDevGuy\Searches\Searcher;

/**
 * Runs a search from a SearchRequest and builds the response schema.
 *
 *     public function index(SearchAction $action)
 *     {
 *         return $action->handle(Post::class);
 *     }
 *
 * Invalid conditions (unknown or forbidden fields, bad values...) are reported as a
 * ValidationException, which Laravel renders as a 422 response.
 */
class SearchAction
{
    /**
     * @var class-string<SearchResponse>
     */
    protected static string $defaultResponseClass = SearchResponseSchema::class;

    /**
     * @var class-string<ItemResponse>
     */
    protected static string $defaultResponseItemClass = ItemResponseSchema::class;

    protected ?SearchResponse $response = null;

    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    protected ?Model $from = null;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        protected SearchRequest $request,
        array $options = [],
    ) {
        $this->options(array_merge($this->request->searchOptions(), $this->options, $options));
    }

    /**
     * Run the search over a model and return the response.
     *
     * @param  Model|class-string<Model>  $from
     *
     * @throws ValidationException
     */
    public function handle(Model|string $from): SearchResponse
    {
        $this->from(is_string($from) ? new $from : $from);

        $this->execute();

        return $this->response();
    }

    /**
     * Get (or replace) the search options.
     *
     * @param  array<string, mixed>|null  $options
     * @return array<string, mixed>
     */
    public function options(?array $options = null): array
    {
        if (! is_null($options)) {
            $this->options = $options;
        }

        return $this->options;
    }

    public function distinct(bool $flag = true): bool
    {
        $this->options['distinct'] = $flag;

        return $flag;
    }

    /**
     * Add a group by column.
     *
     * @return array<int, string>
     */
    public function group(string $field): array
    {
        $this->options['group'] = [...(array) ($this->options['group'] ?? []), $field];

        return $this->options['group'];
    }

    public function request(): SearchRequest
    {
        return $this->request;
    }

    /**
     * Get (or set) the response built by execute().
     *
     * @throws LogicException when the search was not executed yet
     */
    public function response(?SearchResponse $response = null): SearchResponse
    {
        if (! is_null($response)) {
            $this->response = $response;
        }

        return $this->response ?? throw new LogicException('Call execute() before reading the search response.');
    }

    /**
     * Get (or set) the model the search starts from.
     *
     * @throws LogicException when no model was set
     */
    public function from(?Model $from = null): Model
    {
        if (! is_null($from)) {
            $this->from = $from;
        }

        return $this->from ?? throw new LogicException('Set the model to search with from() before executing the search.');
    }

    /**
     * @throws ValidationException
     */
    public function execute(): void
    {
        $search = eloquent_search($this->from(), $this->request()->getConditions(), $this->options());

        try {
            // build the query now, so invalid conditions are reported before the response is rendered
            $search->builder();
            $search->getLastBuilder();
        } catch (SearchException $exception) {
            throw ValidationException::withMessages([
                $exception->key() ?? 'search' => $exception->getMessage(),
            ]);
        }

        $this->response(static::buildSearchResponse($search, $this->options()));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function buildSearchResponse(Searcher $search, array $options): SearchResponse
    {
        $responseClass = static::$defaultResponseClass;

        return $responseClass::fromSearch($search)
            ->setItemResponse(static::buildItemResponse($options));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function buildItemResponse(array $options): ItemResponse
    {
        return new static::$defaultResponseItemClass;
    }
}
