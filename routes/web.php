<?php

use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\AvisoPublicoController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\Panel\AvisoController;
use App\Http\Controllers\Panel\ContactoController;
use App\Http\Controllers\Panel\DistritoController;
use App\Http\Controllers\Panel\GuiaController;
use App\Http\Controllers\Panel\PanelController;
use App\Http\Controllers\Panel\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
| Rutas públicas
*/
Route::get('/', InicioController::class)->name('inicio');
Route::get('/avisos', [AvisoPublicoController::class, 'index'])->name('avisos.index');
Route::get('/avisos/{aviso}', [AvisoPublicoController::class, 'show'])->whereNumber('aviso')->name('avisos.show');
Route::get('/guias', [PaginaController::class, 'guias'])->name('guias');
Route::get('/contactos', [PaginaController::class, 'contactos'])->name('contactos');
Route::get('/fuentes', [PaginaController::class, 'fuentes'])->name('fuentes');
Route::get('/privacidad', [PaginaController::class, 'privacidad'])->name('privacidad');

/*
| Autenticación (solo invitados). Límite de 5 intentos por minuto.
*/
Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [SesionController::class, 'create'])->name('ingresar');
    Route::post('/ingresar', [SesionController::class, 'store'])->middleware('throttle:acceso');
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:acceso');
});

/*
| Cualquier usuario con sesión (ciudadano, operador o administrador)
*/
Route::middleware('auth')->group(function () {
    Route::post('/salir', [SesionController::class, 'destroy'])->name('salir');
    Route::get('/cuenta', [CuentaController::class, 'edit'])->name('cuenta.editar');
    Route::put('/cuenta', [CuentaController::class, 'update'])->name('cuenta.actualizar');
    Route::get('/bandeja', [CuentaController::class, 'bandeja'])->name('bandeja');
    Route::post('/bandeja/leidas', [CuentaController::class, 'marcarTodas'])->name('bandeja.todas');
    Route::post('/bandeja/{id}/leida', [CuentaController::class, 'marcarLeida'])->whereUuid('id')->name('bandeja.leida');
});

/*
| Panel de gestión: operador y administrador
*/
Route::middleware(['auth', 'can:gestionar-contenido'])->prefix('panel')->name('panel.')->group(function () {
    Route::get('/', [PanelController::class, 'index'])->name('inicio');
    Route::post('/sincronizar', [PanelController::class, 'sincronizar'])->middleware('throttle:3,1')->name('sincronizar');
    Route::resource('avisos', AvisoController::class);
    Route::resource('guias', GuiaController::class);
    Route::resource('contactos', ContactoController::class);

    // Solo administrador
    Route::middleware('can:administrar')->group(function () {
        Route::resource('distritos', DistritoController::class);
        Route::resource('usuarios', UsuarioController::class);
        Route::get('/seguridad', [PanelController::class, 'seguridad'])->name('seguridad');
    });
});
