<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property Carbon $date
 * @property string $status
 **/
class Availibility extends Model
{
    public function user(): BelongsTo 
    {
        return $this->belongsTo(User::class);
    }
    public function group(): BelongsTo 
    {
        return $this->belongsTo(Group::class);
    }
    
}
