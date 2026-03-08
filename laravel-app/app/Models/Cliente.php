<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'cliente';
    protected $primaryKey = 'id_cliente';
    public $timestamps = false;

    protected $fillable = [
        'razon_social',
        'ruc',
        'celular',
        'direccion_fiscal',
        'correo',
        'fecha_creacion',
        'fecha_actualizacion',
        'usuario_creacion',
        'estado_contacto',
    ];
}
