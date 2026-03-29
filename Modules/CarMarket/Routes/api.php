<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| CarMarket module API routes (loaded by RouteServiceProvider).
| Main API routes are registered in Connector module's api.php.
*/

Route::middleware('auth:api')->get('/carmarket', function (Request $request) {
    return $request->user();
});
