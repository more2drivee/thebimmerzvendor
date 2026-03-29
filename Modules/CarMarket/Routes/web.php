<?php

use Illuminate\Support\Facades\Route;
use Modules\CarMarket\Http\Controllers\CarMarketController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Authenticated routes for the CarMarket admin panel.
*/

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])
    ->prefix('carmarket')
    ->group(function () {
        // Dashboard / Listings
        Route::get('/', [CarMarketController::class, 'index'])
            ->name('carmarket.index');

        // Create new vehicle
        Route::get('/vehicles/create', [CarMarketController::class, 'create'])
            ->name('carmarket.vehicles.create');
        Route::post('/vehicles', [CarMarketController::class, 'store'])
            ->name('carmarket.vehicles.store');

        // Edit vehicle
        Route::get('/vehicles/{id}/edit', [CarMarketController::class, 'edit'])
            ->name('carmarket.vehicles.edit');
        Route::put('/vehicles/{id}', [CarMarketController::class, 'update'])
            ->name('carmarket.vehicles.update');

        // Get models by brand (for cascading dropdown)
        Route::get('/brands/{brandId}/models', [CarMarketController::class, 'getModelsByBrand'])
            ->name('carmarket.brands.models');

        // Media management
        Route::delete('/vehicles/{vehicleId}/media/{mediaId}', [CarMarketController::class, 'deleteMedia'])
            ->name('carmarket.vehicles.media.delete');
        Route::post('/vehicles/{vehicleId}/media/{mediaId}/set-primary', [CarMarketController::class, 'setPrimaryImage'])
            ->name('carmarket.vehicles.media.set-primary');

        Route::get('/vehicles/datatables', [CarMarketController::class, 'getVehiclesDatatables'])
            ->name('carmarket.vehicles.datatables');

        Route::get('/vehicles/{id}', [CarMarketController::class, 'show'])
            ->name('carmarket.vehicles.show');

        Route::post('/vehicles/{id}/approve', [CarMarketController::class, 'approve'])
            ->name('carmarket.vehicles.approve');

        Route::post('/vehicles/{id}/reject', [CarMarketController::class, 'reject'])
            ->name('carmarket.vehicles.reject');

        Route::post('/vehicles/{id}/deactivate', [CarMarketController::class, 'deactivate'])
            ->name('carmarket.vehicles.deactivate');

        // Inquiries
        Route::get('/inquiries', [CarMarketController::class, 'inquiries'])
            ->name('carmarket.inquiries');

        Route::get('/inquiries/datatables', [CarMarketController::class, 'getInquiriesDatatables'])
            ->name('carmarket.inquiries.datatables');

        Route::put('/inquiries/{id}/status', [CarMarketController::class, 'updateInquiryStatus'])
            ->name('carmarket.inquiries.update-status');

        // Reports
        Route::get('/reports', [CarMarketController::class, 'reports'])
            ->name('carmarket.reports');

        Route::get('/reports/datatables', [CarMarketController::class, 'getReportsDatatables'])
            ->name('carmarket.reports.datatables');

        Route::put('/reports/{id}/status', [CarMarketController::class, 'updateReportStatus'])
            ->name('carmarket.reports.update-status');

        // Settings
        Route::get('/settings', [CarMarketController::class, 'settings'])
            ->name('carmarket.settings');
    });
