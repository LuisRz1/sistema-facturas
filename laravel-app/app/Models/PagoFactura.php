<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoFactura extends Model
{
    protected $table      = 'pago_factura';
    protected $primaryKey = 'id_pago';
    public $timestamps    = false;

    protected $fillable = [
        'id_factura',
        'monto_pagado',
        'fecha_pago',
        'cuenta_pago',
        'ruta_comprobante_pago',
        'numero_operacion',
        'banco_origen',
        'forma_pago',
        'observacion',
        'activo',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura', 'id_factura');
    }
}
