<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Task extends Model
{
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'owner_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'task_follower');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'owner');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggable');
    }
}
