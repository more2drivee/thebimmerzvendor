<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $adminIds;
    public $data;
    public $notificationType; 
    public $fcmData;

    /**
     * @param array 
     * @param array 
     * @param string 
     * @param array|null 
     */
    public function __construct(array $adminIds, array $data, string $notificationType, ?array $fcmData = null)
    {
        $this->adminIds = $adminIds;
        $this->data = $data;
        $this->notificationType = $notificationType;
        $this->fcmData = $fcmData;
    }

    public function handle()
    {
        try {
Log::info('Job notification started', [
    'admin_ids' => $this->adminIds, 
    'notification_type' => $this->notificationType, 
    'data' => $this->data,  
    'fcm_data' => $this->fcmData,
]);
    app(\App\Services\NotificationService::class)
        ->storeBulkDatabaseNotification(
            userIds: $this->adminIds,
            type: $this->notificationType,
            data: $this->data
        );

    
    if (!empty($this->fcmData)) {

        $fcmTokens = \App\Models\FcmToken::whereIn('user_id', $this->adminIds)
            ->where('is_active', 1)
            ->distinct()
            ->pluck('token')
            ->toArray();

          if (!empty($fcmTokens)) {

    $allowedNotificationKeys = ['title', 'body', 'image'];

    $notification = collect($this->fcmData['message']['notification'] ?? [])
        ->only($allowedNotificationKeys)
        ->toArray();

    $data = collect($this->fcmData['message']['data'] ?? [])
        ->map(fn ($value) => (string) $value)
        ->toArray();

    $title = $notification['title'] ?? '';
    $description = $notification['body'] ?? '';

    Log::info('Firebase: Sending notifications', [
        'tokens_count' => count($fcmTokens),
        'title' => $title,
        'description' => $description,
    ]);

    $success = \App\Helpers\FirebaseHelper::send_push_notif_to_devices(
        $fcmTokens,
        [
            'notification' => $notification,
            'data' => $data,
        ],
        false
    );

    Log::info('Firebase: Notifications sent', [
        'tokens_count' => count($fcmTokens),
        'success' => $success,
    ]);

} else {
    Log::warning('Firebase: No FCM tokens found to send notifications');
}
    }

} catch (\Throwable $e) {
    Log::error('Job notification failed', [
        'error' => $e->getMessage(),
    ]);
} catch (\Throwable $e) {
            Log::error('Job notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
