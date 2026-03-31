<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MediaController;
use App\Http\Controllers\API\PlaylistController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/consumer/register', [AuthController::class, 'registerConsumer']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/consumer/login', [AuthController::class, 'loginConsumer']);

Route::get('/media', [MediaController::class, 'index']);
Route::get('/media/{id}', [MediaController::class, 'show']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('media')->controller(MediaController::class)->group(function () {
        Route::post('/', 'store')->middleware('can.upload.media');
        Route::patch('/{id}/favorite', 'toggleFavorite');
    });

    Route::prefix('playlists')->controller(PlaylistController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{publicId}', 'show');
        Route::patch('/{publicId}', 'update');
        Route::delete('/{publicId}', 'destroy');
        Route::post('/{publicId}/media/{mediaPublicId}', 'attachMedia');
        Route::delete('/{publicId}/media/{mediaPublicId}', 'detachMedia');
    });
});
