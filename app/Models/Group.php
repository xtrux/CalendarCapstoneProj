<?php

namespace App\Models;

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
    use HasFactory;

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Events::class);
    }

    public function availibility(): HasMany
    {
        return $this->hasMany(Availibility::class);
    }
}
