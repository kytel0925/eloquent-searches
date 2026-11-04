<?php

namespace SlothDevGuy\Searches;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

/**
 * Adds search helpers and allow lists to an Eloquent model.
 *
 * Optionally declare on the model:
 *
 *     protected array $searchable = ['name', 'email'];          // columns usable in conditions
 *     protected array $sortable = ['name', 'created_at'];       // columns usable in order/group
 *     protected array $searchableRelations = ['posts'];         // relationships that can be joined
 *
 * @mixin Model
 */
trait EloquentSearch
{
    /**
     * Start a new search over this model.
     *
     * @param  array<string, mixed>|Arrayable<string, mixed>  $conditions
     * @param  array<string, mixed>  $options
     */
    public static function search(array|Arrayable $conditions = [], array $options = []): Searcher
    {
        return (new static)->newSearch($conditions, $options);
    }

    /**
     * Start a new search over this model.
     *
     * Use this method (or the Searches facade) when another trait, such as Laravel Scout's
     * Searchable, already defines a static search() method.
     *
     * @param  array<string, mixed>|Arrayable<string, mixed>  $conditions
     * @param  array<string, mixed>  $options
     */
    public function newSearch(array|Arrayable $conditions = [], array $options = []): Searcher
    {
        return app(SearchFactory::class)->make($this, $conditions, $options);
    }

    /**
     * Columns that can be used in search conditions, or null to allow every column that is not hidden.
     *
     * @return array<int, string>|null
     */
    public function searchableFields(): ?array
    {
        return property_exists($this, 'searchable') ? $this->searchable : null;
    }

    /**
     * Columns that can be used to sort and group, or null to fall back to searchableFields().
     *
     * @return array<int, string>|null
     */
    public function sortableFields(): ?array
    {
        return property_exists($this, 'sortable') ? $this->sortable : null;
    }

    /**
     * Relationship methods that can be joined, or null to allow every method with a Relation return type.
     *
     * @return array<int, string>|null
     */
    public function searchableRelations(): ?array
    {
        return property_exists($this, 'searchableRelations') ? $this->searchableRelations : null;
    }
}
