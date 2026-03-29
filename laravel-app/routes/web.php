<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ImportarFacturasController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ValidarDetraccionesController;
use App\Http\Controllers\ImportarRetencionesController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CatalogosController;
use App\Http\Controllers\CotizacionExportController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ImportarClientesController;


// ── AUTENTICACIÓN ─────────────────────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']    )->name('login.post');
Route::post('/logout',[AuthController::class, 'logout']   )->name('logout');

// ── RUTAS PROTEGIDAS ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::redirect('/', '/dashboard');

    // ── DASHBOARD ──────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── VALIDAR DETRACCIONES (SUNAT) ───────────────────────────────
    Route::get('/facturas/validar-detracciones',
        [ValidarDetraccionesController::class, 'index']
    )->name('detracciones.index');

    Route::post('/facturas/validar-detracciones/procesar',
        [ValidarDetraccionesController::class, 'procesar']
    )->name('detracciones.procesar');

    Route::post('/facturas/importar-retenciones/procesar',
        [ImportarRetencionesController::class, 'importar']
    )->name('facturas.importar.retenciones.procesar');

    // ── FACTURAS ───────────────────────────────────────────────────────
    Route::get('/facturas', [FacturaController::class, 'index'])->name('facturas.index');
    Route::get('/facturas/{id}/edit',  [FacturaController::class, 'edit']  )->name('facturas.edit');
    Route::put('/facturas/{id}',       [FacturaController::class, 'update'])->name('facturas.update');
    Route::post('/facturas/{id}/pago', [FacturaController::class, 'procesarPago'])->name('facturas.pago');
    Route::get('/facturas/{id}/cliente', [FacturaController::class, 'obtenerCliente'])->name('facturas.obtener-cliente');
    Route::put('/facturas/{id}/cliente', [FacturaController::class, 'actualizarCliente'])->name('facturas.actualizar-cliente');
    Route::post('/facturas/{id}/upload-comprobante',
        [FacturaController::class, 'uploadComprobante']
    )->name('facturas.upload-comprobante');

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

    // ── IMPORTACIÓN ────────────────────────────────────────────────────
    Route::get('/facturas/importar',
        [ImportarFacturasController::class, 'index']
    )->name('facturas.importar');

    Route::post('/facturas/importar/procesar',
        [ImportarFacturasController::class, 'importar']
    )->name('facturas.importar.procesar');

    // ── IMPORTAR CLIENTES ─────────────────────────────────────────────
    Route::get('/clientes/importar',  [ImportarClientesController::class, 'index'])
        ->name('clientes.importar');

    Route::post('/clientes/importar', [ImportarClientesController::class, 'importar'])
        ->name('clientes.importar.procesar');

    // ── CLIENTES ───────────────────────────────────────────────────────
    Route::get('/clientes',         [ClienteController::class, 'index']  )->name('clientes.index');
    Route::post('/clientes',        [ClienteController::class, 'store']  )->name('clientes.store');
    Route::put('/clientes/{id}',    [ClienteController::class, 'update'] )->name('clientes.update');
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');


    // ── COTIZACIONES ────────────────────────────────────────────────
    // Excel export para cotizaciones (debe ir antes de /cotizaciones/{id})
    Route::post('/cotizaciones/export-excel-bulk',
        [CotizacionExportController::class, 'exportExcelBulk'])->name('cotizaciones.export-excel-bulk');

    Route::get('/cotizaciones',
        [CotizacionController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/create',
        [CotizacionController::class, 'create'])->name('cotizaciones.create');
    Route::post('/cotizaciones',
        [CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::get('/cotizaciones/{id}',
        [CotizacionController::class, 'show'])->whereNumber('id')->name('cotizaciones.show');
    Route::post('/cotizaciones/{id}',
        [CotizacionController::class, 'update'])->whereNumber('id')->name('cotizaciones.update');
    Route::delete('/cotizaciones/{id}',
        [CotizacionController::class, 'destroy'])->whereNumber('id')->name('cotizaciones.destroy');
    Route::get('/cotizaciones/{id}/print',
        [CotizacionController::class, 'print'])->whereNumber('id')->name('cotizaciones.print');

    // Rows AJAX
    Route::post('/cotizaciones/{id}/rows',
        [CotizacionController::class, 'storeRow'])->whereNumber('id')->name('cotizaciones.rows.store');
    Route::post('/cotizaciones/{id}/rows/{rowId}',
        [CotizacionController::class, 'updateRow'])->whereNumber('id')->whereNumber('rowId')->name('cotizaciones.rows.update');
    Route::delete('/cotizaciones/{id}/rows/{rowId}',
        [CotizacionController::class, 'destroyRow'])->whereNumber('id')->whereNumber('rowId')->name('cotizaciones.rows.destroy');

    // Catálogos (Chofer, Maquinaria, Agregado)
    Route::get('/catalogos', [CatalogosController::class, 'index'])->name('catalogos.index');
    Route::post('/catalogos/choferes',         [CatalogosController::class, 'storeChofer'])->name('catalogos.choferes.store');
    Route::post('/catalogos/choferes/{id}',    [CatalogosController::class, 'updateChofer'])->name('catalogos.choferes.update');
    Route::delete('/catalogos/choferes/{id}',  [CatalogosController::class, 'destroyChofer'])->name('catalogos.choferes.destroy');
    Route::post('/catalogos/maquinarias',      [CatalogosController::class, 'storeMaquinaria'])->name('catalogos.maquinarias.store');
    Route::post('/catalogos/maquinarias/{id}', [CatalogosController::class, 'updateMaquinaria'])->name('catalogos.maquinarias.update');
    Route::delete('/catalogos/maquinarias/{id}',[CatalogosController::class, 'destroyMaquinaria'])->name('catalogos.maquinarias.destroy');
    Route::post('/catalogos/agregados',        [CatalogosController::class, 'storeAgregado'])->name('catalogos.agregados.store');
    Route::post('/catalogos/agregados/{id}',   [CatalogosController::class, 'updateAgregado'])->name('catalogos.agregados.update');
    Route::delete('/catalogos/agregados/{id}', [CatalogosController::class, 'destroyAgregado'])->name('catalogos.agregados.destroy');

    Route::get('/cotizaciones/{id}/export-excel',
        [CotizacionExportController::class, 'exportExcel'])->whereNumber('id')->name('cotizaciones.export-excel');

    // Configuración
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::get('/configuracion/whatsapp/status', [ConfiguracionController::class, 'whatsappStatus'])->name('configuracion.whatsapp.status');
    Route::get('/configuracion/whatsapp/qr',     [ConfiguracionController::class, 'whatsappQr'])->name('configuracion.whatsapp.qr');
    Route::post('/configuracion/whatsapp/logout',[ConfiguracionController::class, 'whatsappLogout'])->name('configuracion.whatsapp.logout');

// Retenciones: ruta de confirmación (AGREGAR junto con la ruta de importar)
    Route::post('/facturas/importar-retenciones/confirmar',
        [ImportarRetencionesController::class, 'confirmar']
    )->name('facturas.importar.retenciones.confirmar');

    // ── USUARIOS ───────────────────────────────────────────────────────
    Route::get('/usuarios',         [UsuarioController::class, 'index']  )->name('usuarios.index');
    Route::get('/usuarios/crear',   [UsuarioController::class, 'create'] )->name('usuarios.create');
    Route::post('/usuarios',        [UsuarioController::class, 'store']  )->name('usuarios.store');
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit']   )->name('usuarios.edit');
    Route::put('/usuarios/{id}',    [UsuarioController::class, 'update'] )->name('usuarios.update');
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    // ── REPORTES ───────────────────────────────────────────────────────
    Route::get('/reportes',         [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/json',    [ReporteController::class, 'json'] )->name('reportes.json');
    Route::get('/reportes/pdf',     [ReporteController::class, 'pdf']  )->name('reportes.pdf');
    Route::get('/reportes/excel',   [ReporteController::class, 'exportExcel'])->name('reportes.excel');
    Route::get('/reportes/deuda-general',  [ReporteController::class, 'deudaGeneral'])->name('reportes.deuda-general');
    Route::post('/reportes/enviar-whatsapp',
        [ReporteController::class, 'enviarReporteWhatsApp']
    )->name('reportes.enviar-whatsapp');
    Route::post('/reportes/enviar-correo',
        [ReporteController::class, 'enviarReporteCorreo']
    )->name('reportes.enviar-correo');

});
