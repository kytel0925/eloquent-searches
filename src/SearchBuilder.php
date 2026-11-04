<?php

namespace SlothDevGuy\Searches;

use Illuminate\Database\Eloquent\Builder;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;

/**
 * Translates a single condition key (and its value) into query builder calls.
 */
interface SearchBuilder
{
    /**
     * Build the condition, or return null when this builder does not handle the key.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidSearchCondition when the key is handled by this builder but is not valid
     */
    public static function buildFromKeyAndValue(Searcher $search, string $key, mixed $value, array $options = []): ?SearchBuilder;

    public function pushInQueryBuilder(Builder $builder): Builder;
}
