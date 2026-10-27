<?php

namespace App\Models;

use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $invite_code
 * @property int $admin_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 **/
#[Fillable(['name', 'invite_code', 'admin_user_id'])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * @return HasMany<GroupMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function event(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<Availability, $this>
     */
    public function availability(): HasMany
    {
        return $this->hasMany(Availability::class);
    }
}
