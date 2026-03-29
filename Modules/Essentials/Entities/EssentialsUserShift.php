<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;

class EssentialsUserShift extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the user that owns this shift assignment.
     */
    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Get the shift associated with this user shift assignment.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'essentials_shift_id');
    }
}
