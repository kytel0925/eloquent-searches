<?php

namespace SlothDevGuy\Searches\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a search condition, sort or group can not be safely translated into a query.
 *
 * The messages are written so they can be shown to API clients (for example as a 422 response).
 *
 * @phpstan-consistent-constructor
 */
class InvalidSearchCondition extends InvalidArgumentException implements SearchException
{
    protected ?string $key = null;

    public function key(): ?string
    {
        return $this->key;
    }

    /**
     * Attach the condition key to the exception (the first key set wins, so the innermost key is kept).
     */
    public function withKey(string $key): static
    {
        $this->key ??= $key;

        return $this;
    }

    public static function unsupportedKey(string $key): static
    {
        return (new static("The search key [{$key}] is not supported."))->withKey($key);
    }

    public static function invalidField(string $field): static
    {
        return new static("The field [{$field}] is not a valid column name.");
    }

    public static function forbiddenField(string $field): static
    {
        return new static("The field [{$field}] can not be used in searches.");
    }

    public static function unknownArgument(string $argument): static
    {
        return new static("The search argument [{$argument}] is not supported.");
    }

    public static function invalidValue(string $reason): static
    {
        return new static("Invalid search value: {$reason}.");
    }

    public static function invalidAlias(string $alias): static
    {
        return new static("The alias [{$alias}] is not a valid identifier.");
    }

    public static function unsupportedRelation(string $relation, string $type): static
    {
        return new static("The relationship [{$relation}] of type [{$type}] can not be joined.");
    }

    public static function invalidMorphType(string $type): static
    {
        return new static("The morph type [{$type}] is not a known model.");
    }

    public static function invalidOrder(string $order): static
    {
        return (new static("The order [{$order}] is not valid, use \"column\" or \"column,asc|desc\"."))->withKey('order');
    }
}
