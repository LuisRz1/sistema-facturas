<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table      = 'usuario';
    protected $primaryKey = 'id_usuario';
    public    $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'nombre_usuario',
        'clave_usuario',
        'correo',
        'celular',
        'id_rol',
    ];

    protected $hidden = ['clave_usuario'];

    // ── Mapeo de campos para Laravel Auth ─────────────────────────────────

    /**
     * El campo que identifica al usuario (username de login).
     */
    public function getAuthIdentifierName(): string
    {
        return 'id_usuario';
    }

    /**
     * El campo que contiene la contraseña almacenada.
     */
    public function getAuthPasswordName(): string
    {
        return 'clave_usuario';
    }

    public function getAuthPassword(): string
    {
        return $this->clave_usuario;
    }

    /**
     * Nombre completo del usuario.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }
}
