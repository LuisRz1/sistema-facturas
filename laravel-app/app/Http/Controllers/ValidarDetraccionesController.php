<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

/**
 * Controlador para la validación masiva de detracciones
 * a partir del Excel oficial descargado de SUNAT / Banco de la Nación.
 *
 * Columnas requeridas en la FILA 2 del Excel:
 *   - "Fecha Pago"            → fecha en que se depositó la detracción
 *   - "Monto Deposito"        → monto total de la detracción
 *   - "Serie de Comprobante"  → serie de la factura (ej. FF01)
 *   - "Numero de Comprobante" → número de la factura (ej. 6212)
 *
 * Fila 1 = título / cabecera del archivo (se ignora).
 * Fila 2 = nombres de columnas.
 * Filas 3+ = datos.
 */
class ValidarDetraccionesController extends Controller
{
    // ── Columnas que DEBEN existir en la fila 2 ───────────────────────────
    private const COLS_REQUERIDAS = [
        'fecha_pago'   => 'Fecha Pago',
        'monto'        => 'Monto Deposito',
        'serie'        => 'Serie de Comprobante',
        'numero'       => 'Numero de Comprobante',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // VISTA
    // ─────────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('facturas.validar_detracciones');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PROCESAMIENTO
    // ─────────────────────────────────────────────────────────────────────

    public function procesar(Request $request)
    {
        set_time_limit(120);
        ini_set('memory_limit', '256M');

        $request->validate([
            'archivo'    => 'required|file|mimes:xlsx,xls|max:10240',
            'sheet_name' => 'nullable|string|max:100',
        ], [
            'archivo.required' => 'Selecciona un archivo Excel.',
            'archivo.mimes'    => 'El archivo debe ser .xlsx o .xls.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo leer el Excel: ' . $e->getMessage(),
            ], 422);
        }

        $sheetNames = $spreadsheet->getSheetNames();

        // ── Múltiples hojas sin sheet_name especificado ───────────────────
        if (count($sheetNames) > 1 && !$request->filled('sheet_name')) {
            return response()->json([
                'success'     => false,
                'multi_sheet' => true,
                'sheets'      => $sheetNames,
                'error'       => 'El archivo tiene ' . count($sheetNames) . ' hojas. Selecciona la hoja que contiene las detracciones.',
            ], 200);
        }

        // ── Seleccionar hoja ──────────────────────────────────────────────
        if ($request->filled('sheet_name')) {
            $sheetIndex = array_search($request->input('sheet_name'), $sheetNames, true);
            if ($sheetIndex === false) {
                return response()->json([
                    'success' => false,
                    'error'   => 'La hoja "' . $request->input('sheet_name') . '" no existe en el archivo.',
                ], 422);
            }
            $ws = $spreadsheet->getSheet($sheetIndex);
        } else {
            $ws = $spreadsheet->getActiveSheet();
        }

        // ── Leer encabezados de FILA 2 ───────────────────────────────────
        $headerRow = $ws->rangeToArray('A2:ZZ2', null, true, false, false)[0] ?? [];
        // Normalizar: trim + minúsculas para comparación
        $headerNorm = array_map(fn($h) => mb_strtolower(trim((string) $h)), $headerRow);

        // Mapear las columnas requeridas a índice (base 0)
        $colMap = [];
        foreach (self::COLS_REQUERIDAS as $key => $label) {
            $needle = mb_strtolower(trim($label));
            $idx    = array_search($needle, $headerNorm, true);
            if ($idx === false) {
                return response()->json([
                    'success' => false,
                    'error'   => "No se encontró la columna requerida \"$label\" en la fila 2 del Excel. "
                        . "Verifica que el archivo sea el reporte oficial de SUNAT / Banco de la Nación.",
                ], 422);
            }
            $colMap[$key] = $idx;
        }

        // ── Leer filas de datos (fila 3 en adelante) ─────────────────────
        $allRows = $ws->toArray(null, true, false, false);
        // Índice 0 = fila 1 (título), índice 1 = fila 2 (headers), índice 2+ = datos
        $dataRows = array_slice($allRows, 2);

        if (empty($dataRows)) {
            return response()->json([
                'success' => false,
                'error'   => 'El Excel no contiene filas de datos (a partir de la fila 3).',
            ], 422);
        }

        // ── Procesar cada fila ────────────────────────────────────────────
        $validadas   = [];  // facturas que cambiaron de estado
        $noEncontradas = 0;
        $yaValidadas   = 0;

        DB::beginTransaction();
        try {
            foreach ($dataRows as $row) {
                $serie  = trim((string) ($row[$colMap['serie']]  ?? ''));
                $numero = trim((string) ($row[$colMap['numero']] ?? ''));

                if ($serie === '' || $numero === '') continue;

                $numero = (int) $numero;

                // Parsear fecha de pago
                $fechaPagoRaw = $row[$colMap['fecha_pago']] ?? null;
                $fechaPago    = $this->parsearFecha($fechaPagoRaw);

                // Monto
                $monto = abs((float) ($row[$colMap['monto']] ?? 0));

                // Buscar factura POR VALIDAR DETRACCION con esa serie+número
                $factura = DB::table('factura')
                    ->where('serie',  $serie)
                    ->where('numero', $numero)
                    ->first();

                if (!$factura) {
                    $noEncontradas++;
                    continue;
                }

                // Solo procesar las que están en POR VALIDAR DETRACCION
                if ($factura->estado !== 'POR VALIDAR DETRACCION') {
                    $yaValidadas++;
                    continue;
                }

                // ── Determinar nuevo estado ───────────────────────────────
                // Con detracción validada → calcular pendiente real
                $montoAbonado     = (float) ($factura->monto_abonado ?? 0);
                $importeTotal     = (float) ($factura->importe_total ?? 0);

                // Usar el monto del Excel si mayor que 0, sino el que ya tenía en recaudacion
                $recExistente = DB::table('recaudacion')
                    ->where('id_factura', $factura->id_factura)
                    ->first();

                $totalRecaudacion = $monto > 0
                    ? $monto
                    : (float) ($recExistente->total_recaudacion ?? 0);

                $montoPendiente = max(0, $importeTotal - $montoAbonado - $totalRecaudacion);

                if ($montoPendiente <= 0) {
                    $nuevoEstado = 'PAGADA';
                } elseif ($montoAbonado > 0) {
                    $nuevoEstado = 'PAGO PARCIAL';
                } else {
                    // Tiene detracción pero aún falta abono → PENDIENTE
                    $nuevoEstado = 'PENDIENTE';
                }

                // ── Actualizar factura ────────────────────────────────────
                DB::table('factura')
                    ->where('id_factura', $factura->id_factura)
                    ->update([
                        'estado'              => $nuevoEstado,
                        'monto_pendiente'     => $montoPendiente,
                        'fecha_actualizacion' => now(),
                        // Si hay fecha de pago y no tenía fecha_abono, registrarla
                        'fecha_abono'         => $factura->fecha_abono ?? $fechaPago,
                    ]);

                // ── Actualizar / crear recaudación con fecha_recaudacion ──
                DB::table('recaudacion')->updateOrInsert(
                    ['id_factura' => $factura->id_factura],
                    [
                        'porcentaje'         => $recExistente->porcentaje ?? 0,
                        'total_recaudacion'  => $totalRecaudacion,
                        'fecha_recaudacion'  => $fechaPago,
                    ]
                );

                // ── Guardar para mostrar en modal de resultados ───────────
                $cliente = DB::table('cliente')
                    ->where('id_cliente', $factura->id_cliente)
                    ->value('razon_social');

                $validadas[] = [
                    'id_factura'       => $factura->id_factura,
                    'serie'            => $factura->serie,
                    'numero'           => str_pad($factura->numero, 8, '0', STR_PAD_LEFT),
                    'razon_social'     => $cliente ?? '—',
                    'moneda'           => $factura->moneda,
                    'importe_total'    => number_format($importeTotal, 2),
                    'monto_detraccion' => number_format($totalRecaudacion, 2),
                    'monto_pendiente'  => number_format($montoPendiente, 2),
                    'estado_anterior'  => 'POR VALIDAR DETRACCION',
                    'estado_nuevo'     => $nuevoEstado,
                    'fecha_recaudacion'=> $fechaPago,
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => 'Error al procesar: ' . $e->getMessage() .
                    ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']',
            ], 500);
        }

        return response()->json([
            'success'        => true,
            'validadas'      => $validadas,
            'total_validadas'=> count($validadas),
            'no_encontradas' => $noEncontradas,
            'ya_validadas'   => $yaValidadas,
            'total_filas'    => count($dataRows),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function parsearFecha(mixed $v): ?string
    {
        if (empty($v)) return null;
        // Objeto DateTime de PhpSpreadsheet
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        if (is_numeric($v)) {
            try { return ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d'); }
            catch (\Throwable) {}
        }
        try { return Carbon::createFromFormat('d/m/Y', trim((string) $v))->format('Y-m-d'); }
        catch (\Throwable) {}
        try { return Carbon::parse((string) $v)->format('Y-m-d'); }
        catch (\Throwable) {}
        return null;
    }
}
