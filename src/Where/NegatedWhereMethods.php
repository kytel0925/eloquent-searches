<?php

namespace SlothDevGuy\Searches\Where;

/**
 * Negate a where by swapping its method (whereIn -> whereNotIn) using static::$negatedMethods.
 */
trait NegatedWhereMethods
{
    protected function negateIfRequired(): static
    {
        $arguments = $this->arguments();

        if ($arguments->not()) {
            $arguments->method(static::$negatedMethods[$arguments->method()] ?? $arguments->method());
        }

        return $this;
    }
}
