<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use Modules\ArtificialIntelligence\Http\Controllers\DataValidationController;
use Modules\ArtificialIntelligence\Http\Controllers\VINLookupController;
use Modules\ArtificialIntelligence\Http\Controllers\DiagnoseMessageController;

Route::middleware(['auth:api'])->prefix('ai')->group(function() {
    // Route::post('/test-web-search', 'DataValidationController@testWebSearchApi');
});

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

Route::middleware('auth:api')->get('/artificialintelligence', function (Request $request) {
    return $request->user();
});

Route::prefix('ai')->group(function () {
    // Public VIN lookup endpoint - no authentication required
    // Route::post('lookup-chassis', [VINLookupController::class, 'lookupChassis']);
    // Public Brand and Models import endpoint - no authentication required
    // Route::get('brand-models', [Modules\ArtificialIntelligence\Http\Controllers\ImportBrandDataController::class, 'importBrandAndModels'])->name('ai.BrandAndModels');
});