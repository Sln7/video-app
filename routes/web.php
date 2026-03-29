<?php

use Illuminate\Support\Facades\Route;

// Catch-all: serve a SPA React para qualquer rota web
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
