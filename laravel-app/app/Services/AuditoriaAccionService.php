<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AuditoriaAccionService
{
    public function registrar(string $entidad, ?int $idEntidad, string $accion, array $detalle = []): void
    {
        // El servicio se usa también en pruebas con esquemas aislados y durante
        // despliegues previos a la migración; no debe bloquear la operación.
        if (!Schema::hasTable('auditoria_accion')) {
            return;
        }

        DB::table('auditoria_accion')->insert([
            'id_usuario' => Auth::id(),
            'entidad' => $entidad,
            'id_entidad' => $idEntidad,
            'accion' => $accion,
            'detalle' => $detalle === [] ? null : json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip_origen' => request()?->ip(),
            'fecha_creacion' => now(),
        ]);
    }
}
