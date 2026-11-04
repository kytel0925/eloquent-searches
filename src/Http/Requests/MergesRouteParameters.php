<?php

namespace SlothDevGuy\Searches\Http\Requests;

/**
 * Merge route parameters into the search conditions.
 *
 * For a route like /countries/{country}/locations, list "country" in routeParameterKeys()
 * and the search will be filtered by it: ['country' => '{country value}'].
 *
 * @mixin SearchRequest
 */
trait MergesRouteParameters
{
    /**
     * The route parameters merged into the request input.
     *
     * @return array<int, string>|array<string, string> a list of parameter names, or a [parameter => condition key] map
     */
    protected function routeParameterKeys(): array
    {
        return [];
    }

    protected function mergeRouteParameters(): void
    {
        $route = $this->route();

        if (! $route || ! is_object($route)) {
            return;
        }

        $parameters = [];

        foreach ($this->routeParameterKeys() as $parameter => $key) {
            $parameter = is_int($parameter) ? $key : $parameter;

            if ($route->hasParameter($parameter)) {
                $value = $route->parameter($parameter);
                $parameters[$key] = is_object($value) && method_exists($value, 'getRouteKey') ? $value->getRouteKey() : $value;
            }
        }

        $this->merge($parameters);
    }
}
