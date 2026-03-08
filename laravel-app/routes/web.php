<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\FacturaController;

Route::get('/', [FacturaController::class, 'index'])->name('facturas.index');

Route::post('/facturas/{id}/enviar-whatsapp-manual', [NotificacionController::class, 'enviarWhatsAppManual'])
    ->name('facturas.enviar-whatsapp-manual');

Route::post('/facturas/{id}/enviar-correo-manual', [NotificacionController::class, 'enviarCorreoManual'])
    ->name('facturas.enviar-correo-manual');
