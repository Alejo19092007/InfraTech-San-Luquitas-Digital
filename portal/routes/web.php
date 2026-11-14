<?php

use App\Http\Controllers\NovedadController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\RecursoController;
use App\Http\Middleware\PuedeEditarContenido;
use Illuminate\Support\Facades\Route;

// Módulos públicos
Route::get('/', [PaginaController::class, 'inicio'])->name('inicio');
Route::get('/institucional', [PaginaController::class, 'institucional'])->name('institucional');
Route::get('/recursos', [RecursoController::class, 'index'])->name('recursos.index');

// Novedades: escritura protegida (rol admin cuando exista el login)
Route::middleware(PuedeEditarContenido::class)->group(function () {
    Route::get('/novedades/crear', [NovedadController::class, 'create'])->name('novedades.create');
    Route::post('/novedades', [NovedadController::class, 'store'])->name('novedades.store');
    Route::get('/novedades/{novedad}/editar', [NovedadController::class, 'edit'])->name('novedades.edit');
    Route::put('/novedades/{novedad}', [NovedadController::class, 'update'])->name('novedades.update');
    Route::delete('/novedades/{novedad}', [NovedadController::class, 'destroy'])->name('novedades.destroy');
});

// Novedades: lectura pública
Route::get('/novedades', [NovedadController::class, 'index'])->name('novedades.index');
Route::get('/novedades/{novedad}', [NovedadController::class, 'show'])->name('novedades.show');

// Acceso directo al EVA
Route::redirect('/moodle', config('portal.moodle_url'))->name('moodle');
