<?php

namespace SlothDevGuy\Searches\ResponseModels;

use JsonSerializable;
use SlothDevGuy\Searches\Interfaces\ItemResponseSchemaInterface;
use SlothDevGuy\Searches\Interfaces\SearchResponseSchemaInterface;
use SlothDevGuy\Searches\Searcher;

/**
 * Maps the search results, with pagination data when the search is paginated.
 *
 * @phpstan-consistent-constructor
 */
class SearchResponseSchema implements JsonSerializable, SearchResponseSchemaInterface
{
    protected bool $forcePagination = false;

    /**
     * @var array<int, mixed>|null
     */
    protected ?array $map = null;

    public function __construct(
        protected Searcher $search,
        protected ItemResponseSchemaInterface $itemResponse
    ) {}

    public function search(): Searcher
    {
        return $this->search;
    }

    public function forcePaginationResponse(bool $force = true): SearchResponseSchemaInterface
    {
        $this->forcePagination = $force;

        return $this;
    }

    public function setItemResponse(ItemResponseSchemaInterface $itemResponse): SearchResponseSchemaInterface
    {
        $this->itemResponse = $itemResponse;
        $this->map = null;

        return $this;
    }

    public function toArray(): array
    {
        return $this->withPagination() ?
            $this->mapWithPagination() :
            $this->map();
    }

    protected function withPagination(): bool
    {
        $pagination = $this->search()->pagination();
        $page = data_get($pagination, 'page');
        $max = data_get($pagination, 'max');

        return ($page > 0 && $max > 0) || $this->forcePagination;
    }

    public function map(?callable $mapper = null): array
    {
        if ($mapper) {
            return $this->search()->get()->map($mapper)->toArray();
        }

        return $this->map ??= $this->search()->get()
            ->map($this->itemResponse::mapEach())
            ->toArray();
    }

    protected function mapWithPagination(): array
    {
        $mapWithPagination = [];
        $itemKey = config('searches.responses.pagination_keys.items', 'items');
        $mapWithPagination[$itemKey] = $this->map();

        $this->search()->count();
        $pagination = $this->search()->pagination();
        foreach ($pagination as $key => $value) {
            $newKey = config("searches.responses.pagination_keys.{$key}", $key);
            // all pagination values are integers
            $mapWithPagination[$newKey] = (int) $value;
        }

        return $mapWithPagination;
    }

    /**
     * @param  int  $options
     * @return false|string
     */
    public function toJson($options = 0): string
    {
        return (string) json_encode($this->toArray(), $options);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromSearch(Searcher $search): SearchResponseSchemaInterface
    {
        return new static($search, new ItemResponseSchema);
    }
}
