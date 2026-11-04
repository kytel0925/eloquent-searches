<?php

namespace SlothDevGuy\Searches;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SlothDevGuy\Searches\Where\WhereArguments;

/**
 * Builds one family of where clauses (where, whereIn, whereNull...).
 */
interface WhereBuilder
{
    /**
     * Return the where builder that should handle the condition (itself by default).
     */
    public function redirect(): WhereBuilder;

    public function from(): Model;

    public function getQualifiedField(string $field): string;

    /**
     * Columns of the searched model referenced by this where (checked by the search guard).
     *
     * @return array<int, string>
     */
    public function columns(): array;

    /**
     * The query builder method and its arguments; a null method skips the where.
     *
     * @return array{method: string|null, arguments?: array<array-key, mixed>}
     */
    public function where(): array;

    public function pushInBuilder(Builder $builder): Builder;

    public function arguments(): WhereArguments;

    public function option(string $key, mixed $default = null): mixed;

    public static function supportsMethod(string $method): bool;
}
