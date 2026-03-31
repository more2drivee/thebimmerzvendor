<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Vendors\Http\Controllers\VendorController;
use Modules\Vendors\Http\Controllers\ProductController;

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

Route::middleware('auth:api')->get('/sms', function (Request $request) {
    return $request->user();
});

Route::prefix('vendors')->group(function () {
    
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        
        Route::get('/search', [ProductController::class, 'search']);
        
        Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']);
        
        Route::get('/brand/{brandId}', [ProductController::class, 'byBrand']);
        
        Route::get('/business/{businessId}', [ProductController::class, 'byBusiness']);
        
        Route::get('/active', [ProductController::class, 'active']);
        Route::get('/inactive', [ProductController::class, 'inactive']);
        Route::get('/for-sale', [ProductController::class, 'forSale']);
    });

    Route::prefix('product-by-vendor')->group(function () {
        Route::get('/', [VendorController::class, 'GetAllproducts']);
        Route::get('/warranties', [VendorController::class, 'getAllWarranties']);
        Route::post('/store', [VendorController::class, 'store']);
        Route::post('/storeNewProduct', [VendorController::class, 'storeNewProduct']);
        // Specific routes must come BEFORE /{id} route
        Route::get('/get_car_models_by_brand', [VendorController::class, 'getCarModelsByBrand']);
        Route::get('/get_brands', [VendorController::class, 'getBrands']);
        Route::get('/get_years', [VendorController::class, 'getYears']);
        Route::get('/get_countries', [VendorController::class, 'getCountries']);
        Route::get('/get_categories', [VendorController::class, 'getCategories']);
        Route::get('/get_units', [VendorController::class, 'getUnits']);
        Route::get('/by-vendor/{vendorId}', [VendorController::class, 'GetAllVendorProductsById']);
        Route::get('/compatibility/{id}', [VendorController::class, 'getProductCompatibility']);
        // Generic routes last
        Route::get('/{id}', [VendorController::class, 'show']);
        Route::put('/{id}', [VendorController::class, 'update']);
        Route::delete('/{id}', [VendorController::class, 'destroy']);
    });
    
});