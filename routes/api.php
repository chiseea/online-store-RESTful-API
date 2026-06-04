<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API Working'
    ]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('products', ProductController::class);

    Route::apiResource('users', UserController::class);

    Route::apiResource('transactions', TransactionController::class);

});
