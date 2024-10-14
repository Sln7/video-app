<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\VideoController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/videos', [VideoController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('videos')->controller(VideoController::class)->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::patch('/{id}/like', 'toggleLike');
    });
});
