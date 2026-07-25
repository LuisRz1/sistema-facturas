<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BancoParserConfig extends Model
{
    protected $table = 'banco_parser_config';
    protected $primaryKey = 'id_config';

    protected $fillable = [
        'banco', 'version', 'mapeo_columnas', 'tipos_ignorables',
        'tolerancia_monto', 'tolerancia_dias', 'activo',
    ];

    protected $casts = [
        'mapeo_columnas' => 'array',
        'tipos_ignorables' => 'array',
        'tolerancia_monto' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
