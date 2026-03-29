<?php

namespace App\Helpers;

use App\Models\FcmToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseHelper
{
public static function getAccessToken(array $key): ?string{
    try {
        Log::info('Firebase: Generating JWT for access token');

        $jwtToken = [
            'iss' => $key['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $key['token_uri'],
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $jwtHeader = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $jwtPayload = self::base64UrlEncode(json_encode($jwtToken));

        $unsignedJwt = $jwtHeader . '.' . $jwtPayload;

        $signature = '';
        $privateKey = str_replace("\\n", "\n", $key['private_key']);

        $success = openssl_sign(
            $unsignedJwt,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$success) {
            Log::error('Firebase: Failed to sign JWT with private key');
            return null;
        }

        $jwt = $unsignedJwt . '.' . self::base64UrlEncode($signature);
        Log::info('Firebase JWT generated', ['jwt' => substr($jwt, 0, 50) . '...']);

       $response = Http::asForm()
    ->post($key['token_uri'], [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]);

        Log::info('Firebase token request sent', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if (!$response->ok()) {
            Log::warning('Firebase token request failed', ['response' => $response->body()]);
            return null;
        }

        $accessToken = $response->json()['access_token'] ?? null;
        Log::info('Firebase access token obtained', ['access_token' => substr($accessToken, 0, 20) . '...']);

        return $accessToken;

    } catch (\Exception $e) {
        Log::error('Firebase getAccessToken exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        return null;
    }
}

    public static function sendToTopic(string $title, string $description, string $topic, array $data = []): bool
    {
        try {
            Log::info('Firebase sendToTopic called', [
                'title' => $title,
                'topic' => $topic,
                'data' => $data,
            ]);

            $firebaseConfig = config('services.firebase');

            Log::info('Firebase config', [
                'enabled' => $firebaseConfig['enabled'] ?? 'not set',
                'project_id' => $firebaseConfig['project_id'] ?? 'not set',
                'has_client_email' => !empty($firebaseConfig['client_email']),
                'has_private_key' => !empty($firebaseConfig['private_key']),
            ]);

          

            $projectId = $firebaseConfig['project_id'] ?? null;
            $clientEmail = $firebaseConfig['client_email'] ?? null;
            $privateKey = $firebaseConfig['private_key'] ?? null;

            if (empty($projectId) || empty($clientEmail) || empty($privateKey)) {
                Log::warning('Firebase config incomplete', [
                    'missing_project_id' => empty($projectId),
                    'missing_client_email' => empty($clientEmail),
                    'missing_private_key' => empty($privateKey),
                ]);
                return false;
            }

            $serviceAccountConfig = [
                'project_id' => $projectId,
                'client_email' => $clientEmail,
                'private_key' => $privateKey,
            ];

            Log::info('Requesting Firebase access token');
            $cacertPath = 'C:\Users\more2drive\Desktop\New folder\AddNotification\etc\ssl\certs\cacert.pem';
            $accessToken = self::getAccessToken($serviceAccountConfig, $cacertPath);

            if (! $accessToken) {
                Log::error('Failed to get Firebase access token');
                return false;
            }

            Log::info('Access token obtained successfully');

            $message = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $description,
                    ],
                    'data' => $data,
                ],
            ];

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
            Log::info('Sending Firebase notification', ['url' => $url]);

            $response = Http::withToken($accessToken)
                ->post($url, $message);

            Log::info('Firebase response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'success' => $response->ok(),
            ]);

            if (! $response->ok()) {
                Log::error('Firebase notification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->ok();
        } catch (\Exception $e) {
            Log::error('Firebase sendToTopic error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to all users
     */
    public static function sendToAllUsers(string $title, string $description, array $data = []): bool
    {
        return self::sendToUsers([], $title, $description, $data);
    }

    /**
     * Send notification to specific users by IDs
     */
    public static function sendToUserIds(array $userIds, string $title, string $description, array $data = []): bool
    {
        return self::sendToUsers(['user_ids' => $userIds], $title, $description, $data);
    }

    /**
     * Send notification to users with specific roles
     */
    public static function sendToRoles(array $roles, string $title, string $description, array $data = []): bool
    {
        return self::sendToUsers(['roles' => $roles], $title, $description, $data);
    }

    /**
     * Send notification to users with specific permissions
     */
    public static function sendToPermissions(array $permissions, string $title, string $description, array $data = []): bool
    {
        return self::sendToUsers(['permissions' => $permissions], $title, $description, $data);
    }

    /**
     * Send notification to admins only
     */
    public static function sendToAdmins(string $title, string $description, array $data = []): bool
    {
        return self::sendToUsers(['roles' => ['Admin']], $title, $description, $data);
    }

    /**
     * Send notification to anonymous users
     */
    public static function sendToAnonymousUsers(string $title, string $description, array $data = []): bool
    {
        try {
            Log::info('Firebase sendToAnonymousUsers called', [
                'title' => $title,
                'data' => $data,
            ]);

            $firebaseConfig = config('services.firebase');

            

            $projectId = $firebaseConfig['project_id'] ?? null;
            $clientEmail = $firebaseConfig['client_email'] ?? null;
            $privateKey = $firebaseConfig['private_key'] ?? null;

            if (empty($projectId) || empty($clientEmail) || empty($privateKey)) {
                Log::warning('Firebase config incomplete');
                return false;
            }

            $serviceAccountConfig = [
                'project_id' => $projectId,
                'client_email' => $clientEmail,
                'private_key' => $privateKey,
            ];

            $accessToken = self::getAccessToken($serviceAccountConfig);

            if (!$accessToken) {
                Log::error('Failed to get Firebase access token');
                return false;
            }

            $fcmTokens = \App\Models\FcmToken::anonymous()->active()->get();
            $tokens = $fcmTokens->pluck('token')->toArray();

            if (empty($tokens)) {
                Log::warning('No active anonymous FCM tokens found');
                return false;
            }

            Log::info('Found active anonymous FCM tokens', [
                'count' => count($tokens),
            ]);

            $successCount = 0;
            $failureCount = 0;

            foreach ($tokens as $token) {
                $stringData = [];
                foreach ($data as $key => $value) {
                    $stringData[$key] = is_string($value) ? $value : (string) $value;
                }
                $stringData['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';

                $message = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $description,
                        ],
                        'data' => $stringData,
                    ],
                ];

                $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

                $response = Http::withToken($accessToken)
                    ->post($url, $message);

                if ($response->ok()) {
                    $successCount++;
                } else {
                    $failureCount++;
                    Log::warning('Failed to send to anonymous token', [
                        'token' => substr($token, 0, 20) . '...',
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    
                    if ($response->status() === 404) {
                        $responseBody = json_decode($response->body(), true);
                        if (isset($responseBody['error']['details'][0]['errorCode']) && 
                            $responseBody['error']['details'][0]['errorCode'] === 'UNREGISTERED') {
                            \App\Models\FcmToken::where('token', $token)->delete();
                            Log::info('Deleted unregistered anonymous FCM token', [
                                'token' => substr($token, 0, 20) . '...',
                            ]);
                        }
                    }
                }
            }

            Log::info('Firebase sendToAnonymousUsers completed', [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_tokens' => count($tokens),
            ]);

            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error('Firebase sendToAnonymousUsers error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

 
    public static function sendToUsers(array $criteria, string $title, string $description, array $data = []): bool
    {
        try {
            Log::info('Firebase sendToUsers called', [
                'title' => $title,
                'criteria' => $criteria,
                'data' => $data,
            ]);

            $query = \App\Models\FcmToken::active()->with('user');

            if (!empty($criteria['user_ids'])) {
                $query->whereIn('user_id', $criteria['user_ids']);
                Log::info('Filtering by user IDs', ['user_ids' => $criteria['user_ids']]);
            }

            if (!empty($criteria['roles'])) {
                $query->whereHas('user.roles', function($q) use ($criteria) {
                    $q->whereIn('name', $criteria['roles']);
                });
                Log::info('Filtering by roles', ['roles' => $criteria['roles']]);
            }

            if (!empty($criteria['permissions'])) {
                // Try to filter by permissions, but if no users found, fallback to all active tokens
                $queryWithPermissions = clone $query;
                $queryWithPermissions->whereHas('user.roles.permissions', function($q) use ($criteria) {
                    $q->whereIn('name', $criteria['permissions']);
                });
                Log::info('Filtering by permissions', ['permissions' => $criteria['permissions']]);
                
                $fcmTokensWithPermissions = $queryWithPermissions->get();
                
                if ($fcmTokensWithPermissions->isEmpty()) {
                    Log::warning('No users found with permissions, falling back to all active FCM tokens', ['permissions' => $criteria['permissions']]);
                    $fcmTokens = $query->get();
                } else {
                    $fcmTokens = $fcmTokensWithPermissions;
                }
            } else {
                $fcmTokens = $query->get();
            }

            $tokens = $fcmTokens->pluck('token')->toArray();

            if (empty($tokens)) {
                Log::warning('No active FCM tokens found for criteria', ['criteria' => $criteria]);
                return false;
            }

            Log::info('Found active FCM tokens', [
                'count' => count($tokens),
                'users' => $fcmTokens->pluck('user.name')->toArray()
            ]);

            $firebaseConfig = config('services.firebase');

            

            $projectId = $firebaseConfig['project_id'] ?? null;
            $clientEmail = $firebaseConfig['client_email'] ?? null;
            $privateKey = $firebaseConfig['private_key'] ?? null;

            if (empty($projectId) || empty($clientEmail) || empty($privateKey)) {
                Log::warning('Firebase config incomplete');
                return false;
            }

            $serviceAccountConfig = [
                'project_id' => $projectId,
                'client_email' => $clientEmail,
                'private_key' => $privateKey,
            ];

            $accessToken = self::getAccessToken($serviceAccountConfig);

            if (!$accessToken) {
                Log::error('Failed to get Firebase access token');
                return false;
            }

            Log::info('Access token obtained successfully');

            $successCount = 0;
            $failureCount = 0;

            // Send to each token individually
            foreach ($tokens as $token) {
                // Convert all data values to strings (Firebase requirement)
                $stringData = [];
                foreach ($data as $key => $value) {
                    $stringData[$key] = is_string($value) ? $value : (string) $value;
                }
                $stringData['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';

                $message = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $description,
                        ],
                        'data' => $stringData,
                    ],
                ];

                $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

                $response = Http::withToken($accessToken)
                    ->post($url, $message);

                if ($response->ok()) {
                    $successCount++;
                } else {
                    $failureCount++;
                    Log::warning('Failed to send to token', [
                        'token' => substr($token, 0, 20) . '...',
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    
                    // Delete unregistered tokens from database
                    if ($response->status() === 404) {
                        $responseBody = json_decode($response->body(), true);
                        if (isset($responseBody['error']['details'][0]['errorCode']) && 
                            $responseBody['error']['details'][0]['errorCode'] === 'UNREGISTERED') {
                            \App\Models\FcmToken::where('token', $token)->delete();
                            Log::info('Deleted unregistered FCM token', [
                                'token' => substr($token, 0, 20) . '...',
                            ]);
                        }
                    }
                }
            }

            Log::info('Firebase sendToUsers completed', [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_tokens' => count($tokens),
                'criteria' => $criteria,
            ]);

            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error('Firebase sendToUsers error: ' . $e->getMessage(), [
                'criteria' => $criteria,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
public static function sendNotificationToHttp(array $data)
{
    Log::info('FCM: sendNotificationToHttp called', [
        'data' => $data,
    ]);
    try {
        $notificationSettingsJson = DB::table('business')->value('notification_settings');

        if (empty($notificationSettingsJson)) {
            Log::warning('FCM: notification_settings is empty or missing');
            return ['error' => 'notification_settings_missing'];
        }

        // Try to decode directly first

        $notificationSettings = json_decode($notificationSettingsJson, true);

if (is_string($notificationSettings)) {
    $notificationSettings = json_decode($notificationSettings, true);
}

        Log::info('FCM: notification_settings decoded', ['decoded' => $notificationSettings]);

        

        $key = [
            'project_id'   => $notificationSettings['firebase_project_id'] ?? null,
            'client_email' => $notificationSettings['firebase_client_email'] ?? null,
            'private_key'  => isset($notificationSettings['firebase_private_key']) ? str_replace("\\n", "\n", $notificationSettings['firebase_private_key']) : null,
            'token_uri'    => 'https://oauth2.googleapis.com/token',
        ];

        if (empty($key['project_id']) || empty($key['client_email']) || empty($key['private_key'])) {
            Log::warning('FCM: Incomplete firebase keys in notification_settings', ['keys' => $key]);
            return ['error' => 'firebase_keys_missing'];
        }


        $accessToken = self::getAccessToken($key, );

        if (! $accessToken) {
            Log::error('FCM: Failed to get access token');
            return ['error' => 'access_token_failed'];
        }

        Log::info('FCM: Access token generated successfully');

        $url = 'https://fcm.googleapis.com/v1/projects/' . $key['project_id'] . '/messages:send';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        $responseBody = null;
        try {
            $responseBody = $response->json();
        } catch (\Throwable $t) {
            $responseBody = ['raw_body' => $response->body()];
        }

        Log::info('FCM: Response received', [
            'status'  => $response->status(),
            'success' => $response->ok(),
            'body'    => $responseBody,
        ]);

        if (! $response->ok()) {
            Log::error('FCM: Notification failed', [
                'status' => $response->status(),
                'body'   => $responseBody,
            ]);
        }

        // Return the parsed FCM response so callers can inspect errors or success name
        return $responseBody;

    } catch (\Exception $exception) {
        Log::error('FCM: Exception while sending notification', [
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
        ]);

        return false;
    }
}

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
      public static function send_push_notif_to_device($fcm_token, $data, $isDeliverymanAssigned = false)
    {
        $postData = [
            'message' => [
                "token" => $fcm_token,
                "data" => [
                    "title" => (string)$data['title'],
                    "body" => (string)$data['description'],
                    "image" => (string)$data['image'],
                    "order_id" => (string)$data['order_id'],
                    "type" => (string)$data['type'],
                    "is_deliveryman_assigned" => $isDeliverymanAssigned ? "1" : "0",
                ],
                "notification" => [
                    'title' => (string)$data['title'],
                    'body' => (string)$data['description'],
                ],
            ]
        ];
        return self::sendNotificationToHttp($postData);
    }
public static function send_push_notif_to_devices(array $fcm_tokens, array $data, $isDeliverymanAssigned = false)
{
    if (empty($fcm_tokens)) {
        return false;
    }

    $successCount = 0;
    $failureCount = 0;

    foreach ($fcm_tokens as $token) {

        $postData = [
            'message' => [
                'token' => $token,
                'notification' => $data['notification'] ?? [],
                'data' => $data['data'] ?? [],
                'android' => [
                    'priority' => 'high'
                ],
                'apns' => [
                    'headers' => ['apns-priority' => '10']
                ]
            ]
        ];

        try {
            $response = self::sendNotificationToHttp($postData);

            if ($response && isset($response['error'])) {
                $failureCount++;

                if (isset($response['error']['errorCode']) && $response['error']['errorCode'] === 'UNREGISTERED') {
                    Log::warning('FCM: Removing unregistered token', ['token' => $token]);
                    FcmToken::where('token', $token)->delete();
                }

                Log::error('FCM: Failed to send notification', [
                    'token' => $token,
                    'error' => $response['error'],
                ]);

            } elseif ($response && isset($response['name'])) {
                $successCount++;
            }elseif (!$response) {
    $failureCount++;
    Log::error('FCM: No response from sendNotificationToHttp', [
        'token' => $token
    ]);
} else {
    $failureCount++;
    Log::warning('FCM: Unexpected response structure', [
        'token' => $token,
        'response' => $response
    ]);
}

        } catch (\Exception $e) {
            $failureCount++;
            Log::error('FCM: Exception while sending notification', [
                'token' => $token,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    Log::info('FCM: Push summary', [
        'total_tokens' => count($fcm_tokens),
        'success' => $successCount,
        'failure' => $failureCount,
    ]);

    return $successCount > 0;
}

}
