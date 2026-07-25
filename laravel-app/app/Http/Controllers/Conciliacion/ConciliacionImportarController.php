<?php

namespace App\Http\Controllers\Conciliacion;

use App\Http\Controllers\Controller;
use App\Models\ArchivoImportado;
use App\Services\Conciliacion\ParserFactory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador para la importación de extractos bancarios.
 *
 * Maneja:
 *   - GET  /conciliacion/importar         → formulario de importación
 *   - POST /conciliacion/importar         → procesamiento del archivo
 */
class ConciliacionImportarController extends Controller
{
    /** Tipos de archivo permitidos */
    private const EXTENSIONES_PERMITIDAS = ['xlsx', 'xls', 'csv'];

    /** Tamaño máximo en kilobytes (20 MB) */
    private const TAMANO_MAXIMO_KB = 20480;

    /**
     * Muestra el formulario de importación de extractos bancarios.
     */
    public function index()
    {
        $parserFactory = new ParserFactory();

        return view('conciliacion.importar', [
            'bancosSoportados' => $parserFactory->bancosSoportados(),
            'extensiones'      => implode(', ', array_map(fn($ext) => '.' . $ext, self::EXTENSIONES_PERMITIDAS)),
            'tamanoMaximoMB'   => self::TAMANO_MAXIMO_KB / 1024,
        ]);
    }

