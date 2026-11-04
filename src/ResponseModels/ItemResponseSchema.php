<?php

namespace SlothDevGuy\Searches\ResponseModels;

use Closure;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use SlothDevGuy\Searches\Interfaces\ItemResponseSchemaInterface;

/**
 * Maps one model of the search results into its array (JSON) representation.
 *
 * @phpstan-consistent-constructor
 */
class ItemResponseSchema implements ItemResponseSchemaInterface, JsonSerializable
{
    protected array $map = [];

    protected ?array $only = null;

    protected ?array $except = null;

    public function __construct(
        protected ?Model $item = null
    ) {}

    /**
     * @param  mixed  $item
     * @return Model
     */
    public function item($item = null)
    {
        if (! is_null($item)) {
            $this->item = $item;
            $this->map = [];
        }

        return $this->item;
    }

    /**
     * {@inheritDoc}
     */
    public function map(): array
    {
        if (! $this->item()) {
            return [];
        }

        if (empty($this->map)) {
            $this->map = $this->mapItem();
        }

        $map = $this->map;

        if (! is_null($this->only)) {
            $map = collect($map)
                ->only($this->only)
                ->toArray();
        }

        if (! is_null($this->except)) {
            $map = collect($map)
                ->except($this->except)
                ->toArray();
        }

        return $map;
    }

    /**
     * {@inheritDoc}
     */
    public function only(?array $keys = null): ItemResponseSchemaInterface
    {
        $this->only = $keys;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function except(?array $keys = null): ItemResponseSchemaInterface
    {
        $this->except = $keys;

        return $this;
    }

    protected function mapItem(): array
    {
        return $this->item()->toArray();
    }

    public function toArray(): array
    {
        return $this->map();
    }

    /**
     * @param  int  $options
     * @return false|string
     */
    public function toJson($options = 0): string
    {
        return (string) json_encode($this->toArray(), $options);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function mapEach(): Closure
    {
        return function (Model $model) {
            return new static($model);
        };
    }
}
