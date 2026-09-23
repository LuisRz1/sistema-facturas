<?php

namespace App\Console\Commands;

use App\Services\SaldoFacturaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepararRecaudacionesUsd extends Command
{
    protected $signature = 'facturas:reparar-recaudaciones-usd
                            {--apply : Aplica los cambios encontrados; por defecto solo muestra la vista previa}
                            {--factura= : Limita la reparación a un id_factura concreto}';

    protected $description = 'Recupera el tipo de cambio de recaudaciones USD históricas y recalcula sus saldos';

    public function handle(SaldoFacturaService $saldoFactura): int
    {
        $filas = $this->candidatas($saldoFactura);

        $this->table(
            ['Factura', 'Recaudación PEN', 'Equiv. USD', 'TC recuperado', 'Pendiente actual', 'Pendiente corregido'],
            $filas->map(fn (array $fila) => [
                $fila['serie'].'-'.str_pad((string) $fila['numero'], 8, '0', STR_PAD_LEFT),
                number_format($fila['total_recaudacion'], 2, '.', ''),
                number_format($fila['equivalente_usd'], 2, '.', ''),
                number_format($fila['monto_cambio_recuperado'], 4, '.', ''),
                number_format($fila['monto_pendiente'], 2, '.', ''),
                number_format($fila['monto_pendiente_corregido'], 2, '.', ''),
            ])->all(),
        );

        $this->newLine();
        $this->line('Facturas candidatas: '.$filas->count());

        if (!$this->option('apply')) {
            $this->comment('Vista previa. Use --apply después de revisar los montos y el tipo de cambio recuperado.');

            return self::SUCCESS;
        }

        if ($filas->isEmpty()) {
            $this->info('No hay recaudaciones USD históricas que reparar.');

            return self::SUCCESS;
        }

        $backupPath = $this->guardarRespaldo($filas->all());

        $actualizadas = DB::transaction(function () use ($saldoFactura): int {
            $actualizadas = 0;

            foreach ($this->candidatas($saldoFactura, true) as $fila) {
                DB::table('factura')
                    ->where('id_factura', $fila['id_factura'])
                    ->update([
                        'monto_cambio' => $fila['monto_cambio_recuperado'],
                        'monto_pendiente' => $fila['monto_pendiente_corregido'],
                        'estado' => $fila['estado_corregido'],
                        'fecha_actualizacion' => now(),
                    ]);
                $actualizadas++;
            }

            return $actualizadas;
        });

        $this->info("Reparación aplicada a {$actualizadas} factura(s).");
        $this->info("Respaldo previo: {$backupPath}");

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function candidatas(SaldoFacturaService $saldoFactura, bool $forUpdate = false)
    {
        $query = DB::table('factura as f')
            ->join('recaudacion as r', 'r.id_factura', '=', 'f.id_factura')
            ->where('f.activo', 1)
            ->whereRaw('UPPER(f.moneda) = ?', ['USD'])
            ->where('r.activo', 1)
            ->whereNotNull('r.fecha_recaudacion')
            ->where('r.total_recaudacion', '>', 0)
            ->where('r.porcentaje', '>', 0)
            ->where(function ($query) {
                $query->whereNull('f.monto_cambio')->orWhere('f.monto_cambio', '<=', 0);
            })
            ->where(function ($query) {
                $query->whereNull('f.tipo_recaudacion')->orWhere('f.tipo_recaudacion', '!=', 'AUTODETRACCION');
            })
            ->select([
                'f.id_factura', 'f.serie', 'f.numero', 'f.importe_total', 'f.monto_abonado',
                'f.monto_pendiente', 'f.estado', 'f.fecha_vencimiento', 'f.tipo_recaudacion',
                'r.total_recaudacion', 'r.porcentaje', 'r.fecha_recaudacion',
            ])
            ->orderBy('f.id_factura');

        if ($this->option('factura') !== null) {
            $query->where('f.id_factura', (int) $this->option('factura'));
        }
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()
            ->map(function (object $factura) use ($saldoFactura): ?array {
                // En las filas USD creadas por la interfaz histórica,
                // porcentaje contiene el equivalente USD (no un porcentaje).
                $equivalenteUsd = round((float) $factura->porcentaje, 2);
                $tipoCambio = round((float) $factura->total_recaudacion / $equivalenteUsd, 4);

                // Evita reinterpretar porcentajes reales o datos corruptos como
                // tipo de cambio. Es el rango histórico razonable PEN/USD.
                if ($tipoCambio < 2 || $tipoCambio > 6) {
                    return null;
                }

                $pendiente = $saldoFactura->calcular(
                    importeTotal: (float) $factura->importe_total,
                    montoAbonado: (float) $factura->monto_abonado,
                    totalRecaudacion: (float) $factura->total_recaudacion,
                    fechaRecaudacion: $factura->fecha_recaudacion,
                    moneda: 'USD',
                    montoCambio: $tipoCambio,
                    tipoRecaudacion: $factura->tipo_recaudacion,
                    estado: $factura->estado,
                    recaudacionActiva: true,
                );

                return [
                    'id_factura' => (int) $factura->id_factura,
                    'serie' => (string) $factura->serie,
                    'numero' => (int) $factura->numero,
                    'total_recaudacion' => round((float) $factura->total_recaudacion, 2),
                    'equivalente_usd' => $equivalenteUsd,
                    'monto_cambio_recuperado' => $tipoCambio,
                    'monto_pendiente' => round((float) $factura->monto_pendiente, 2),
                    'monto_pendiente_corregido' => $pendiente,
                    'estado_corregido' => $this->estadoCorregido($factura, $pendiente),
                ];
            })
            ->filter()
            ->values();
    }

    private function estadoCorregido(object $factura, float $pendiente): string
    {
        if ($pendiente <= 0) {
            return 'PAGADA';
        }
        if ((float) $factura->total_recaudacion > 0 && !empty($factura->fecha_recaudacion)) {
            return 'DIFERENCIA PENDIENTE';
        }
        if ((float) $factura->monto_abonado > 0) {
            return 'DIFERENCIA PENDIENTE';
        }
        if ($factura->fecha_vencimiento && $factura->fecha_vencimiento < now()->toDateString()) {
            return 'VENCIDO';
        }

        return 'PENDIENTE';
    }

    /** @param array<int, array<string, mixed>> $filas */
    private function guardarRespaldo(array $filas): string
    {
        $path = 'backups/reparacion-recaudaciones-usd-'.now()->format('Ymd-His').'.json';
        $disk = config('filesystems.default', 'local');
        $contenido = json_encode([
            'created_at' => now()->toIso8601String(),
            'rows_before_update' => $filas,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (!Storage::disk($disk)->put($path, $contenido)) {
            throw new \RuntimeException('No se pudo guardar el respaldo antes de reparar las recaudaciones USD.');
        }

        return $disk.':'.$path;
    }
}
