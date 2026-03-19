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
        'cuenta_pago',
        'ruta_comprobante_pago',
        'fecha_creacion',
        'fecha_actualizacion',
        'usuario_creacion',
        'monto_abonado',
        'monto_pendiente',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    /**
     * El usuario principal responsable de la factura.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /**
     * El usuario que creó el registro de la factura.
     */
    public function usuarioCreacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_creacion', 'id_usuario');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionFactura::class, 'id_factura', 'id_factura');
    }
}
