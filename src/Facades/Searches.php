<?php

namespace SlothDevGuy\Searches\Facades;

use Illuminate\Support\Facades\Facade;
use SlothDevGuy\Searches\SearchFactory;

/**
 * @method static \SlothDevGuy\Searches\Searcher make(\Illuminate\Database\Eloquent\Model|string $from, array|\Illuminate\Contracts\Support\Arrayable $conditions = [], array $options = [])
 * @method static \SlothDevGuy\Searches\Searcher fromRequest(\Illuminate\Database\Eloquent\Model|string $from, \SlothDevGuy\Searches\Http\Requests\SearchRequest $request, array $options = [])
 * @method static string searcher()
 *
 * @see SearchFactory
 */
class Searches extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SearchFactory::class;
    }
}
