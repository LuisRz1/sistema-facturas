<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/** Reparto determinista de horas facturables, en centésimas para evitar deriva decimal. */
class ValorizacionOcService
{
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

    /** El llamador debe bloquear la cabecera de la valorización dentro de una transacción. */
    public function recalcular(int $idCotizacion): void
    {
        $ordenes = DB::table('cotizacion_orden_compra')
            ->where('id_cotizacion', $idCotizacion)
            ->orderBy('id_orden_compra')->get();
        $filas = DB::table('maquinaria_cotizacion')
            ->where('id_cotizacion', $idCotizacion)->where('activo', 1)
            ->orderBy('fecha')->orderBy('hora_inicio')->orderBy('id_cotizacion_maqu')->get();

        $ids = DB::table('maquinaria_cotizacion')->where('id_cotizacion', $idCotizacion)
            ->pluck('id_cotizacion_maqu')->all();
        if ($ids) {
            DB::table('maquinaria_cotizacion_oc')->whereIn('id_cotizacion_maqu', $ids)->delete();
        }
        $asignaciones = self::repartir($ordenes, $filas);
        if ($asignaciones) {
            DB::table('maquinaria_cotizacion_oc')->insert($asignaciones);
        }
    }

    public static function repartir(Collection $ordenes, Collection $filas): array
    {
        $disponible = [];
        foreach ($ordenes as $orden) {
            $disponible[$orden->id_orden_compra] = (int) round((float) $orden->horas_autorizadas * 100);
        }

        $asignaciones = [];
        foreach ($filas as $fila) {
            $restante = self::horasFacturables($fila);
            foreach ($ordenes as $orden) {
                if ($restante <= 0) break;
                $tomar = min($restante, $disponible[$orden->id_orden_compra]);
                if ($tomar <= 0) continue;
                $asignaciones[] = [
                    'id_cotizacion_maqu' => $fila->id_cotizacion_maqu,
                    'id_orden_compra' => $orden->id_orden_compra,
                    'horas_asignadas' => $tomar / 100,
                ];
                $disponible[$orden->id_orden_compra] -= $tomar;
                $restante -= $tomar;
            }
        }
        return $asignaciones;
    }

    public function resumen(int $idCotizacion): array
    {
        $ordenes = DB::table('cotizacion_orden_compra as oc')
            ->leftJoin('maquinaria_cotizacion_oc as a', 'a.id_orden_compra', '=', 'oc.id_orden_compra')
            ->where('oc.id_cotizacion', $idCotizacion)
            ->groupBy('oc.id_orden_compra', 'oc.id_cotizacion', 'oc.numero', 'oc.horas_autorizadas', 'oc.ruta_documento', 'oc.created_at', 'oc.updated_at')
            ->orderBy('oc.id_orden_compra')
            ->select('oc.*', DB::raw('COALESCE(SUM(a.horas_asignadas), 0) as horas_consumidas'))->get();

        $demanda = DB::table('maquinaria_cotizacion')->where('id_cotizacion', $idCotizacion)
            ->where('activo', 1)->get()->sum(fn ($fila) => self::horasFacturables($fila)) / 100;
        $asignado = $ordenes->sum('horas_consumidas');

        return [
            'ordenes' => $ordenes,
            'horas_autorizadas' => round($ordenes->sum('horas_autorizadas'), 2),
            'horas_consumidas' => round($asignado, 2),
            'horas_sin_oc' => round(max(0, $demanda - $asignado), 2),
        ];
    }

    public function detallarFilas(Collection $filas, bool $controlActivo = true): Collection
    {
        $ids = $filas->pluck('id_cotizacion_maqu')->filter()->all();
        $porFila = $ids ? DB::table('maquinaria_cotizacion_oc as a')
            ->join('cotizacion_orden_compra as oc', 'oc.id_orden_compra', '=', 'a.id_orden_compra')
            ->whereIn('a.id_cotizacion_maqu', $ids)
            ->orderBy('oc.id_orden_compra')
            ->get(['a.id_cotizacion_maqu', 'oc.numero', 'a.horas_asignadas'])
            ->groupBy('id_cotizacion_maqu') : collect();

        return $filas->map(function ($fila) use ($porFila, $controlActivo) {
            $fila->oc_asignaciones = $porFila->get($fila->id_cotizacion_maqu, collect())->values();
            $fila->horas_sin_oc = !$controlActivo ? 0 : round(max(0,
                self::horasFacturables($fila) / 100 - $fila->oc_asignaciones->sum('horas_asignadas')
            ), 2);
            return $fila;
        });
    }
}
