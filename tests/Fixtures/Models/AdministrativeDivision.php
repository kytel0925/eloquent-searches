<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdministrativeDivision extends Model
{
    protected $table = 'administrative_division';

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function administrativeDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class);
    }

    public function administrativeDivisions(): HasMany
    {
        return $this->hasMany(AdministrativeDivision::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
