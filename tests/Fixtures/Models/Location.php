<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'location';

    public function administrativeDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
}
