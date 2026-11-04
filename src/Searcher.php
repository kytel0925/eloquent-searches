<?php

namespace SlothDevGuy\Searches;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Translates a set of array conditions into an Eloquent query.
 */
interface Searcher
{
    /**
     * The model the search starts from.
     */
    public function from(): Model;

    /**
     * Get (or set) the selected columns.
     *
     * @param  array<int, string>|string|null  $select
     * @return array<int, string>|string
     */
    public function select(array|string|null $select = null): array|string;

    /**
     * Get (or set) the distinct flag.
     */
    public function distinct(?bool $distinct = null): bool;

    /**
     * Run the search and return the (paginated) results.
     *
     * @return Collection<int, Model>
     */
    public function get(): Collection;

    /**
     * Count every result of the search, ignoring the pagination.
     */
    public function count(bool $reCount = false): int;

    /**
     * Get the pagination state: page, pages, max and total.
     *
     * @return array{page: int|null, pages: int|null, max: int|null, total: int|null}
     */
    public function pagination(): array;

    /**
     * Get the query builder with every condition (joins and wheres) applied.
     */
    public function builder(): Builder;

    /**
     * Get the builder used by the last get() call, before pagination was applied.
     */
    public function getLastBuilder(): Builder;

    /**
     * Qualify a field with the table (or alias) of the model the search starts from.
     */
    public function getFromQualifiedField(string $field): string;

    public function option(string $key, mixed $default = null): mixed;

    /**
     * @return array<string, mixed>
     */
    public function options(): array;
}
