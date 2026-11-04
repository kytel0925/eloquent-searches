<?php

namespace SlothDevGuy\Searches;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use SlothDevGuy\Searches\Http\Requests\SearchRequest;

/**
 * Builds searchers. This is the service behind the Searches facade and the eloquent_search() helper.
 */
class SearchFactory
{
    /**
     * @param  class-string<Searcher>  $searcher
     */
    public function __construct(
        protected string $searcher = Search::class,
    ) {
        if (! is_subclass_of($this->searcher, Searcher::class)) {
            throw new InvalidArgumentException("The searcher [{$this->searcher}] must implement ".Searcher::class.'.');
        }
    }

    /**
     * Create a new search over a model.
     *
     * @param  Model|class-string<Model>  $from
     * @param  array<string, mixed>|Arrayable<string, mixed>  $conditions
     * @param  array<string, mixed>  $options
     */
    public function make(Model|string $from, array|Arrayable $conditions = [], array $options = []): Searcher
    {
        return new $this->searcher($from, $conditions, null, $options);
    }

    /**
     * Create a new search from the conditions and options of a validated search request.
     *
     * @param  Model|class-string<Model>  $from
     * @param  array<string, mixed>  $options  overrides the options found in the request
     */
    public function fromRequest(Model|string $from, SearchRequest $request, array $options = []): Searcher
    {
        return $this->make($from, $request->getConditions(), array_merge($request->searchOptions(), $options));
    }

    /**
     * @return class-string<Searcher>
     */
    public function searcher(): string
    {
        return $this->searcher;
    }
}