    /**
     * Procesa la subida de un archivo de extracto bancario.
     *
     * Flujo:
     *   1. Validar archivo (tipo, tamaño).
     *   2. Calcular hash SHA-256 y rechazar duplicados (ERR-003).
     *   3. Detectar banco del archivo mediante ParserFactory.
     *   4. Validar estructura del archivo con el parser del banco.
     *   5. Crear registro ArchivoImportado.
     *   6. Despachar job ProcesarArchivoBancario.
     *   7. Redirigir al detalle del historial.
     */
    public function procesar(Request $request)
    {
        // ── 1. Validar archivo ──────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'archivo' => [
                'required',
                'file',
                'mimes:' . implode(',', self::EXTENSIONES_PERMITIDAS),
                'max:' . self::TAMANO_MAXIMO_KB,
            ],
        ], [
            'archivo.required' => 'Debe seleccionar un archivo para importar.',
            'archivo.file'     => 'El archivo no es válido.',
            'archivo.mimes'    => 'Formato no permitido. Use: .' . implode(', .', self::EXTENSIONES_PERMITIDAS),
            'archivo.max'      => 'El archivo excede el tamaño máximo de ' . (self::TAMANO_MAXIMO_KB / 1024) . ' MB.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('conciliacion.importar')
                ->withErrors($validator)
                ->withInput();
        }

        /** @var UploadedFile $archivo */
        $archivo = $request->file('archivo');

        // ── 2. Calcular hash y rechazar duplicados (ERR-003) ────────
        $hash = hash_file('sha256', $archivo->getRealPath());

        $existente = ArchivoImportado::where('hash_archivo', $hash)
            ->where('activo', true)
            ->first();

        if ($existente) {
            return redirect()
                ->route('conciliacion.importar')
                ->with('error', "ERR-003: Este archivo ya fue importado anteriormente (ID: {$existente->id_archivo}, fecha: {$existente->fecha_importacion->format('d/m/Y H:i')}).")
                ->withInput();
        }

        // ── 3. Detectar banco ───────────────────────────────────────
        $parserFactory = new ParserFactory();
        $deteccion = $parserFactory->detectar($archivo->getRealPath());

        if (!$deteccion['ok']) {
            return redirect()
                ->route('conciliacion.importar')
                ->with('error', 'No se pudo detectar el banco de origen. ' . ($deteccion['error'] ?? 'Formato no reconocido.'))
                ->withInput();
        }

        $banco  = $deteccion['banco'];
        $moneda = $deteccion['moneda'] ?? 'PEN';

        // ── 4. Validar estructura con el parser del banco ────────────
        try {
            $parser = $parserFactory->crear($banco);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('conciliacion.importar')
                ->with('error', 'Banco detectado pero no soportado: ' . $e->getMessage())
                ->withInput();
        }

        $validacion = $parser->validarEstructura($archivo->getRealPath());

        if (!$validacion['ok']) {
            return redirect()
                ->route('conciliacion.importar')
                ->with('error', 'El archivo tiene errores de estructura: ' . ($validacion['error'] ?? 'Formato invalido.'))
                ->withInput();
        }

        // ── 5. Guardar archivo y crear registro ─────────────────────
        $nombreOriginal = $archivo->getClientOriginalName();
        $rutaGuardado = $archivo->storeAs(
            'conciliacion/' . date('Y/m'),
            date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal),
            'local'
        );

        $archivoImportado = ArchivoImportado::create([
            'banco'                   => $banco,
            'moneda'                  => $moneda,
            'nombre_archivo'          => $nombreOriginal,
            'hash_archivo'            => $hash,
            'usuario_id'              => auth()->id(),
            'fecha_importacion'       => Carbon::now(),
            'estado'                  => 'PENDIENTE',
            'total_registros'         => 0,
            'total_conciliados'       => 0,
            'total_pendientes'        => 0,
            'total_errores'           => 0,
            'tiempo_procesamiento_ms' => 0,
            'activo'                  => true,
        ]);

        // ── 6. Despachar job de procesamiento ────────────────────────
        // El job ProcesarArchivoBancario se encarga de parsear el archivo
        // y crear los registros MovimientoBancario en segundo plano.
        //
        // NOTA: La clase ProcesarArchivoBancario debe ser creada en Phase 2.
        // Por ahora despachamos solo si existe; si no, se procesa sincrónicamente
        // como fallback.
        $jobClass = 'App\\Jobs\\ProcesarArchivoBancario';
        if (class_exists($jobClass)) {
            $jobClass::dispatch($archivoImportado->id_archivo, $rutaGuardado);
        } else {
            // Fallback: procesamiento síncrono básico (Phase 2 lo reemplazará)
            $this->procesarSincronicoFallback($archivoImportado, $rutaGuardado, $parser);
        }

        // ── 7. Redirigir al detalle del historial ────────────────────
        return redirect()
            ->route('conciliacion.historial.detalle', ['id' => $archivoImportado->id_archivo])
            ->with('success', "Archivo '{$nombreOriginal}' importado correctamente. Banco: {$banco}. Los movimientos se están procesando.");
    }

    /**
     * Procesamiento sincrónico de fallback cuando el job aún no existe.
     *
     * Útil durante el desarrollo de Phase 1; será reemplazado por el job
     * asíncrono en Phase 2.
     */
    private function procesarSincronicoFallback(
        ArchivoImportado $archivoImportado,
        string $rutaArchivo,
        \App\Services\Conciliacion\Parsers\IBankStatementParser $parser
    ): void {
        try {
            $rutaCompleta = Storage::path($rutaArchivo);
            $movimientos = $parser->parse($rutaCompleta);

            $ignorados = 0;
            $importados = 0;

            foreach ($movimientos as $mov) {
                if ($mov->es_ignorable) {
                    $ignorados++;
                    continue;
                }

                // Generar hash unico del movimiento
                $hashMov = hash('sha256', implode('|', [
                    $mov->banco,
                    $mov->fecha_operacion ?? '',
                    $mov->descripcion ?? '',
                    $mov->importe,
                ]));

                // Verificar duplicado de movimiento
                $existente = \App\Models\MovimientoBancario::where('hash_movimiento', $hashMov)
                    ->where('activo', true)
                    ->exists();

                if ($existente) {
                    continue;
                }

                \App\Models\MovimientoBancario::create([
                    'id_archivo'            => $archivoImportado->id_archivo,
                    'banco'                 => $mov->banco,
                    'moneda'                => $mov->moneda,
                    'cuenta_bancaria'       => $mov->cuenta_bancaria,
                    'fecha_operacion'       => $mov->fecha_operacion,
                    'fecha_proceso'         => $mov->fecha_proceso,
                    'hora'                  => $mov->hora,
                    'numero_operacion'      => $mov->numero_operacion,
                    'descripcion'           => $mov->descripcion,
                    'referencia'            => $mov->referencia,
                    'importe'               => $mov->importe,
                    'tipo_movimiento'       => $mov->tipo_movimiento,
                    'abono'                 => $mov->abono,
                    'cargo'                 => $mov->cargo,
                    'saldo'                 => $mov->saldo,
                    'codigo_interno_banco'  => $mov->codigo_interno_banco,
                    'hash_movimiento'       => $hashMov,
                    'estado_conciliacion'   => \App\Models\MovimientoBancario::ESTADO_IMPORTADO,
                    'activo'                => true,
                ]);

                $importados++;
            }

            // Actualizar resumen del archivo
            $archivoImportado->update([
                'estado'                  => 'PROCESADO',
                'total_registros'         => count($movimientos),
                'total_conciliados'       => 0,
                'total_pendientes'        => $importados,
                'total_errores'           => $ignorados,
                'tiempo_procesamiento_ms' => 0,
            ]);

        } catch (\Throwable $e) {
            $archivoImportado->update([
                'estado'  => 'ERROR',
            ]);

            // Log del error para debugging
            logger()->error('ConciliacionImportarController: Error en procesamiento sincrónico', [
                'archivo_id' => $archivoImportado->id_archivo,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
