<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use SlothDevGuy\Searches\Search;
use SlothDevGuy\Searches\Searcher;

if (! function_exists('eloquent_search')) {
    /**
     * Create a new search over a model.
     *
     * @param  Model|class-string<Model>  $from
     * @param  array<string, mixed>|Arrayable<string, mixed>  $conditions
     * @param  array<string, mixed>  $options
     */
    function eloquent_search(Model|string $from, array|Arrayable $conditions = [], array $options = []): Searcher
    {
        return new Search($from, $conditions, null, $options);
    }
}

if (! function_exists('morph_model_alias')) {
    /**
     * Get the alias registered for a model in the morph map.
     *
     * When the model is not mapped and "searches.morphs.build_default_aliases" is enabled,
     * a kebab-case alias is built from the class name; otherwise null is returned.
     *
     * @param  Model|class-string<Model>  $model
     */
    function morph_model_alias(Model|string $model, bool $strict = true): ?string
    {
        $class = is_object($model) ? get_class($model) : $model;

        $alias = array_search($class, Relation::morphMap(), $strict);

        if ($alias !== false) {
            return (string) $alias;
        }

        if (config('searches.morphs.build_default_aliases')) {
            return Str::kebab(class_basename($class));
        }

        return null;
    }
}
