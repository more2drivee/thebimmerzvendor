<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiSetting extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'domain',
        'base_url'
    ];
}
