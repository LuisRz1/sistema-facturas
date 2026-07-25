<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MovimientoBancario extends Model
{
    protected $table = 'movimiento_bancario';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_archivo', 'banco', 'moneda', 'cuenta_bancaria',
        'fecha_operacion', 'fecha_proceso', 'hora', 'numero_operacion',
        'descripcion', 'referencia', 'importe', 'tipo_movimiento',
        'abono', 'cargo', 'saldo', 'codigo_interno_banco',
        'hash_movimiento', 'estado_conciliacion',
        'id_cliente', 'id_factura', 'score_match',
        'usuario_conciliador_id', 'fecha_conciliacion',
        'version_config_parser', 'activo',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'abono' => 'decimal:2',
        'cargo' => 'decimal:2',
        'saldo' => 'decimal:2',
        'score_match' => 'decimal:2',
        'fecha_operacion' => 'date',
        'fecha_proceso' => 'date',
        'activo' => 'boolean',
    ];

    public function archivo(): BelongsTo { return $this->belongsTo(ArchivoImportado::class, 'id_archivo'); }
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente'); }
    public function factura(): BelongsTo { return $this->belongsTo(Factura::class, 'id_factura', 'id_factura'); }
    public function historialEstados(): HasMany { return $this->hasMany(MovimientoHistorialEstado::class, 'id_movimiento')->orderBy('fecha_transicion'); }
    public function extorno(): HasOne { return $this->hasOne(Extorno::class, 'id_movimiento'); }

    // Estados de conciliacion
    public const ESTADO_IMPORTADO = 'IMPORTADO';
    public const ESTADO_CONCILIADO = 'CONCILIADO';
    public const ESTADO_CONCILIADO_TOLERANCIA = 'CONCILIADO_TOLERANCIA';
    public const ESTADO_CONCILIADO_MANUAL = 'CONCILIADO_MANUAL';
    public const ESTADO_PARCIAL = 'PARCIAL';
    public const ESTADO_SIN_MATCH = 'SIN_MATCH';
    public const ESTADO_DUPLICADO_OMITIDO = 'DUPLICADO_OMITIDO';
    public const ESTADO_EXTORNADO = 'EXTORNADO';
    public const ESTADO_IGNORADO = 'IGNORADO';
}
