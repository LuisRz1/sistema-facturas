<?php

namespace App\Jobs;

use App\Models\ArchivoImportado;
use App\Models\MovimientoBancario;
use App\Services\Conciliacion\MatchingEngine;
use App\Services\Conciliacion\MovimientoEstandar;
use App\Services\Conciliacion\ParserFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcesarArchivoBancario implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $archivoId;
    protected string $filePath;

    /**
     * @param int    $archivoId  ID del ArchivoImportado
     * @param string $filePath   Ruta absoluta del archivo bancario a procesar
     */
    public function __construct(int $archivoId, string $filePath)
    {
        $this->archivoId = $archivoId;
        $this->filePath  = $filePath;
    }

    /**
     * Ejecuta el job.
     */
    public function handle(): void
    {
        $inicio = microtime(true);

        try {
            DB::transaction(function () use ($inicio) {
                // 1. Cargar el archivo importado
                $archivo = ArchivoImportado::findOrFail($this->archivoId);

                if (!file_exists($this->filePath)) {
                    throw new \RuntimeException("El archivo no existe: {$this->filePath}");
                }

                // 2. Parsear el archivo usando ParserFactory
                $parserFactory = new ParserFactory();
                $parser = $parserFactory->crear($archivo->banco);

                /** @var MovimientoEstandar[] $movimientosEstandar */
                $movimientosEstandar = $parser->parse($this->filePath);

                if (empty($movimientosEstandar)) {
                    throw new \RuntimeException('El archivo no contiene filas válidas para procesar.');
                }

                $totalRegistros    = 0;
                $totalConciliados  = 0;
                $totalPendientes   = 0;
                $totalErrores      = 0;

                $matchingEngine = new MatchingEngine();

                // Obtener tolerancia desde la configuración del parser del banco (una sola vez)
                $parserConfig = DB::table('banco_parser_config')
                    ->where('banco', $archivo->banco)
                    ->where('activo', true)
                    ->first();

                $tolerancia = $parserConfig->tolerancia_monto ?? 5.00;

                // 3. Procesar cada movimiento estandarizado
                foreach ($movimientosEstandar as $std) {
                    $totalRegistros++;

                    try {
                        // Convertir a array para facilitar el acceso
                        $fila = $std->toArray();

                        // Calcular hash único del movimiento
                        $rawHash = implode('|', [
                            $fila['banco'] ?? $archivo->banco,
                            $fila['cuenta_bancaria'] ?? '',
                            $fila['fecha_operacion'] ?? '',
                            $fila['hora'] ?? '',
                            $fila['numero_operacion'] ?? '',
                            $fila['importe'] ?? '0',
                        ]);

                        $hashMovimiento = hash('sha256', $rawHash);

                        // Verificar duplicado
                        $existe = DB::table('movimiento_bancario')
                            ->where('hash_movimiento', $hashMovimiento)
                            ->exists();

                        if ($existe) {
                            // Insertar como DUPLICADO_OMITIDO
                            DB::table('movimiento_bancario')->insert([
                                'id_archivo'             => $archivo->id_archivo,
                                'banco'                  => $fila['banco'] ?: $archivo->banco,
                                'moneda'                 => $fila['moneda'] ?: $archivo->moneda,
                                'cuenta_bancaria'        => $fila['cuenta_bancaria'] ?? '',
                                'fecha_operacion'        => $this->parseFecha($fila['fecha_operacion'] ?? null),
                                'fecha_proceso'          => $this->parseFecha($fila['fecha_proceso'] ?? null),
                                'hora'                   => $fila['hora'] ?? null,
                                'numero_operacion'       => $fila['numero_operacion'] ?? '',
                                'descripcion'            => $fila['descripcion'] ?? '',
                                'referencia'             => $fila['referencia'] ?: null,
                                'importe'                => $fila['importe'] ?? 0,
                                'tipo_movimiento'        => $fila['tipo_movimiento'] ?? 'ABONO',
                                'abono'                  => ($fila['tipo_movimiento'] ?? 'ABONO') === 'ABONO' ? ($fila['importe'] ?? 0) : null,
                                'cargo'                  => ($fila['tipo_movimiento'] ?? 'ABONO') !== 'ABONO' ? ($fila['importe'] ?? 0) : null,
                                'saldo'                  => $fila['saldo'] ?? null,
                                'codigo_interno_banco'   => $fila['codigo_interno_banco'] ?: null,
                                'hash_movimiento'        => $hashMovimiento,
                                'estado_conciliacion'    => MovimientoBancario::ESTADO_DUPLICADO_OMITIDO,
                                'version_config_parser'  => $archivo->banco . '_v1',
                                'activo'                 => true,
                            ]);
                            Log::info('Movimiento duplicado omitido', [
                                'hash'              => $hashMovimiento,
                                'numero_operacion'  => $fila['numero_operacion'] ?? '',
                            ]);
                            continue;
                        }

                        // Insertar el movimiento
                        $idMovimiento = DB::table('movimiento_bancario')->insertGetId([
                            'id_archivo'             => $archivo->id_archivo,
                            'banco'                  => $fila['banco'] ?: $archivo->banco,
                            'moneda'                 => $fila['moneda'] ?: $archivo->moneda,
                            'cuenta_bancaria'        => $fila['cuenta_bancaria'] ?? '',
                            'fecha_operacion'        => $this->parseFecha($fila['fecha_operacion'] ?? null),
                            'fecha_proceso'          => $this->parseFecha($fila['fecha_proceso'] ?? null),
                            'hora'                   => $fila['hora'] ?? null,
                            'numero_operacion'       => $fila['numero_operacion'] ?? '',
                            'descripcion'            => $fila['descripcion'] ?? '',
                            'referencia'             => $fila['referencia'] ?: null,
                            'importe'                => $fila['importe'] ?? 0,
                            'tipo_movimiento'        => $fila['tipo_movimiento'] ?? 'ABONO',
                            'abono'                  => ($fila['tipo_movimiento'] ?? 'ABONO') === 'ABONO' ? ($fila['importe'] ?? 0) : null,
                            'cargo'                  => ($fila['tipo_movimiento'] ?? 'ABONO') !== 'ABONO' ? ($fila['importe'] ?? 0) : null,
                            'saldo'                  => $fila['saldo'] ?? null,
                            'codigo_interno_banco'   => $fila['codigo_interno_banco'] ?: null,
                            'hash_movimiento'        => $hashMovimiento,
                            'estado_conciliacion'    => MovimientoBancario::ESTADO_IMPORTADO,
                            'version_config_parser'  => $archivo->banco . '_v1',
                            'activo'                 => true,
                        ]);

                        // Recuperar el modelo insertado
                        $movimiento = MovimientoBancario::find($idMovimiento);

                        // Solo procesar ABONOS (pagos entrantes) que no sean ignorables
                        $tipoMov = $fila['tipo_movimiento'] ?? 'ABONO';
                        $esIgnorable = $std->es_ignorable;

                        if ($tipoMov === 'ABONO' && !$esIgnorable) {
                            $resultado = $matchingEngine->evaluar($movimiento, [
                                'tolerancia_monto' => $tolerancia,
                            ]);

                            if ($resultado['accion'] === 'AUTO') {
                                // Conciliación automática
                                $matchingEngine->conciliar($movimiento, $resultado);
                                $totalConciliados++;
                                Log::info('Conciliación automática exitosa', [
                                    'id_movimiento' => $idMovimiento,
                                    'score'         => $resultado['score'],
                                    'id_factura'    => $resultado['factura_id'],
                                ]);
                            } else {
                                // Sin match automático — queda pendiente en bandeja
                                DB::table('movimiento_bancario')
                                    ->where('id_movimiento', $idMovimiento)
                                    ->update([
                                        'estado_conciliacion' => MovimientoBancario::ESTADO_SIN_MATCH,
                                        'score_match'         => $resultado['score'],
                                    ]);
                                $totalPendientes++;
                            }
                        } else {
                            // No es ABONO o es ignorable — queda como IMPORTADO sin match
                            $totalPendientes++;
                        }
                    } catch (\Throwable $e) {
                        $totalErrores++;
                        Log::error('Error procesando fila del archivo bancario', [
                            'archivo_id' => $this->archivoId,
                            'fila'       => $fila ?? [],
                            'error'      => $e->getMessage(),
                            'trace'      => $e->getTraceAsString(),
                        ]);
                    }
                }

                // 4. Actualizar el archivo importado con totales
                $tiempoMs = round((microtime(true) - $inicio) * 1000);

                $archivo->update([
                    'total_registros'         => $totalRegistros,
                    'total_conciliados'       => $totalConciliados,
                    'total_pendientes'        => $totalPendientes,
                    'total_errores'           => $totalErrores,
                    'tiempo_procesamiento_ms' => $tiempoMs,
                    'estado'                  => $totalErrores === $totalRegistros ? 'ERROR' : 'COMPLETADO',
                ]);

                Log::info('Archivo bancario procesado exitosamente', [
                    'id_archivo'      => $this->archivoId,
                    'total_registros' => $totalRegistros,
                    'conciliados'     => $totalConciliados,
                    'pendientes'      => $totalPendientes,
                    'errores'         => $totalErrores,
                    'tiempo_ms'       => $tiempoMs,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Error fatal en ProcesarArchivoBancario', [
                'id_archivo' => $this->archivoId,
                'filePath'   => $this->filePath,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            // Marcar el archivo como error si aún existe
            try {
                DB::table('archivo_importado')
                    ->where('id_archivo', $this->archivoId)
                    ->update(['estado' => 'ERROR']);
            } catch (\Throwable $ex) {
                Log::error('No se pudo actualizar estado del archivo a ERROR', ['error' => $ex->getMessage()]);
            }

            throw $e;
        }
    }

    /**
     * Parsea una fecha en diversos formatos bancarios.
     *
     * @param string|null $fecha
     * @return string|null  Fecha en formato Y-m-d o null
     */
    private function parseFecha($fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }

        try {
            // Si ya es un objeto Carbon o DateTime
            if ($fecha instanceof \DateTimeInterface) {
                return $fecha->format('Y-m-d');
            }

            // Intentar formatos comunes bancarios
            $formatos = [
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'Y/m/d',
                'd.m.Y',
                'Ymd',
            ];

            foreach ($formatos as $formato) {
                try {
                    $carbon = Carbon::createFromFormat($formato, (string) $fecha);
                    if ($carbon) {
                        return $carbon->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Último recurso: parse automático
            return Carbon::parse($fecha)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('No se pudo parsear fecha: ' . $fecha, ['error' => $e->getMessage()]);
            return null;
        }
    }
}
