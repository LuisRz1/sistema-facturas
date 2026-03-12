<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $table = 'factura';
    protected $primaryKey = 'id_factura';
    public $timestamps = false;

    protected $fillable = [
        'serie',
        'numero',
        'tipo_operacion',
        'id_cliente',
        'id_usuario',
        'moneda',
        'subtotal_gravado',
        'monto_igv',
        'importe_total',
        'estado',
        'glosa',
        'forma_pago',
        'tipo_recaudacion',
        'fecha_vencimiento',
        'fecha_emision',
        'fecha_abono',
        'fecha_creacion',
        'fecha_actualizacion',
        'usuario_creacion',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionFactura::class, 'id_factura', 'id_factura');
    }
}
