<?php

namespace SlothDevGuy\Searches\Interfaces;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use SlothDevGuy\Searches\Searcher;

/**
 * Maps the results of a search into a response.
 */
interface SearchResponseSchemaInterface extends Arrayable, Jsonable
{
    public function forcePaginationResponse(bool $force = true): SearchResponseSchemaInterface;

    public function setItemResponse(ItemResponseSchemaInterface $itemResponse): SearchResponseSchemaInterface;

    public function map(?callable $mapper = null): array;

    public function search(): Searcher;

    public static function fromSearch(Searcher $search): SearchResponseSchemaInterface;
}
