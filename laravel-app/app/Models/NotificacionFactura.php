<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionFactura extends Model
{
    protected $table = 'notificacion_factura';
    protected $primaryKey = 'id_notificacion';
    public $timestamps = false;

    protected $fillable = [
        'id_factura',
        'id_regla',
        'canal',
        'categoria',
        'tipo_notificacion',
        'numero_intento_dia',
        'destinatario',
        'asunto',
        'mensaje',
        'estado_envio',
        'fecha_programada',
        'fecha_envio',
        'respuesta_proveedor',
        'observacion',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura', 'id_factura');
    }
}
