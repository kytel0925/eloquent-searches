<?php

namespace SlothDevGuy\Searches;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Joins the table of a relationship (and its pivot table, when there is one).
 */
interface JoinRelationshipBuilder
{
    public function from(): Model;

    public function getFromTableQualifiedField(string $field): string;

    public function to(): Model;

    public function getToTableQualifiedField(string $field): string;

    public function joins(): array;

    public function on(): array;

    public function wheres(): array;

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public function option(string $key, $default = null);

    public function pushInBuilder(Builder $builder): Builder;

    /**
     * @return mixed
     */
    public static function instanceOf(Relation $relationship): bool;

    public static function getQualifiedField(string $field, string $table, ?string $alias = null): string;
}
