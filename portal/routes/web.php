<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NovedadController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\RecursoController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VinculacionController;
use App\Http\Controllers\WebhookWhatsAppController;
use App\Http\Middleware\PuedeEditarContenido;
use Illuminate\Support\Facades\Route;

// Módulos públicos
Route::get('/', [PaginaController::class, 'inicio'])->name('inicio');
Route::get('/institucional', [PaginaController::class, 'institucional'])->name('institucional');
Route::get('/recursos', [RecursoController::class, 'index'])->name('recursos.index');

// Novedades: escritura protegida (rol admin; con PORTAL_EDICION_ABIERTA=true queda abierta para demostraciones)
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

// Acceso con usuario y contraseña (máximo 6 intentos por minuto)
Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.enviar');
});
Route::post('/salir', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Zona privada: tareas, vinculación de WhatsApp y gestión de usuarios
Route::middleware('auth')->group(function () {
    Route::get('/tareas', [TareaController::class, 'index'])->name('tareas.index');
    Route::get('/tareas/nueva', [TareaController::class, 'create'])->name('tareas.create');
    Route::post('/tareas', [TareaController::class, 'store'])->name('tareas.store');
    Route::patch('/tareas/{tarea}/entregar', [TareaController::class, 'entregar'])->name('tareas.entregar');

    Route::get('/vincular-whatsapp', [VinculacionController::class, 'mostrar'])->name('vincular');
    Route::post('/vincular-whatsapp', [VinculacionController::class, 'generar'])->middleware('throttle:10,1')->name('vincular.generar');

    Route::middleware('rol:admin')->prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::get('/nuevo', [UsuarioController::class, 'create'])->name('create');
        Route::post('/', [UsuarioController::class, 'store'])->name('store');
        Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
        Route::delete('/{usuario}', [UsuarioController::class, 'destroy'])->name('destroy');
    });
});

// Bot de WhatsApp: Meta llama a esta ruta (verificación GET, eventos POST). Sin sesión ni CSRF: se protege con la firma.
Route::get('/webhook/whatsapp', [WebhookWhatsAppController::class, 'verificar'])->name('webhook.verificar');
Route::post('/webhook/whatsapp', [WebhookWhatsAppController::class, 'recibir'])->middleware('throttle:240,1')->name('webhook.recibir');
