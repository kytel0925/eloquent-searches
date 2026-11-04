<?php

namespace SlothDevGuy\Searches\Interfaces;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Maps one model of the search results into a response item.
 */
interface ItemResponseSchemaInterface extends Arrayable, Jsonable
{
    /**
     * @param  mixed  $item
     * @return mixed
     */
    public function item($item = null);

    public function map(): array;

    public function only(?array $keys = null): ItemResponseSchemaInterface;

    public function except(?array $keys = null): ItemResponseSchemaInterface;

    public static function mapEach(): Closure;
}
