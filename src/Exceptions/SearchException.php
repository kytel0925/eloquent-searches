<?php

namespace SlothDevGuy\Searches\Exceptions;

use Throwable;

/**
 * Marker interface implemented by every exception thrown by this package.
 */
interface SearchException extends Throwable
{
    /**
     * The search condition key that caused the error, when known.
     */
    public function key(): ?string;
}
