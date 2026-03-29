<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomNotification extends Model
{
    use SoftDeletes;

    protected $table = 'notifications';

    protected $guarded = [];

    protected $dates = ['read_at', 'created_at', 'updated_at', 'deleted_at'];

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at')->whereNull('deleted_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at')->whereNull('deleted_at');
    }

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    public function softDelete()
    {
        $this->delete();
    }

    public function restoreNotification()
    {
        $this->restore();
    }
}