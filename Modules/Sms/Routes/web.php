<?php

use Illuminate\Support\Facades\Route;
use Modules\Sms\Http\Controllers\SmsMessageController;
use Modules\Sms\Http\Controllers\SmsSettingsController;

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])

    ->prefix('sms/messages')
    ->group(function () {

        Route::get('/', [SmsMessageController::class, 'index'])
            ->name('sms.messages.index');

        Route::get('/data', [SmsMessageController::class, 'getData'])
            ->name('sms.messages.data');

        Route::get('/create', [SmsMessageController::class, 'create'])
            ->name('sms.messages.create');

        Route::post('/', [SmsMessageController::class, 'store'])
            ->name('sms.messages.store');

        // Dashboard showing messages and SMS logs
        Route::get('/dashboard', [SmsMessageController::class, 'dashboard'])
            ->name('sms.messages.dashboard');

        // Data for SMS logs DataTable
        Route::get('/logs-data', [SmsMessageController::class, 'logsData'])
            ->name('sms.messages.logs-data');

        // Standalone SMS Settings page
        Route::get('/settings', [SmsSettingsController::class, 'index'])
            ->name('sms.messages.settings');

        // Save standalone SMS Settings
        Route::post('/settings', [SmsSettingsController::class, 'update'])
            ->name('sms.messages.settings.update');

        Route::get('/{id}', [SmsMessageController::class, 'show'])
            ->name('sms.messages.show');

        Route::get('/{id}/edit', [SmsMessageController::class, 'edit'])
            ->name('sms.messages.edit');

        Route::put('/{id}', [SmsMessageController::class, 'update'])
            ->name('sms.messages.update');

        Route::delete('/{id}', [SmsMessageController::class, 'destroy'])
            ->name('sms.messages.destroy');

        Route::post('/{id}/assign-roles', [SmsMessageController::class, 'assignRoles'])
            ->name('sms.messages.assign-roles');
    });
