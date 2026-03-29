<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Support\Facades\Route;
use Modules\ArtificialIntelligence\Http\Controllers\AIProductController;
use Modules\ArtificialIntelligence\Http\Controllers\VINLookupController;
use Modules\ArtificialIntelligence\Http\Controllers\DiagnoseMessageController;
use Modules\ArtificialIntelligence\Http\Controllers\ImportBrandDataController;
use Modules\ArtificialIntelligence\Http\Controllers\ArtificialIntelligenceController;

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->prefix('artificialintelligence')->group(function() {
    // Route::get('/', 'ArtificialIntelligenceController@chatStudio');

    // AI Chat Studio routes
    Route::post('/send-chat-message', 'ArtificialIntelligenceController@sendChatMessage')->name('artificialintelligence.send-chat-message');
    Route::get('/diagnose-message/edit', [DiagnoseMessageController::class, 'edit'])->name('diagnose-message.edit');
    Route::post('/diagnose-message/update', [DiagnoseMessageController::class, 'update'])->name('diagnose-message.update');
    Route::get('/brandImport', [DiagnoseMessageController::class, 'BrandImport'])->name('brandImport');
    Route::post('/jobsheet_obds_ai', [DiagnoseMessageController::class, 'handle_jobsheet_obds_ai']);
    Route::get('brand-models', [Modules\ArtificialIntelligence\Http\Controllers\ImportBrandDataController::class, 'importBrandAndModels'])->name('ai.BrandAndModels');

    // Route::get('ai/models', 'DiagnoseMessageController@getModels')->name('ai.getModels');
    // Add this route if it doesn't exist
    Route::get('/artificialintelligence/get-models', 'ArtificialIntelligenceController@getModels')->name('artificialintelligence.get-models');
    Route::get('settings', 'ArtificialIntelligenceController@settings')->name('artificialintelligence.settings');

    // AI Providers CRUD routes
    Route::get('/providers', 'ArtificialIntelligenceController@providers')->name('artificialintelligence.providers');
    Route::get('/providers/create', 'ArtificialIntelligenceController@createProvider')->name('artificialintelligence.providers.create');
    Route::post('/providers', 'ArtificialIntelligenceController@storeProvider')->name('artificialintelligence.providers.store');
    Route::get('/providers/{id}', 'ArtificialIntelligenceController@showProvider')->name('artificialintelligence.providers.show');
    Route::get('/providers/{id}/edit', 'ArtificialIntelligenceController@editProvider')->name('artificialintelligence.providers.edit');
    Route::put('/providers/{id}', 'ArtificialIntelligenceController@updateProvider')->name('artificialintelligence.providers.update');
    Route::delete('/providers/{id}', 'ArtificialIntelligenceController@destroyProvider')->name('artificialintelligence.providers.destroy');

    // API processing route
    Route::post('/process', 'ArtificialIntelligenceController@process')->name('artificialintelligence.process');

    // Product routes
    Route::post('/product/process', [AIProductController::class, 'processProductRequest'])->name('product.process');
    Route::get('/get-product-details', [AIProductController::class, 'getProductDetails'])->name('get.product.details');
    Route::post('/product/process-excel', [AIProductController::class, 'processExcelImport'])->name('product.process.excel');

    Route::post('ai/lookup-chassis', [VINLookupController::class, 'lookupChassis'])->name('booking.lookup_chassis');

    // Route::post('/test-web-search', 'DataValidationController@testWebSearch')->name('ai.test.web-search');
    Route::post('settings/update-active', 'ArtificialIntelligenceController@updateActiveProvider')
        ->name('artificialintelligence.settings.update-active');
});
