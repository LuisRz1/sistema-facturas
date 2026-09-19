<?php

namespace App\Console\Commands;

use App\Services\SaldoFacturaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NormalizarPendientesFacturas extends Command
{
    protected $signature = 'facturas:normalizar-pendientes
                            {--apply : Aplica los cambios encontrados; sin esta opción solo muestra la vista previa}';

    protected $description = 'Normaliza monto_pendiente sin sumar recaudaciones no confirmadas';

    public function handle(SaldoFacturaService $saldoFactura): int
    {
        $filas = $this->filasConDiferencia($saldoFactura);
        $resumen = $this->resumen($filas);

        $this->table(
            ['Factura', 'Moneda', 'Actual', 'Corregido', 'Diferencia', 'Recaudación confirmada'],
            $filas->map(fn (array $fila) => [
                $fila['serie'].'-'.str_pad((string) $fila['numero'], 8, '0', STR_PAD_LEFT),
                $fila['moneda'],
                number_format($fila['monto_pendiente'], 2, '.', ''),
                number_format($fila['monto_pendiente_corregido'], 2, '.', ''),
                number_format($fila['monto_pendiente'] - $fila['monto_pendiente_corregido'], 2, '.', ''),
                $fila['fecha_recaudacion'] ?: 'No',
            ])->all()
        );

        $this->newLine();
        $this->line('Facturas a actualizar: '.$resumen['facturas']);
        $this->line('Pendiente actual: '.number_format($resumen['pendiente_actual'], 2, '.', ''));
        $this->line('Pendiente corregido: '.number_format($resumen['pendiente_corregido'], 2, '.', ''));
        $this->line('Reducción: '.number_format($resumen['impacto'], 2, '.', ''));

        if (!$this->option('apply')) {
            $this->comment('Vista previa. Use --apply para actualizar los datos después de validar el resumen.');

            return self::SUCCESS;
        }

        if ($filas->isEmpty()) {
            $this->info('No hay pendientes que normalizar.');

            return self::SUCCESS;
        }

        $backupPath = $this->guardarRespaldo($filas->all(), $resumen);

        $actualizadas = DB::transaction(function () use ($saldoFactura) {
            $actualizadas = 0;

            foreach ($this->filasConDiferencia($saldoFactura, true) as $fila) {
                DB::table('factura')
                    ->where('id_factura', $fila['id_factura'])
                    ->update([
                        'monto_pendiente' => $fila['monto_pendiente_corregido'],
                        'fecha_actualizacion' => now(),
                    ]);
                $actualizadas++;
            }

            return $actualizadas;
        });

        $this->info("Normalización aplicada a {$actualizadas} facturas.");
        $this->info("Respaldo previo: {$backupPath}");

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function filasConDiferencia(SaldoFacturaService $saldoFactura, bool $forUpdate = false)
    {
        $query = DB::table('factura as f')
            ->leftJoin('recaudacion as r', 'r.id_factura', '=', 'f.id_factura')
            ->where('f.activo', 1)
            ->select([
                'f.id_factura', 'f.serie', 'f.numero', 'f.moneda', 'f.estado',
                'f.importe_total', 'f.monto_abonado', 'f.monto_pendiente',
                'f.tipo_recaudacion', 'f.monto_cambio',
                'r.total_recaudacion', 'r.fecha_recaudacion', 'r.activo as recaudacion_activa',
            ])
            ->orderBy('f.id_factura');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()
            ->map(function (object $factura) use ($saldoFactura): array {
                $corregido = $saldoFactura->calcular(
                    importeTotal: (float) $factura->importe_total,
                    montoAbonado: (float) $factura->monto_abonado,
                    totalRecaudacion: (float) ($factura->total_recaudacion ?? 0),
                    fechaRecaudacion: $factura->fecha_recaudacion,
                    moneda: (string) $factura->moneda,
                    montoCambio: $factura->monto_cambio === null ? null : (float) $factura->monto_cambio,
                    tipoRecaudacion: $factura->tipo_recaudacion,
                    estado: $factura->estado,
                    recaudacionActiva: (bool) ($factura->recaudacion_activa ?? true),
                );

                return [
                    'id_factura' => (int) $factura->id_factura,
                    'serie' => (string) $factura->serie,
                    'numero' => (int) $factura->numero,
                    'moneda' => (string) $factura->moneda,
                    'monto_pendiente' => round((float) $factura->monto_pendiente, 2),
                    'monto_pendiente_corregido' => $corregido,
                    'fecha_recaudacion' => $factura->fecha_recaudacion,
                    'importe_total' => round((float) $factura->importe_total, 2),
                    'monto_abonado' => round((float) $factura->monto_abonado, 2),
                    'total_recaudacion' => round((float) ($factura->total_recaudacion ?? 0), 2),
                ];
            })
            ->filter(fn (array $fila) => abs($fila['monto_pendiente'] - $fila['monto_pendiente_corregido']) >= 0.01)
            ->values();
    }

    /** @param array<int, array<string, mixed>> $filas */
    private function resumen(array|\Illuminate\Support\Collection $filas): array
    {
        $filas = collect($filas);

        return [
            'facturas' => $filas->count(),
            'pendiente_actual' => round((float) $filas->sum('monto_pendiente'), 2),
            'pendiente_corregido' => round((float) $filas->sum('monto_pendiente_corregido'), 2),
            'impacto' => round((float) $filas->sum(fn (array $fila) => $fila['monto_pendiente'] - $fila['monto_pendiente_corregido']), 2),
        ];
    }

    /** @param array<int, array<string, mixed>> $filas */
    private function guardarRespaldo(array $filas, array $resumen): string
    {
        $filename = 'normalizacion-pendientes-'.now()->format('Ymd-His').'.json';
        $path = 'backups/'.$filename;
        $disk = config('filesystems.default', 'local');
        $contenido = json_encode([
            'created_at' => now()->toIso8601String(),
            'summary' => $resumen,
            'rows_before_update' => $filas,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (!Storage::disk($disk)->put($path, $contenido)) {
            throw new \RuntimeException('No se pudo guardar el respaldo antes de normalizar pendientes.');
        }

        return $disk.':'.$path;
    }
}
