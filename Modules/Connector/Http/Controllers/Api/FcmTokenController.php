<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FcmTokenController extends ApiController
{
    /**
     * Store or update FCM token for authenticated user
     */
   public function store(Request $request)
{
    $request->validate([
        'fcm_token'  => 'required|string',
        'device_info'=> 'nullable|string',
    ]);

    $user = Auth::user();

    Log::info('Connector API: FCM token request received', [
        'token'   => substr($request->fcm_token, 0, 20) . '...',
        'user_id' => Auth::id(),
    ]);

    if (!$user) {
        Log::warning('Connector API: No authenticated user');
        return $this->setStatusCode(401)->respondWithError('Not authenticated');
    }

    try {
        
        $existingToken = FcmToken::where('token', $request->fcm_token)->first();

        if ($existingToken) {

            
            if ($existingToken->user_id !== $user->id) {
                Log::info('Connector API: Token ownership changed', [
                    'old_user_id' => $existingToken->user_id,
                    'new_user_id' => $user->id,
                ]);

                $existingToken->update([
                    'user_id'      => $user->id,
                    'device_info'  => $request->device_info,
                    'is_active'    => true,
                    'last_used_at' => now(),
                ]);
            } else {
                $existingToken->update([
                    'device_info'  => $request->device_info,
                    'is_active'    => true,
                    'last_used_at' => now(),
                ]);
            }

            $fcmToken = $existingToken;

        } else {

            /**
             * 2️⃣ Token جديد
             * هل اليوزر عنده Token قديم؟ → عطله
             */
            FcmToken::where('user_id', $user->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            // إنشاء Token جديد
            $fcmToken = FcmToken::create([
                'user_id'      => $user->id,
                'token'        => $request->fcm_token,
                'device_info'  => $request->device_info,
                'is_active'    => true,
                'last_used_at' => now(),
            ]);
        }

        Log::info('Connector API: FCM token saved successfully', [
            'user_id' => $user->id,
            'token_id'=> $fcmToken->id,
        ]);

        return $this->respond([
            'success'  => true,
            'message'  => 'FCM token registered successfully',
            'token_id' => $fcmToken->id,
            'user_id'  => $user->id,
        ]);

    } catch (\Exception $e) {
        Log::error('Connector API: FCM token error', [
            'user_id' => $user->id,
            'error'   => $e->getMessage(),
        ]);

        return $this->setStatusCode(500)
            ->respondWithError('Failed to register FCM token');
    }
}

    /**
     * Get user's FCM tokens
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->setStatusCode(401)->respondWithError('Not authenticated');
        }

        $tokens = FcmToken::where('user_id', $user->id)
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'token_preview' => substr($token->token, 0, 20) . '...',
                    'device_info' => $token->device_info,
                    'is_active' => $token->is_active,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                ];
            });

        return $this->respond([
            'success' => true,
            'tokens' => $tokens,
            'total_count' => $tokens->count(),
        ]);
    }

    /**
     * Delete FCM token
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->setStatusCode(401)->respondWithError('Not authenticated');
        }

        try {
            $token = FcmToken::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$token) {
                return $this->setStatusCode(404)->respondWithError('FCM token not found');
            }

            $token->delete();

            Log::info('Connector API: FCM token deleted', [
                'user_id' => $user->id,
                'token_id' => $id,
            ]);

            return $this->respond([
                'success' => true,
                'message' => 'FCM token deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Connector API: FCM token deletion error', [
                'user_id' => $user->id,
                'token_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->setStatusCode(500)->respondWithError('Failed to delete FCM token');
        }
    }

    /**
     * Update FCM token status (activate/deactivate)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return $this->setStatusCode(401)->respondWithError('Not authenticated');
        }

        try {
            $token = FcmToken::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$token) {
                return $this->setStatusCode(404)->respondWithError('FCM token not found');
            }

            $token->update([
                'is_active' => $request->is_active,
                'last_used_at' => now(),
            ]);

            Log::info('Connector API: FCM token status updated', [
                'user_id' => $user->id,
                'token_id' => $id,
                'is_active' => $request->is_active,
            ]);

            return $this->respond([
                'success' => true,
                'message' => 'FCM token status updated successfully',
                'is_active' => $token->is_active,
            ]);

        } catch (\Exception $e) {
            Log::error('Connector API: FCM token status update error', [
                'user_id' => $user->id,
                'token_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->setStatusCode(500)->respondWithError('Failed to update FCM token status');
        }
    }

    /**
     * Test notification endpoint for debugging
     */
    public function testNotification(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->setStatusCode(401)->respondWithError('Not authenticated');
        }

        try {
            // Send test notification using the NotificationService
            $result = \App\Services\NotificationService::sendToUserIds(
                [$user->id],
                'Test Notification',
                'This is a test notification from the Connector API.',
                [
                    'type' => 'test',
                    'source' => 'connector_api',
                    'timestamp' => now()->toDateTimeString(),
                    'url' => '/restaurant/booking',
                ]
            );

            Log::info('Connector API: Test notification sent', [
                'user_id' => $user->id,
                'success' => $result,
            ]);

            return $this->respond([
                'success' => true,
                'message' => $result ? 'Test notification sent successfully' : 'Failed to send test notification',
                'success' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Connector API: Test notification error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->setStatusCode(500)->respondWithError('Failed to send test notification');
        }
    }

    /**
     * Register FCM token for anonymous user
     */
    public function storeAnonymous(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'anonymous_user_id' => 'required|string',
            'device_info' => 'nullable|string',
        ]);

        Log::info('Connector API: Anonymous FCM token registration request', [
            'token' => substr($request->fcm_token, 0, 20) . '...',
            'anonymous_user_id' => $request->anonymous_user_id,
        ]);

        try {
            $existingToken = FcmToken::where('token', $request->fcm_token)->first();
            
            if ($existingToken && $existingToken->anonymous_user_id !== $request->anonymous_user_id) {
                Log::info('Connector API: FCM token exists for different anonymous user, updating', [
                    'old_anonymous_user_id' => $existingToken->anonymous_user_id,
                    'new_anonymous_user_id' => $request->anonymous_user_id,
                ]);
                
                $existingToken->update([
                    'user_id' => null,
                    'anonymous_user_id' => $request->anonymous_user_id,
                    'device_info' => $request->device_info,
                    'auth_type' => 'anonymous',
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
                
                $fcmToken = $existingToken;
            } else {
                $fcmToken = FcmToken::updateOrCreate(
                    [
                        'anonymous_user_id' => $request->anonymous_user_id,
                        'token' => $request->fcm_token,
                    ],
                    [
                        'user_id' => null,
                        'device_info' => $request->device_info,
                        'auth_type' => 'anonymous',
                        'is_active' => true,
                        'last_used_at' => now(),
                    ]
                );
            }

            Log::info('Connector API: Anonymous FCM token registered', [
                'anonymous_user_id' => $request->anonymous_user_id,
                'fcm_token_id' => $fcmToken->id,
                'action' => $fcmToken->wasRecentlyCreated ? 'created' : 'updated',
            ]);

            return $this->respond([
                'success' => true,
                'message' => 'Anonymous FCM token registered successfully',
                'token_id' => $fcmToken->id,
                'anonymous_user_id' => $request->anonymous_user_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Connector API: Anonymous FCM token registration error', [
                'anonymous_user_id' => $request->anonymous_user_id,
                'token' => substr($request->fcm_token, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);

            return $this->setStatusCode(500)->respondWithError('Failed to register anonymous FCM token');
        }
    }

    /**
     * Subscribe anonymous user to a topic
     */
    public function subscribeToTopic(Request $request)
    {
        $request->validate([
            'anonymous_user_id' => 'required|string',
            'topic' => 'required|string',
        ]);

        Log::info('Connector API: Topic subscription request', [
            'anonymous_user_id' => $request->anonymous_user_id,
            'topic' => $request->topic,
        ]);

        try {
            $fcmTokens = FcmToken::where('anonymous_user_id', $request->anonymous_user_id)
                ->active()
                ->get();

            if ($fcmTokens->isEmpty()) {
                Log::warning('Connector API: No active FCM tokens found for anonymous user', [
                    'anonymous_user_id' => $request->anonymous_user_id,
                ]);
                return $this->setStatusCode(404)->respondWithError('No active FCM tokens found');
            }

            $successCount = 0;
            foreach ($fcmTokens as $token) {
                $token->subscribeTopic($request->topic);
                $successCount++;
            }

            Log::info('Connector API: Topic subscription completed', [
                'anonymous_user_id' => $request->anonymous_user_id,
                'topic' => $request->topic,
                'tokens_updated' => $successCount,
            ]);

            return $this->respond([
                'success' => true,
                'message' => 'Subscribed to topic successfully',
                'topic' => $request->topic,
                'tokens_updated' => $successCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Connector API: Topic subscription error', [
                'anonymous_user_id' => $request->anonymous_user_id,
                'topic' => $request->topic,
                'error' => $e->getMessage(),
            ]);

            return $this->setStatusCode(500)->respondWithError('Failed to subscribe to topic');
        }
    }

    /**
     * Unsubscribe anonymous user from a topic
     */
    public function unsubscribeFromTopic(Request $request)
    {
        $request->validate([
            'anonymous_user_id' => 'required|string',
            'topic' => 'required|string',
        ]);

        Log::info('Connector API: Topic unsubscription request', [
            'anonymous_user_id' => $request->anonymous_user_id,
            'topic' => $request->topic,
        ]);

        try {
            $fcmTokens = FcmToken::where('anonymous_user_id', $request->anonymous_user_id)
                ->active()
                ->get();

            if ($fcmTokens->isEmpty()) {
                Log::warning('Connector API: No active FCM tokens found for anonymous user', [
                    'anonymous_user_id' => $request->anonymous_user_id,
                ]);
                return $this->setStatusCode(404)->respondWithError('No active FCM tokens found');
            }

            $successCount = 0;
            foreach ($fcmTokens as $token) {
                $token->unsubscribeTopic($request->topic);
                $successCount++;
            }

            Log::info('Connector API: Topic unsubscription completed', [
                'anonymous_user_id' => $request->anonymous_user_id,
                'topic' => $request->topic,
                'tokens_updated' => $successCount,
            ]);

            return $this->respond([
                'success' => true,
                'message' => 'Unsubscribed from topic successfully',
                'topic' => $request->topic,
                'tokens_updated' => $successCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Connector API: Topic unsubscription error', [
                'anonymous_user_id' => $request->anonymous_user_id,
                'topic' => $request->topic,
                'error' => $e->getMessage(),
            ]);

            return $this->setStatusCode(500)->respondWithError('Failed to unsubscribe from topic');
        }
    }

    /**
     * Get anonymous user's subscribed topics
     */
    public function getAnonymousTopics(Request $request)
    {
        $request->validate([
            'anonymous_user_id' => 'required|string',
        ]);

        try {
            $fcmTokens = FcmToken::where('anonymous_user_id', $request->anonymous_user_id)
                ->active()
                ->get();

            if ($fcmTokens->isEmpty()) {
                return $this->respond([
                    'success' => true,
                    'topics' => [],
                    'message' => 'No active FCM tokens found',
                ]);
            }

            $allTopics = [];
            foreach ($fcmTokens as $token) {
                $topics = $token->subscribed_topics ?? [];
                $allTopics = array_unique(array_merge($allTopics, $topics));
            }

            return $this->respond([
                'success' => true,
                'anonymous_user_id' => $request->anonymous_user_id,
                'topics' => array_values($allTopics),
                'token_count' => $fcmTokens->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Connector API: Get anonymous topics error', [
                'anonymous_user_id' => $request->anonymous_user_id,
                'error' => $e->getMessage(),
            ]);

            return $this->setStatusCode(500)->respondWithError('Failed to retrieve topics');
        }
    }
}