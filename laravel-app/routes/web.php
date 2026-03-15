<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ImportarFacturasController;
use App\Http\Controllers\ReporteController;

Route::redirect('/', '/dashboard');

// ── DASHBOARD ─────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ── FACTURAS ──────────────────────────────────────────────────────────────
Route::get('/facturas', [FacturaController::class, 'index'])->name('facturas.index');
Route::get('/facturas/{id}/edit', [FacturaController::class, 'edit'])->name('facturas.edit');
Route::put('/facturas/{id}', [FacturaController::class, 'update'])->name('facturas.update');
Route::post('/facturas/{id}/upload-comprobante', [FacturaController::class, 'uploadComprobante'])->name('facturas.upload-comprobante');

Route::post('/facturas/{id}/enviar-whatsapp-manual',
    [NotificacionController::class, 'enviarWhatsAppManual']
)->name('facturas.enviar-whatsapp-manual');

Route::post('/facturas/{id}/enviar-correo-manual',
    [NotificacionController::class, 'enviarCorreoManual']
)->name('facturas.enviar-correo-manual');

Route::post('/facturas/{id}/enviar-factura-pagada-whatsapp',
    [NotificacionController::class, 'enviarFacturaPagadaWhatsApp']
)->name('facturas.enviar-factura-pagada-whatsapp');

Route::post('/facturas/{id}/enviar-factura-pagada-correo',
    [NotificacionController::class, 'enviarFacturaPagadaCorreo']
)->name('facturas.enviar-factura-pagada-correo');

// ── IMPORTACIÓN ───────────────────────────────────────────────────────────
Route::get('/facturas/importar',
    [ImportarFacturasController::class, 'index']
)->name('facturas.importar');

Route::post('/facturas/importar/procesar',
    [ImportarFacturasController::class, 'importar']
)->name('facturas.importar.procesar');

// ── CLIENTES ──────────────────────────────────────────────────────────────
Route::get('/clientes',         [ClienteController::class, 'index']  )->name('clientes.index');
Route::post('/clientes',        [ClienteController::class, 'store']  )->name('clientes.store');
Route::put('/clientes/{id}',    [ClienteController::class, 'update'] )->name('clientes.update');
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

// ── REPORTES ──────────────────────────────────────────────────────────────
Route::get('/reportes',         [ReporteController::class, 'index'])->name('reportes.index');
Route::get('/reportes/json',    [ReporteController::class, 'json'] )->name('reportes.json');
Route::get('/reportes/pdf',     [ReporteController::class, 'pdf']  )->name('reportes.pdf');
Route::post('/reportes/enviar-whatsapp', [ReporteController::class, 'enviarReporteWhatsApp'])->name('reportes.enviar-whatsapp');
Route::post('/reportes/enviar-correo',   [ReporteController::class, 'enviarReporteCorreo']  )->name('reportes.enviar-correo');
