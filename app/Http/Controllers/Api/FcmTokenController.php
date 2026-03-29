<?php

namespace App\Http\Controllers\Api;

use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FcmTokenController extends Controller
{
  public function update(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
        'user_id'   => 'nullable|integer',
        'device_info' => 'nullable|string',
    ]);

    $user = Auth::user();
    $userId = $user ? $user->id : null;

    Log::info('FCM token request received', [
        'token'    => substr($request->fcm_token, 0, 20) . '...',
        'user_id'  => $userId,
        'device_info' => $request->device_info ?? null,
    ]);

    try {
        $existingToken = FcmToken::where('token', $request->fcm_token)->first();

        if ($existingToken) {
            if ($existingToken->user_id !== $userId) {
                Log::info('FCM token ownership changed', [
                    'old_user_id' => $existingToken->user_id,
                    'new_user_id' => $userId,
                ]);

                $existingToken->update([
                    'user_id'           => $userId,
                    'anonymous_user_id' => null,
                    'device_info'       => $request->device_info,
                    'is_active'         => true,
                    'last_used_at'      => now(),
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
            $fcmToken = FcmToken::create([
                'anonymous_user_id' => null,
                'user_id'           => $userId,
                'token'             => $request->fcm_token,
                'device_info'       => $request->device_info,
                'is_active'         => true,
                'last_used_at'      => now(),
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'FCM token saved successfully',
            'token_id' => $fcmToken->id,
            'user_id'  => $userId,
        ]);
    } catch (\Exception $e) {
        Log::error('FCM token error', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update FCM token',
        ], 500);
    }
}

}
