<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{PublicController,AuthController,ResourceController};
Route::get('/',[\App\Http\Controllers\MonitoringController::class,'index'])->name('home');
Route::get('/api/seguimiento',[\App\Http\Controllers\MonitoringController::class,'data'])->middleware('throttle:30,1');
Route::get('/practica',[PublicController::class,'index']);
Route::get('/alertas/{alert}',[PublicController::class,'alert']);
Route::view('/privacidad','privacy');
Route::middleware('guest')->group(function(){
 Route::get('/login',[AuthController::class,'form'])->name('login');
 Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1');
 Route::get('/registro',[AuthController::class,'registration']);
 Route::post('/registro',[AuthController::class,'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[AuthController::class,'logout']);
 Route::get('/cuenta',[AuthController::class,'account']);
 Route::put('/cuenta',[AuthController::class,'preference']);
 Route::post('/avisos/{id}/leido',[AuthController::class,'read']);
 Route::middleware('staff')->prefix('gestion')->group(function(){
  Route::get('/{kind}',[ResourceController::class,'index']);
  Route::get('/{kind}/crear',[ResourceController::class,'create']);
  Route::post('/{kind}',[ResourceController::class,'store']);
  Route::get('/{kind}/{id}/editar',[ResourceController::class,'edit']);
  Route::put('/{kind}/{id}',[ResourceController::class,'update']);
  Route::delete('/{kind}/{id}',[ResourceController::class,'destroy']);
 });
});
