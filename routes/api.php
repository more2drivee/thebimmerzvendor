<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseTestController;
use App\Http\Controllers\Api\NotificationTestController;
use App\Http\Controllers\Api\SupplierLoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Supplier Authentication Routes
Route::prefix('supplier')->group(function () {
    Route::post('/login', [SupplierLoginController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [SupplierLoginController::class, 'logout']);
        Route::get('/profile', [SupplierLoginController::class, 'profile']);
    });
});

// Firebase test routes - no auth required for testing
Route::get('/firebase/config', [FirebaseTestController::class, 'getFirebaseConfig']);
Route::post('/firebase/test', [FirebaseTestController::class, 'testNotification']);
Route::get('/firebase/debug-tokens', [FirebaseTestController::class, 'debugTokens']);

// Notification test routes - no auth required for testing
Route::prefix('notifications/test')->group(function () {
    Route::post('/all-users', [NotificationTestController::class, 'testAllUsers']);
    Route::post('/admins', [NotificationTestController::class, 'testAdmins']);
    Route::post('/booking-users', [NotificationTestController::class, 'testBookingUsers']);
    Route::post('/specific-users', [NotificationTestController::class, 'testSpecificUsers']);
    Route::post('/roles', [NotificationTestController::class, 'testRoles']);
    Route::post('/permissions', [NotificationTestController::class, 'testPermissions']);
    Route::get('/options', [NotificationTestController::class, 'getAvailableOptions']);
});
