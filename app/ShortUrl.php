<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    protected $table = 'short_urls';

    protected $fillable = [
        'code',
        'long_url',
        'clicks',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
