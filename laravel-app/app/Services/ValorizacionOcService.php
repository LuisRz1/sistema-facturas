<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * OC de una valorización de maquinaria. La asignación es 100% manual: cada fila
 * facturable tiene (o no) una única OC en `maquinaria_cotizacion.id_orden_compra`.
 * Este servicio solo deriva el consumo por OC y las horas sin OC.
 */
class ValorizacionOcService
{
    /** Horas facturables de una fila, en centésimas para evitar deriva decimal. */
    public static function horasFacturables(object $fila): int
    {
        $cobra = $fila->es_facturable === null
            ? (float) $fila->total_fila > 0
            : (bool) $fila->es_facturable;

        if (!$cobra || (bool) ($fila->es_ajuste_horometro ?? false)) {
            return 0;
        }

        return max(
            (int) round((float) $fila->horas_trabajadas * 100),
            (int) round((float) $fila->hora_minima * 100)
        );
    }

    /** Resumen de cupos: autorizado, consumido y horas sin OC. */
    public function resumen(int $idCotizacion): array
    {
        $ordenes = DB::table('cotizacion_orden_compra')
            ->where('id_cotizacion', $idCotizacion)
            ->orderBy('id_orden_compra')
            ->get();

        $filas = DB::table('maquinaria_cotizacion')
            ->where('id_cotizacion', $idCotizacion)
            ->where('activo', 1)
            ->get();

        $consumoPorOc = [];
        $horasSinOc = 0.0;

        foreach ($filas as $fila) {
            $horas = self::horasFacturables($fila) / 100;
            if ($horas <= 0) {
                continue;
            }

            if (!empty($fila->id_orden_compra)) {
                $idOc = (int) $fila->id_orden_compra;
                $consumoPorOc[$idOc] = ($consumoPorOc[$idOc] ?? 0) + $horas;
            } else {
                $horasSinOc += $horas;
            }
        }

        $ordenes = $ordenes->map(function ($orden) use ($consumoPorOc) {
            $orden->horas_consumidas = round($consumoPorOc[(int) $orden->id_orden_compra] ?? 0, 2);
            return $orden;
        });

        return [
            'ordenes' => $ordenes,
            'horas_autorizadas' => round((float) $ordenes->sum('horas_autorizadas'), 2),
            'horas_consumidas' => round((float) $ordenes->sum('horas_consumidas'), 2),
            'horas_sin_oc' => round(max(0, $horasSinOc), 2),
        ];
    }

    /**
     * Adjunta a cada fila su OC asignada (una sola) y las horas sin OC.
     * Espera que las filas traigan `id_orden_compra` y, opcionalmente, `oc_numero`.
     */
    public function detallarFilas(Collection $filas, bool $controlActivo = true): Collection
    {
        return $filas->map(function ($fila) use ($controlActivo) {
            $horas = (float) self::horasFacturables($fila) / 100;
            $tieneOc = $controlActivo && !empty($fila->id_orden_compra);

            $fila->oc_asignaciones = $tieneOc
                ? collect([(object) [
                    'numero' => $fila->oc_numero ?? null,
                    'horas_asignadas' => round($horas, 2),
                ]])
                : collect();

            $fila->horas_sin_oc = ($controlActivo && !$tieneOc) ? round(max(0, $horas), 2) : 0.0;

            return $fila;
        });
    }
}
