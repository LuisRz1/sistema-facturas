<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos incompletos de una valorización: casilleros vacíos de sus filas y
 * control de OC/HES. Alimenta el modal de validación y el aviso al usuario
 * cuando intenta marcar la valorización como COMPLETO.
 *
 * Solo informa; nunca bloquea por sí mismo.
 */
class ValorizacionValidacionService
{
    /** Columnas de fila que no pueden quedar vacías, por tipo de valorización. */
    private const CAMPOS_MAQUINARIA = [
        'fecha'        => 'fecha',
        'id_chofer'    => 'chofer',
        'id_maquinaria'=> 'maquinaria',
        'hora_inicio'  => 'hora de inicio',
        'hora_fin'     => 'hora de término',
        'precio_hora'  => 'precio por hora',
    ];

    private const CAMPOS_AGREGADO = [
        'fecha'      => 'fecha',
        'id_chofer'  => 'chofer',
        'id_agregado'=> 'agregado',
        'm3'         => 'metros cúbicos',
        'precio_m3'  => 'precio por m³',
    ];

    /**
     * Lista de faltantes en texto legible. Vacía = valorización completa.
     *
     * @return string[]
     */
    public function faltantes(int $idCotizacion): array
    {
        $cot = DB::table('cotizacion')
            ->where('id_cotizacion', $idCotizacion)
            ->where('activo', 1)
            ->first();

        if (! $cot) {
            return [];
        }

        $esMaquinaria = $cot->tipo_cotizacion === 'MAQUINARIA';
        $tabla  = $esMaquinaria ? 'maquinaria_cotizacion' : 'agregado_cotizacion';
        $pk     = $esMaquinaria ? 'id_cotizacion_maqu' : 'id_cotizacion_agr';
        $campos = $esMaquinaria ? self::CAMPOS_MAQUINARIA : self::CAMPOS_AGREGADO;

        $filas = DB::table($tabla)
            ->where('id_cotizacion', $idCotizacion)
            ->where('activo', 1)
            ->orderBy('fecha')
            ->orderBy($pk)
            ->get();

        if ($filas->isEmpty()) {
            return ['La valorización no tiene ninguna fila registrada.'];
        }

        $faltantes    = [];
        $facturables  = [];
        $sinParte     = 0;
        $sinGrr       = 0;
        $rutaParte    = $this->columnaParteDiario($tabla);
        $numeroFila   = 0;

        foreach ($filas as $fila) {
            $numeroFila++;

            // Las filas de ajuste de horómetro no se cobran ni requieren documento.
            if (! empty($fila->es_ajuste_horometro)) {
                continue;
            }

            $vacios = [];
            foreach ($campos as $columna => $etiqueta) {
                if ($this->estaVacio($fila->{$columna} ?? null, $columna)) {
                    $vacios[] = $etiqueta;
                }
            }

            if ($vacios) {
                $fecha = ! empty($fila->fecha) ? " ({$fila->fecha})" : '';
                $faltantes[] = "Fila {$numeroFila}{$fecha}: falta " . implode(', ', $vacios) . '.';
            }

            $cobra = $fila->es_facturable === null
                ? (float) $fila->total_fila > 0
                : (bool) $fila->es_facturable;

            if ($cobra) {
                $facturables[] = $fila;

                if ($rutaParte && empty($fila->{$rutaParte})) {
                    $sinParte++;
                }
                if (! $esMaquinaria && Schema::hasColumn('agregado_cotizacion', 'ruta_grr') && empty($fila->ruta_grr)) {
                    $sinGrr++;
                }
            }
        }

        if ($esMaquinaria) {
            $faltantes = array_merge($faltantes, $this->faltantesOc($idCotizacion));
        }

        if ((bool) $cot->usa_hes) {
            $sinHes = collect($facturables)->filter(fn ($f) => empty($f->id_hes))->count();
            if ($sinHes > 0) {
                $faltantes[] = "{$sinHes} fila(s) facturable(s) sin HES asignado.";
            }
        }

        if ($sinParte > 0) {
            $faltantes[] = "{$sinParte} fila(s) facturable(s) sin parte diario adjunto.";
        }
        if ($sinGrr > 0) {
            $faltantes[] = "{$sinGrr} fila(s) facturable(s) sin GRR adjunto.";
        }

        return $faltantes;
    }

    /** true si la valorización no tiene ningún dato incompleto. */
    public function estaCompleta(int $idCotizacion): bool
    {
        return $this->faltantes($idCotizacion) === [];
    }

    /**
     * OC con cupo: existencia, documento adjunto y horas facturables sin asignar.
     *
     * @return string[]
     */
    private function faltantesOc(int $idCotizacion): array
    {
        $ordenes = DB::table('cotizacion_orden_compra')
            ->where('id_cotizacion', $idCotizacion)
            ->get(['id_orden_compra', 'numero', 'ruta_documento']);

        if ($ordenes->isEmpty()) {
            return ['No hay ninguna orden de compra registrada.'];
        }

        $faltantes = [];
        $sinDocumento = $ordenes->filter(fn ($oc) => empty($oc->ruta_documento))->count();
        if ($sinDocumento > 0) {
            $faltantes[] = "{$sinDocumento} orden(es) de compra sin documento adjunto.";
        }

        $sinOc = (float) (app(ValorizacionOcService::class)->resumen($idCotizacion)['horas_sin_oc'] ?? 0);
        if ($sinOc > 0) {
            $faltantes[] = number_format($sinOc, 2) . ' h facturables sin orden de compra asignada.';
        }

        return $faltantes;
    }

    /**
     * Un casillero está vacío si es nulo, cadena vacía o un importe/volumen en cero.
     * (Hora 0.00 no cuenta como vacía: es medianoche.)
     */
    private function estaVacio(mixed $valor, string $columna): bool
    {
        if ($valor === null || $valor === '') {
            return true;
        }

        return (float) $valor === 0.0 && in_array($columna, ['precio_hora', 'precio_m3', 'm3'], true);
    }

    /** Columna real que guarda la imagen del parte diario en esa tabla. */
    private function columnaParteDiario(string $tabla): ?string
    {
        foreach (['ruta_n_parte_diario', 'ruta_parte_diario'] as $columna) {
            if (Schema::hasColumn($tabla, $columna)) {
                return $columna;
            }
        }

        return null;
    }
}
