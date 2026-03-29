<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Str;
use App\Helpers\FirebaseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\BookingNotification;

class NotificationService
{
    /**
     * Send notification to all users
     */
    public static function sendToAllUsers(string $title, string $message, array $data = [], bool $createDatabaseNotification = true, ?int $excludeUserId = null): bool
    {
        return self::sendNotification([], $title, $message, $data, $createDatabaseNotification, $excludeUserId);
    }

    /**
     * Send notification to specific users by IDs
     */
    public static function sendToUserIds(array $userIds, string $title, string $message, array $data = [], bool $createDatabaseNotification = true, ?int $excludeUserId = null): bool
    {
        return self::sendNotification(['user_ids' => $userIds], $title, $message, $data, $createDatabaseNotification, $excludeUserId);
    }

    /**
     * Send notification to users with specific roles
     */
    public static function sendToRoles(array $roles, string $title, string $message, array $data = [], bool $createDatabaseNotification = true, ?int $excludeUserId = null): bool
    {
        return self::sendNotification(['roles' => $roles], $title, $message, $data, $createDatabaseNotification, $excludeUserId);
    }

    /**
     * Send notification to users with specific permissions
     */
    public static function sendToPermissions(array $permissions, string $title, string $message, array $data = [], bool $createDatabaseNotification = true, ?int $excludeUserId = null): bool
    {
        return self::sendNotification(['permissions' => $permissions], $title, $message, $data, $createDatabaseNotification, $excludeUserId);
    }

    /**
     * Send notification to admins only
     */
    public static function sendToAdmins(string $title, string $message, array $data = [], bool $createDatabaseNotification = true, ?int $excludeUserId = null): bool
    {
        return self::sendNotification(['roles' => ['Admin']], $title, $message, $data, $createDatabaseNotification, $excludeUserId);
    }

    /**
     * Send notification to users with booking permissions
     */
    public static function sendToBookingUsers(string $title, string $message, array $data = [], bool $createDatabaseNotification = true, ?int $excludeUserId = null): bool
    {
        return self::sendNotification(['permissions' => ['crud_all_bookings', 'crud_own_bookings']], $title, $message, $data, $createDatabaseNotification, $excludeUserId);
    }

