<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'anonymous_user_id',
        'token',
        'device_info',
        'subscribed_topics',
        'auth_type',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'subscribed_topics' => 'array',
    ];

    /**
     * Get the user that owns the FCM token.
     */
    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Scope a query to only include active tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include authenticated user tokens.
     */
    public function scopeAuthenticated($query)
    {
        return $query->where('auth_type', 'authenticated')->whereNotNull('user_id');
    }

    /**
     * Scope a query to only include anonymous user tokens.
     */
    public function scopeAnonymous($query)
    {
        return $query->where('auth_type', 'anonymous')->whereNotNull('anonymous_user_id');
    }

    /**
     * Scope a query to filter by topic subscription.
     */
    public function scopeSubscribedToTopic($query, $topic)
    {
        return $query->whereJsonContains('subscribed_topics', $topic);
    }

    /**
     * Mark token as used.
     */
    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Subscribe token to a topic.
     */
    public function subscribeTopic($topic)
    {
        $topics = $this->subscribed_topics ?? [];
        if (!in_array($topic, $topics)) {
            $topics[] = $topic;
            $this->update(['subscribed_topics' => $topics]);
        }
    }

    /**
     * Unsubscribe token from a topic.
     */
    public function unsubscribeTopic($topic)
    {
        $topics = $this->subscribed_topics ?? [];
        $topics = array_filter($topics, fn($t) => $t !== $topic);
        $this->update(['subscribed_topics' => array_values($topics)]);
    }
}
