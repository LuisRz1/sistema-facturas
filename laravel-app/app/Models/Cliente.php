<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table      = 'cliente';
    protected $primaryKey = 'id_cliente';
    public    $timestamps = false;

    protected $fillable = [
        'razon_social',
        'ruc',
        'celular',
        'direccion_fiscal',
        'correo',
        'fecha_creacion',
        'fecha_actualizacion',
        'estado_contado',   // nombre correcto de la columna en BD
    ];

    public function usuarioCreacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_creacion', 'id_usuario');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_cliente', 'id_cliente');
    }
}
