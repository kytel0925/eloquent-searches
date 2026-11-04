<?php

namespace SlothDevGuy\Searches;

/**
 * Read access to an array of options.
 */
trait BuilderOptions
{
    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    public function option(string $key, mixed $default = null): mixed
    {
        return data_get($this->options, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function defaultOptions(array $options = []): array
    {
        return $options;
    }
}
