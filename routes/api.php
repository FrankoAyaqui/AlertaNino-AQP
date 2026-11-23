<?php

use App\Http\Controllers\Api\EstadoController;
use Illuminate\Support\Facades\Route;

// API pública de solo lectura: estado oficial por distrito (usada por el mapa).
Route::get('/estado', EstadoController::class)->middleware('throttle:api')->name('api.estado');
