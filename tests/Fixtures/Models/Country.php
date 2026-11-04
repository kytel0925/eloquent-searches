<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Country extends Model
{
    protected $table = 'country';

    public function administrativeDivisions(): HasMany
    {
        return $this->hasMany(AdministrativeDivision::class);
    }

    public function locations(): HasManyThrough
    {
        return $this->hasManyThrough(Location::class, AdministrativeDivision::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
}
