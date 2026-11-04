<?php

namespace SlothDevGuy\Searches\Support;

/**
 * Small helpers to work with (possibly qualified) column names.
 */
final class Fields
{
    /**
     * Prefix a field with a table name (or alias), unless it is already qualified.
     */
    public static function qualify(string $field, string $table, ?string $alias = null): string
    {
        $alias = $alias ?: $table;

        if ($alias === '' || str_contains($field, '.')) {
            return $field;
        }

        return "{$alias}.{$field}";
    }
}
