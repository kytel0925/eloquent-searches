<?php

use SlothDevGuy\Searches\Join;
use SlothDevGuy\Searches\Where;

return [

    /*
    |--------------------------------------------------------------------------
    | Strict mode
    |--------------------------------------------------------------------------
    |
    | Search conditions usually come from user input. In strict mode a model
    | can only be searched, sorted or joined through the explicit allow lists
    | declared on it ($searchable, $sortable and $searchableRelations). Outside
    | strict mode, any column that is not $hidden can be searched and any
    | relationship method with a Relation return type can be joined.
    |
    */

    'strict' => env('SEARCHES_STRICT', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP requests
    |--------------------------------------------------------------------------
    |
    | Limits applied by SearchRequest to the query string options.
    |
    */

    'request' => [
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Full text searches
    |--------------------------------------------------------------------------
    |
    | Full text conditions with fewer characters than this are ignored.
    |
    */

    'full_text' => [
        'min_length' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Morph relationships
    |--------------------------------------------------------------------------
    |
    | When a model is missing from the morph map, build a kebab-case alias from
    | its class name instead of using the fully qualified class name.
    |
    */

    'morphs' => [
        'build_default_aliases' => env('SEARCH_BUILD_DEFAULT_MORPH_ALIASES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Responses
    |--------------------------------------------------------------------------
    |
    | Keys used by SearchResponseSchema when the results are paginated.
    |
    */

    'responses' => [
        'pagination_keys' => [
            'items' => 'items',
            'page' => 'page',
            'pages' => 'pages',
            'max' => 'max',
            'total' => 'total',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Where builders
    |--------------------------------------------------------------------------
    |
    | Each class handles one family of where methods. Add your own classes
    | (extending Where\BaseWhere) to support more operations.
    |
    */

    'wheres' => [
        Where\Where::class,
        Where\WhereNull::class,
        Where\WhereColumn::class,
        Where\WhereIn::class,
        Where\WhereBetween::class,
        Where\WhereFullText::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Joins
    |--------------------------------------------------------------------------
    |
    | "join_types" maps the argument used in a key (posts|left-join) to the
    | query builder method. "relationships" maps a relationship class (in
    | kebab case) to the adapter that knows how to join it.
    |
    */

    'join_types' => [
        'join' => 'join',
        'inner-join' => 'join',
        'left-join' => 'leftJoin',
        'right-join' => 'rightJoin',
        'cross-join' => 'crossJoin',
    ],

    'relationships' => [
        'belongs-to' => Join\JoinBelongsTo::class,
        'belongs-to-many' => Join\JoinBelongsToMany::class,
        'has-one' => Join\JoinHasOneOrMany::class,
        'has-many' => Join\JoinHasOneOrMany::class,
        'has-one-or-many' => Join\JoinHasOneOrMany::class,
        'morph-to' => Join\JoinMorphTo::class,
        'morph-one-or-many' => Join\JoinMorphOneOrMany::class,
        'morph-one' => Join\JoinMorphOneOrMany::class,
        'morph-many' => Join\JoinMorphOneOrMany::class,
        'morph-to-many' => Join\JoinMorphToMany::class,
    ],

];