    /**
     * Main notification method that handles both Firebase and database notifications
     */
    private static function sendNotification(array $criteria, string $title, string $message, array $data = [], bool $createDatabaseNotification = true, ?int $excludeUserId = null): bool
    {
        try {
            Log::info('NotificationService: Sending notification', [
                'title' => $title,
                'criteria' => $criteria,
                'create_db_notification' => $createDatabaseNotification,
            ]);

            // Get target users based on criteria
            $users = self::getUsersByCriteria($criteria, $excludeUserId);

            if ($users->isEmpty()) {
                Log::warning('No users found for notification criteria', ['criteria' => $criteria]);
                return false;
            }

            Log::info('Found target users for notification', [
                'count' => $users->count(),
                'users' => $users->pluck('name')->toArray(),
            ]);

            $firebaseSuccess = false;
            $databaseSuccess = false;

            // Send Firebase push notification
            if (!empty($criteria)) {
                // Use specific criteria for Firebase
                if (!empty($criteria['user_ids'])) {
                    $firebaseSuccess = FirebaseHelper::sendToUserIds($criteria['user_ids'], $title, $message, $data);
                } elseif (!empty($criteria['roles'])) {
                    $firebaseSuccess = FirebaseHelper::sendToRoles($criteria['roles'], $title, $message, $data);
                } elseif (!empty($criteria['permissions'])) {
                    $firebaseSuccess = FirebaseHelper::sendToPermissions($criteria['permissions'], $title, $message, $data);
                }
            } else {
                // Send to all users
                $firebaseSuccess = FirebaseHelper::sendToAllUsers($title, $message, $data);
            }

            // Create database notifications for navbar updates
            if ($createDatabaseNotification) {
                foreach ($users as $user) {
                    try {
                        $user->notify(new BookingNotification([
                            'title' => $title,
                            'message' => $message,
                            'booking_id' => $data['booking_id'] ?? null,
                            'contact_id' => $data['contact_id'] ?? null,
                            'location_id' => $data['location_id'] ?? null,
                            'data' => $data,
                        ]));
                        $databaseSuccess = true;
                    } catch (\Exception $e) {
                        Log::error('Failed to create database notification for user', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('NotificationService: Notification sending completed', [
                'firebase_success' => $firebaseSuccess,
                'database_success' => $databaseSuccess,
                'target_users_count' => $users->count(),
            ]);

            return $firebaseSuccess || $databaseSuccess;

        } catch (\Exception $e) {
            Log::error('NotificationService: Error sending notification', [
                'error' => $e->getMessage(),
                'criteria' => $criteria,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Get users based on criteria
     */
    private static function getUsersByCriteria(array $criteria, ?int $excludeUserId = null)
    {
        $query = User::query();

        if (!empty($criteria['user_ids'])) {
            $query->whereIn('id', $criteria['user_ids']);
        }

        if (!empty($criteria['roles'])) {
            $query->whereHas('roles', function($q) use ($criteria) {
                $q->whereIn('name', $criteria['roles']);
            });
        }

        if (!empty($criteria['permissions'])) {
            $query->whereHas('roles.permissions', function($q) use ($criteria) {
                $q->whereIn('name', $criteria['permissions']);
            });
        }

        // If no criteria specified, get all users
        if (empty($criteria)) {
            // You might want to add business_id filter here
            // $query->where('business_id', session('user.business_id'));
        }

        // Exclude the creator user if specified
        if ($excludeUserId !== null) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->get();
    }

    /**
     * Quick methods for common notification scenarios
     */
    public static function notifyBookingCreated(string $customerName, string $locationName, string $bookingTime, array $bookingData = [], ?int $excludeUserId = null): bool
    {
        $title = 'New Booking';
        $message = "New booking for {$customerName} at {$locationName} on {$bookingTime}";
        
        return self::sendToBookingUsers($title, $message, array_merge($bookingData, [
            'type' => 'booking_created',
            'url' => '/restaurant/booking',
        ]), true, $excludeUserId);
    }

    public static function notifyBookingUpdated(string $customerName, string $status, array $bookingData = [], ?int $excludeUserId = null): bool
    {
        $title = 'Booking Updated';
        $message = "Booking for {$customerName} has been {$status}";
        
        return self::sendToBookingUsers($title, $message, array_merge($bookingData, [
            'type' => 'booking_updated',
            'url' => '/restaurant/booking',
        ]), true, $excludeUserId);
    }

    public static function notifyAdmins(string $title, string $message, array $data = [], ?int $excludeUserId = null): bool
    {
        return self::sendToAdmins($title, $message, $data, true, $excludeUserId);
    }

    public static function notifySpecificUsers(array $userIds, string $title, string $message, array $data = [], ?int $excludeUserId = null): bool
    {
        return self::sendToUserIds($userIds, $title, $message, $data, true, $excludeUserId);
    }
     public function storeDatabaseNotification(
        int $userId,
        string $type,
        array $data,
        string $status = 'pending'
    ): void {
        DB::table('notifications')->insert([
            'id'                  => (string) Str::uuid(),
            'user_id'             => $userId,
            'type'                => $type,
            'status'              => 0,
            'data'                => json_encode($data, JSON_UNESCAPED_UNICODE),
            'notification_status' => $status,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }
    public function storeBulkDatabaseNotification(
    array $userIds,
    string $type,
    array $data,
    string $status = 'pending'
): void {

    $now = now();

    $rows = collect($userIds)->map(function ($userId) use ($type, $data, $status, $now) {
        return [
            'id'                  => (string) \Str::uuid(),
            'user_id'             => $userId,
            'type'                => $type,
            'status'              => 0,
            'data'                => json_encode($data, JSON_UNESCAPED_UNICODE),
            'notification_status' => $status,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
    })->toArray();
    Log::info('Inserting bulk notifications into database', [
        'user_ids' => $userIds,
        'type' => $type,
        'data' => $data,
        'status' => $status,
        'rows_count' => count($rows),
    ]); 
    DB::table('notifications')->insert($rows);
}
}
