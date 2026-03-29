<?php

namespace Modules\Sms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsLog extends Model
{
    use HasFactory;

    protected $table = 'sms_logs';

    protected $fillable = [
        'sms_message_id',
        'contact_id',
        'transaction_id',
        'job_sheet_id',
        'mobile',
        'message_content',
        'status',
        'error_message',
        'provider_balance',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(SmsMessage::class, 'sms_message_id');
    }
}
