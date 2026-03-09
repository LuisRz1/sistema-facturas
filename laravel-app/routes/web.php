<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\ClienteController;

// Redirigir raíz a facturas
Route::redirect('/', '/facturas');

// ── FACTURAS ──────────────────────────────────────────────────────────
Route::get('/facturas', [FacturaController::class, 'index'])
    ->name('facturas.index');

Route::post('/facturas/{id}/enviar-whatsapp-manual', [NotificacionController::class, 'enviarWhatsAppManual'])
    ->name('facturas.enviar-whatsapp-manual');

Route::post('/facturas/{id}/enviar-correo-manual', [NotificacionController::class, 'enviarCorreoManual'])
    ->name('facturas.enviar-correo-manual');

// ── CLIENTES ──────────────────────────────────────────────────────────
Route::get('/clientes', [ClienteController::class, 'index'])
    ->name('clientes.index');

Route::post('/clientes', [ClienteController::class, 'store'])
    ->name('clientes.store');

Route::put('/clientes/{id}', [ClienteController::class, 'update'])
    ->name('clientes.update');

Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])
    ->name('clientes.destroy');
