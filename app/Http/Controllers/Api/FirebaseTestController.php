<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\FirebaseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Notifications\BookingNotification;

class FirebaseTestController extends Controller
{
    public function testNotification(Request $request)
    {
        try {
            Log::info('Firebase test notification requested', [
                'user_authenticated' => auth()->check(),
                'user_id' => auth()->id(),
            ]);

            // Send Firebase push notification
            $result = FirebaseHelper::sendToAllUsers(
                'Test Notification',
                'This is a test notification to verify Firebase is working correctly.',
                [
                    'type' => 'test',
                    'timestamp' => now()->toDateTimeString(),
                    'url' => '/restaurant/booking',
                ]
            );

            // Also create a database notification for testing navbar updates
            if (auth()->check()) {
                auth()->user()->notify(new \App\Notifications\BookingNotification([
                    'title' => 'Test Notification',
                    'message' => 'This is a test notification to verify Firebase is working correctly.',
                    'booking_id' => null,
                    'contact_id' => null,
                    'location_id' => null,
                ]));
                
                Log::info('Database notification created for test');
            }

            Log::info('Firebase test notification result', ['success' => $result]);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Test notification sent successfully' : 'Failed to send test notification',
                'user_authenticated' => auth()->check(),
            ]);
        } catch (\Exception $e) {
            Log::error('Firebase test notification error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFirebaseConfig()
    {
        $config = config('services.firebase');
        
        return response()->json([
            'enabled' => $config['enabled'] ?? false,
            'project_id' => $config['project_id'] ?? 'not set',
            'has_client_email' => !empty($config['client_email']),
            'has_private_key' => !empty($config['private_key']),
            'has_vapid_key' => !empty($config['vapid_public_key']),
        ]);
    }

    /**
     * Debug endpoint to check FCM tokens
     */
    public function debugTokens()
    {
        try {
            $allTokens = \App\Models\FcmToken::with('user')->get();
            $activeTokens = \App\Models\FcmToken::active()->with('user')->get();
            
            return response()->json([
                'total_tokens' => $allTokens->count(),
                'active_tokens' => $activeTokens->count(),
                'tokens_detail' => $activeTokens->map(function($token) {
                    return [
                        'id' => $token->id,
                        'user_id' => $token->user_id,
                        'user_name' => $token->user->name ?? 'Unknown',
                        'token_preview' => substr($token->token, 0, 20) . '...',
                        'is_active' => $token->is_active,
                        'last_used_at' => $token->last_used_at,
                        'created_at' => $token->created_at,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Debug tokens error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}