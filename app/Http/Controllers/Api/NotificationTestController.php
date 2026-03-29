<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationTestController extends Controller
{
    /**
     * Test notification to all users
     */
    public function testAllUsers(Request $request)
    {
        try {
            $result = NotificationService::sendToAllUsers(
                'Test: All Users',
                'This notification was sent to all users in the system.',
                ['type' => 'test_all_users', 'url' => '/restaurant/booking']
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification sent to all users' : 'Failed to send notification',
            ]);
        } catch (\Exception $e) {
            Log::error('Test all users notification error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test notification to admins only
     */
    public function testAdmins(Request $request)
    {
        try {
            $result = NotificationService::sendToAdmins(
                'Test: Admin Only',
                'This notification was sent to administrators only.',
                ['type' => 'test_admins', 'url' => '/restaurant/booking']
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification sent to admins' : 'Failed to send notification',
            ]);
        } catch (\Exception $e) {
            Log::error('Test admin notification error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test notification to users with booking permissions
     */
    public function testBookingUsers(Request $request)
    {
        try {
            $result = NotificationService::sendToBookingUsers(
                'Test: Booking Users',
                'This notification was sent to users with booking permissions.',
                ['type' => 'test_booking_users', 'url' => '/restaurant/booking']
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification sent to booking users' : 'Failed to send notification',
            ]);
        } catch (\Exception $e) {
            Log::error('Test booking users notification error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test notification to specific user IDs
     */
    public function testSpecificUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $result = NotificationService::sendToUserIds(
                $request->user_ids,
                'Test: Specific Users',
                'This notification was sent to specific users: ' . implode(', ', $request->user_ids),
                ['type' => 'test_specific_users', 'url' => '/restaurant/booking']
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification sent to specific users' : 'Failed to send notification',
                'target_users' => $request->user_ids,
            ]);
        } catch (\Exception $e) {
            Log::error('Test specific users notification error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test notification to users with specific roles
     */
    public function testRoles(Request $request)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string',
        ]);

        try {
            $result = NotificationService::sendToRoles(
                $request->roles,
                'Test: Role-based',
                'This notification was sent to users with roles: ' . implode(', ', $request->roles),
                ['type' => 'test_roles', 'url' => '/restaurant/booking']
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification sent to users with specified roles' : 'Failed to send notification',
                'target_roles' => $request->roles,
            ]);
        } catch (\Exception $e) {
            Log::error('Test roles notification error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Test notification to users with specific permissions
     */
    public function testPermissions(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        try {
            $result = NotificationService::sendToPermissions(
                $request->permissions,
                'Test: Permission-based',
                'This notification was sent to users with permissions: ' . implode(', ', $request->permissions),
                ['type' => 'test_permissions', 'url' => '/restaurant/booking']
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notification sent to users with specified permissions' : 'Failed to send notification',
                'target_permissions' => $request->permissions,
            ]);
        } catch (\Exception $e) {
            Log::error('Test permissions notification error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get available roles and permissions for testing
     */
    public function getAvailableOptions()
    {
        try {
            $roles = \Spatie\Permission\Models\Role::all()->pluck('name');
            $permissions = \Spatie\Permission\Models\Permission::all()->pluck('name');

            return response()->json([
                'roles' => $roles,
                'permissions' => $permissions,
            ]);
        } catch (\Exception $e) {
            Log::error('Get available options error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}