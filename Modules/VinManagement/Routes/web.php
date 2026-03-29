<?php

use Illuminate\Support\Facades\Route;
use Modules\VinManagement\Http\Controllers\VinManagementController;

Route::middleware('web', 'authh', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')
    ->prefix('vin')
    ->group(function () {
        Route::get('dashboard', [VinManagementController::class, 'dashboard'])->name('vin.dashboard');
        Route::get('import', [VinManagementController::class, 'import'])->name('vin.import');
        Route::post('import/upload', [VinManagementController::class, 'uploadImport'])->name('vin.import.upload');
        Route::post('import/submit', [VinManagementController::class, 'submitImport'])->name('vin.import.submit');
        Route::post('store', [VinManagementController::class, 'storeSingleVin'])->name('vin.store_single');
        
        // Dropdown data for manual entry
        Route::get('brands', [VinManagementController::class, 'brands'])->name('vin.brands');
        Route::get('models-by-brand/{brand}', [VinManagementController::class, 'modelsByBrand'])->name('vin.models_by_brand');

        // Server-side data and export
        Route::get('list', [VinManagementController::class, 'list'])->name('vin.list');
        Route::get('export', [VinManagementController::class, 'export'])->name('vin.export');
        Route::get('template', [VinManagementController::class, 'template'])->name('vin.template');
        Route::delete('{vin}', [VinManagementController::class, 'destroy'])->name('vin.destroy');

        // Autocomplete suggestions
        Route::get('manufacturers/suggestions', [VinManagementController::class, 'manufacturerSuggestions'])->name('vin.manufacturers.suggestions');
        Route::get('groups', [VinManagementController::class, 'groups'])->name('vin.groups');
        // Groups CRUD and assignment
        Route::get('groups/list', [VinManagementController::class, 'groupList'])->name('vin.groups.list');
        Route::post('groups', [VinManagementController::class, 'groupStore'])->name('vin.groups.store');
        Route::put('groups/{id}', [VinManagementController::class, 'groupUpdate'])->name('vin.groups.update');
        Route::delete('groups/{id}', [VinManagementController::class, 'groupDelete'])->name('vin.groups.delete');
        Route::get('groups/{id}/vins', [VinManagementController::class, 'groupVins'])->name('vin.groups.vins');
        Route::post('groups/assign', [VinManagementController::class, 'assignVinToGroup'])->name('vin.groups.assign');
        Route::post('groups/unassign', [VinManagementController::class, 'unassignVinFromGroup'])->name('vin.groups.unassign');
        // Lookup groups by VIN string
        Route::get('vin-groups-by-number', [VinManagementController::class, 'vinGroupsByNumber'])->name('vin.groups.by_number');
        Route::get('campaigns', [VinManagementController::class, 'campaigns'])->name('vin.campaigns');
        Route::get('automation', [VinManagementController::class, 'automation'])->name('vin.automation');

        // Default landing to dashboard
        Route::get('/', [VinManagementController::class, 'dashboard'])->name('vin.index');
    });