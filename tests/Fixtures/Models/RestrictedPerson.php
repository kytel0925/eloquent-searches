<?php

namespace Tests\Fixtures\Models;

use SlothDevGuy\Searches\EloquentSearch;

/**
 * A person with explicit search allow lists.
 */
class RestrictedPerson extends Person
{
    use EloquentSearch;

    protected $hidden = ['birth_date'];

    protected array $searchable = ['name', 'last_name', 'sex'];

    protected array $sortable = ['name'];

    protected array $searchableRelations = ['legacyTasks'];
}
