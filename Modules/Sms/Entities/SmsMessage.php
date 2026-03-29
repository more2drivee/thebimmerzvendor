<?php

namespace Modules\Sms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsMessage extends Model
{
    use HasFactory;

    protected $table = 'sms_messages';

    protected $fillable = [
        'name',
        'message_template',
        'description',
        'status',
        'created_by',
        'updated_by',
        'roles',
    ];

    protected $casts = [
        'status' => 'boolean',
        'roles' => 'array',
    ];

    /**
     * Check if a role can send this message
     */
    public function hasRole($roleId)
    {
        return in_array($roleId, $this->roles);
    }

    /**
     * Assign a role to this message
     */
    public function assignRole($roleId)
    {
        if (!$this->hasRole($roleId)) {
            $this->roles[] = $roleId;
            $this->save();
        }
    }

    /**
     * Remove a role from this message
     */
    public function removeRole($roleId)
    {
        $this->roles = array_diff($this->roles, [$roleId]);
        $this->save();
    }

    /**
     * Sync roles for this message
     */
    public function syncRoles($roleIds)
    {
        $this->roles = $roleIds;
        $this->save();
    }
}
