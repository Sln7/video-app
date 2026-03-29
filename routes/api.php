<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MediaController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/media', [MediaController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('media')->controller(MediaController::class)->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::patch('/{id}/favorite', 'toggleFavorite');
    });
});
