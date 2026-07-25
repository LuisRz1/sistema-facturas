<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MovimientoHistorialEstado extends Model
{
    protected $table = 'movimiento_historial_estado';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;

    protected $fillable = [
        'id_movimiento', 'estado_anterior', 'estado_nuevo',
        'usuario_id', 'motivo', 'fecha_transicion',
    ];

    protected $casts = ['fecha_transicion' => 'datetime'];

    public function movimiento() { return $this->belongsTo(MovimientoBancario::class, 'id_movimiento'); }
}
