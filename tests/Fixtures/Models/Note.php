<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use SlothDevGuy\Searches\EloquentSearch;

/**
 * A model using the EloquentSearch trait without allow lists.
 */
class Note extends Model
{
    use EloquentSearch;

    protected $guarded = [];

    protected $hidden = ['secret'];
}
