<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'fecha_creacion',
        'fecha_actualizacion',
        'usuario_creacion',
        'monto_abonado',
        'monto_pendiente',
        'monto_cambio',
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

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoFactura::class, 'id_factura', 'id_factura')
            ->where('activo', 1)
            ->orderBy('fecha_pago')
            ->orderBy('id_pago');
    }

    /**
     * Si esta factura es una nota de crédito, obtiene la relación con el crédito.
     */
    public function credito()
    {
        return $this->hasOne(Credito::class, 'id_factura', 'id_factura');
    }
}
