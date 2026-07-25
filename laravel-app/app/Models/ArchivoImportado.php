<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoImportado extends Model
{
    protected $table = 'archivo_importado';
    protected $primaryKey = 'id_archivo';
    public $timestamps = false;

    protected $fillable = [
        'banco', 'moneda', 'nombre_archivo', 'hash_archivo',
        'usuario_id', 'fecha_importacion', 'estado',
        'total_registros', 'total_conciliados', 'total_pendientes',
        'total_errores', 'tiempo_procesamiento_ms', 'activo',
    ];

    protected $casts = [
        'fecha_importacion' => 'datetime',
        'total_registros' => 'integer',
        'total_conciliados' => 'integer',
        'total_pendientes' => 'integer',
        'total_errores' => 'integer',
        'activo' => 'boolean',
    ];

    public function usuario(): BelongsTo { return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario'); }
    public function movimientos(): HasMany { return $this->hasMany(MovimientoBancario::class, 'id_archivo'); }
}
