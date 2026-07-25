<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Extorno extends Model
{
    protected $table = 'extorno';
    protected $primaryKey = 'id_extorno';
    public $timestamps = false;

    protected $fillable = [
        'id_movimiento', 'usuario_id', 'aprobado_por_id',
        'motivo', 'monto', 'estado', 'fecha_extorno',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_extorno' => 'datetime',
    ];

    public const ESTADO_PENDIENTE = 'PENDIENTE_APROBACION';
    public const ESTADO_EJECUTADO = 'EJECUTADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    public function movimiento() { return $this->belongsTo(MovimientoBancario::class, 'id_movimiento'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario'); }
}
