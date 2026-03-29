<?php

use Illuminate\Http\Request;
use Modules\CheckCar\Http\Controllers\PrivacyPolicyController;

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

Route::middleware('auth:api')->get('/checkcar', function (Request $request) {
    return $request->user();
});

Route::get('checkcar/privacy-policy', [PrivacyPolicyController::class, 'show'])
    ->name('checkcar.api.privacy_policy');