<?php

use Illuminate\Support\Facades\Route;
use Modules\Connector\Http\Controllers\Api\BookingController;
use Modules\Connector\Http\Controllers\Api\ContactLoginController;
use Modules\Connector\Http\Controllers\Api\dashboardController;
use Modules\Connector\Http\Controllers\Api\DataJobSheetController;
use Modules\Connector\Http\Controllers\Api\JobsheetExitController;
use Modules\Connector\Http\Controllers\Api\MessagesController;
use Modules\Connector\Http\Controllers\Api\RepairOrderImportController;

Route::get('/saveProduct', [BookingController::class, 'saveData']);


Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin')->prefix('connector')->group(function () {
    Route::get('install', [Modules\Connector\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [Modules\Connector\Http\Controllers\InstallController::class, 'install']);
    Route::get('install/uninstall', [Modules\Connector\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [Modules\Connector\Http\Controllers\InstallController::class, 'update']);
    Route::post('store/messages', [MessagesController::class, 'store'])->name('store.message');
    Route::get('dashboard/table', [dashboardController::class, 'table'])->name('table.dashboard');
});

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->prefix('connector')->group(function () {
    Route::get('/api', [Modules\Connector\Http\Controllers\ConnectorController::class, 'index']);
    Route::resource('/client', 'Modules\Connector\Http\Controllers\ClientController');
    Route::get('messages', [MessagesController::class, 'index'])->name('submit.message');

    Route::get('import/repair-orders', [RepairOrderImportController::class, 'showImportForm']);
    Route::post('import/repair-orders', [RepairOrderImportController::class, 'handleImportForm']);
});
Route::get('all/message', [MessagesController::class, 'show']);

// Route::get('dashboard/draw',[dashboardController::class, 'draw']);
// Route::get('jobsheet/data', [DataJobSheetController::class, 'data']);
// Route::get('workers/data', [DataJobSheetController::class, 'workers']);
// Route::put('notification', [DataJobSheetController::class, 'updateStatus']);


// Route::get('dashboard/counter',[dashboardController::class, 'data']);

// Route::get('response', [MessagesController::class, 'storeResponse']);

// Route::get('exit/permission', [JobsheetExitController::class, 'index']);
// Route::get('update/exit/permission/{id}', [JobsheetExitController::class, 'updateExitPermission']);

// Route::get('/register', [ContactLoginController::class, 'saveDataRegister']);
// Route::get('/login', [ContactLoginController::class, 'login']);
