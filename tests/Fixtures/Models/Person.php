<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $table = 'persons';

    protected $guarded = [];

    /**
     * Records the calls to methods that must never be invoked by a search.
     *
     * @var array<int, string>
     */
    public static array $calls = [];

    /**
     * A public method without parameters that is not a relationship.
     */
    public function archive()
    {
        static::$calls[] = 'archive';

        return $this;
    }

    /**
     * A relationship without a return type: only joinable through an allow list.
     */
    public function legacyTasks()
    {
        return $this->hasMany(Task::class, 'owner_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'owner_id');
    }

    public function tasksFollowed(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_follower');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'owner_id');
    }
}
