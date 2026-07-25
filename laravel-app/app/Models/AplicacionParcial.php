<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AplicacionParcial extends Model
{
    protected $table = 'aplicacion_parcial';
    protected $primaryKey = 'id_aplicacion';
    public $timestamps = false;

    protected $fillable = [
        'id_factura', 'id_movimiento', 'monto_aplicado',
        'saldo_remanente', 'fecha_aplicacion',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
        'saldo_remanente' => 'decimal:2',
        'fecha_aplicacion' => 'datetime',
    ];

    public function factura(): BelongsTo { return $this->belongsTo(Factura::class, 'id_factura', 'id_factura'); }
    public function movimiento(): BelongsTo { return $this->belongsTo(MovimientoBancario::class, 'id_movimiento'); }
}
