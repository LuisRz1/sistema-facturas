<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

/**
 * Importa el Excel oficial de Retenciones SUNAT (Consulta de Comprobantes Emitidos/Recibidos).
 *
 * Estructura del Excel por cada bloque de retención:
 *   Fila N+0  : (vacía o título global)
 *   Fila N+4  : Emisor  → col B = "Emisor:"  col C = "RUC XXXXXXXX - NOMBRE"
 *   Fila N+5  : Receptor
 *   Fila N+6  : Importe Total Retenido | tasa %
 *   Fila N+7  : col H = "Fecha de emisión:" (= fecha de pago/recaudación)  col I = "DD/MM/YYYY"
 *   Fila N+8  : cabeceras (Tipo | Serie | Número | Fecha emisión | Total comprobante | ... | Retención | ...)
 *   Fila N+9+ : filas de facturas hasta encontrar la fila de subtotal (solo col H tiene número)
 *
 * Columnas de cada fila de factura (A..I):
 *   A = Tipo de documento  (ignorado, siempre FACTURA)
 *   B = Serie              → serie
 *   C = Número             → numero
 *   D = Fecha de emisión   → fecha_emision de la factura
 *   E = Total comprobante  → importe_total  (formato "S/ 1234.56")
 *   F = Nro. de pago       (ignorado)
 *   G = Importe pagado     → monto_abonado  (pendiente de validación manual)
 *   H = Retención S/       → total_recaudacion
 *   I = Monto neto a pagar (ignorado)
 *
 * Campos tomados del bloque cabecera:
 *   - fecha_recaudacion  = col I de la fila "Fecha de emisión:" del comprobante de retención
 *   - ruc_emisor         = extraído de col C de la fila "Emisor:"
 *   - razon_social       = extraído de col C de la fila "Emisor:"
 *   - porcentaje         = col F de la fila "Importe Total Retenido:" (tasa %)
 */
class ImportarRetencionesController extends Controller
{
    public function index()
    {
        return view('facturas.importar_retenciones');
    }

    public function importar(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $request->validate([
            'archivo' => 'required|file|max:10240',
        ], [
            'archivo.required' => 'Selecciona un archivo Excel de retenciones.',
        ]);

        $archivo   = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'])) {
            return back()->with('error', 'El archivo debe ser .xlsx o .xls')->withInput();
        }

        try {
            $spreadsheet = IOFactory::load($archivo->getPathname());
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el Excel: ' . $e->getMessage())->withInput();
        }

        $hoja = $spreadsheet->getActiveSheet();
        $rows = $hoja->toArray(null, true, false, false); // array 0-indexed, columnas 0-indexed

        $idUsuario  = Auth::id();
        $procesadas = 0;
        $omitidas   = 0;
        $duplicadas = 0;
        $noEncontradas = 0;
        $errores    = [];
        $resultados = [];

        DB::beginTransaction();

        try {
            $bloques = $this->extraerBloques($rows);

            foreach ($bloques as $bloque) {
                $fechaRecaudacion = $bloque['fecha_recaudacion'];
                $rucEmisor        = $bloque['ruc_emisor'];
                $razonSocial      = $bloque['razon_social'];
                $porcentaje       = $bloque['porcentaje'];

                foreach ($bloque['facturas'] as $fila) {
                    $serie    = trim((string) ($fila['serie']   ?? ''));
                    $numeroRaw = trim((string) ($fila['numero'] ?? ''));
                    $numero   = (int) preg_replace('/\D/', '', $numeroRaw);

                    if (empty($serie) || $numero <= 0) {
                        $omitidas++;
                        continue;
                    }

                    $importe         = $this->parseMonto($fila['importe_total']);
                    $totalRetencion  = $this->parseMonto($fila['total_recaudacion']);
                    $importePagado   = $this->parseMonto($fila['importe_pagado']);
                    $fechaEmision    = $this->parsearFecha($fila['fecha_emision']);

                    // Buscar la factura en la BD por serie y número
                    $factura = DB::table('factura')
                        ->where('serie',  $serie)
                        ->where('numero', $numero)
                        ->first();

                    if (!$factura) {
                        $noEncontradas++;
                        $errores[] = "No encontrada: {$serie}-" . str_pad($numero, 8, '0', STR_PAD_LEFT)
                            . " ({$razonSocial})";
                        $resultados[] = [
                            'serie'            => $serie,
                            'numero'           => str_pad($numero, 8, '0', STR_PAD_LEFT),
                            'emisor'           => $razonSocial,
                            'importe'          => $importe,
                            'retencion'        => $totalRetencion,
                            'fecha_emision'    => $fechaEmision,
                            'fecha_recaudacion'=> $fechaRecaudacion,
                            'estado_anterior'  => null,
                            'estado_nuevo'     => null,
                            'accion'           => 'NO_ENCONTRADA',
                        ];
                        continue;
                    }

                    $estadoActual = $factura->estado;

                    // Si ya está PAGADA, solo actualizar recaudación si no existe
                    // Para estados pendientes: actualizar recaudación y dejar en PENDIENTE
                    // (el pago real debe validarse manualmente, como indica el usuario)

                    // Upsert en tabla recaudacion
                    DB::table('recaudacion')->updateOrInsert(
                        ['id_factura' => $factura->id_factura],
                        [
                            'porcentaje'        => $porcentaje,
                            'total_recaudacion' => $totalRetencion,
                            'fecha_recaudacion' => $fechaRecaudacion,
                        ]
                    );

                    // Recalcular monto pendiente
                    $montoAbonado   = (float) ($factura->monto_abonado ?? 0);
                    $importeTotal   = (float) ($factura->importe_total ?? $importe);
                    $montoPendiente = max(0, $importeTotal - $montoAbonado - $totalRetencion);

                    // Determinar nuevo estado:
                    //   - Si ya está PAGADA → no tocar
                    //   - Si el importe pagado del Excel == importe total → PAGO PARCIAL (pendiente validación)
                    //   - Resto → PENDIENTE (mantener o actualizar tipo_recaudacion)
                    $nuevoEstado = $estadoActual;
                    if (!in_array($estadoActual, ['PAGADA', 'ANULADO'])) {
                        // Registramos la retención pero NO marcamos como pagada automáticamente.
                        // El usuario debe validar manualmente el pago real.
                        // Solo actualizamos tipo_recaudacion = RETENCION.
                        $nuevoEstado = $estadoActual; // sin cambio de estado
                    }

                    DB::table('factura')
                        ->where('id_factura', $factura->id_factura)
                        ->update([
                            'tipo_recaudacion'    => 'RETENCION',
                            'monto_pendiente'     => $montoPendiente,
                            'fecha_actualizacion' => now(),
                        ]);

                    $procesadas++;
                    $resultados[] = [
                        'serie'            => $serie,
                        'numero'           => str_pad($numero, 8, '0', STR_PAD_LEFT),
                        'emisor'           => $razonSocial,
                        'importe'          => $importeTotal,
                        'retencion'        => $totalRetencion,
                        'importe_pagado'   => $importePagado,
                        'fecha_emision'    => $fechaEmision,
                        'fecha_recaudacion'=> $fechaRecaudacion,
                        'estado_anterior'  => $estadoActual,
                        'estado_nuevo'     => $nuevoEstado,
                        'accion'           => 'RETENCION_REGISTRADA',
                    ];
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error',
                'Error al procesar: ' . $e->getMessage() .
                ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']'
            )->with('resumen_tipo', 'retencion')->withInput();
        }

        return redirect()->route('facturas.importar')->with('resumen', [
            'procesadas'    => $procesadas,
            'duplicadas'    => $duplicadas,
            'omitidas'      => $omitidas,
            'no_encontradas'=> $noEncontradas,
            'errores'       => $errores,
            'resultados'    => $resultados,
        ])->with('resumen_tipo', 'retencion');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recorre todas las filas del Excel e identifica los bloques de retención.
     * Cada bloque tiene:
     *   - fecha_recaudacion : fecha del comprobante de retención (col I de fila "Fecha de emisión:")
     *   - ruc_emisor        : RUC del emisor
     *   - razon_social      : razón social del emisor
     *   - porcentaje        : tasa de retención
     *   - facturas[]        : filas de comprobantes afectados
     */
    private function extraerBloques(array $rows): array
    {
        $bloques = [];
        $totalRows = count($rows);
        $i = 0;

        while ($i < $totalRows) {
            $row = $rows[$i];

            // Detectar fila de "Emisor:" → col B (índice 1) = "Emisor:"
            $colB = trim((string) ($row[1] ?? ''));

            if (strtolower($colB) === 'emisor:') {
                // Fila Emisor (i)
                $emisorTexto = trim((string) ($row[2] ?? ''));
                [$rucEmisor, $razonSocial] = $this->parsearEmisor($emisorTexto);

                // Fila Receptor (i+1) → ignorada
                // Fila Importe Total Retenido (i+2) → col E = tasa %
                $filaTasa    = $rows[$i + 2] ?? [];
                $porcentaje  = (float) trim((string) ($filaTasa[5] ?? '0'));

                // Fila "Fecha de emisión:" del comprobante retención (i+1) col H/I
                // En el Excel: fila Receptor tiene col H = "Fecha de emisión:" y col I = fecha
                $filaReceptor       = $rows[$i + 1] ?? [];
                $fechaRecaudacion   = $this->parsearFecha($filaReceptor[8] ?? null);

                // Saltamos: Emisor(i), Receptor(i+1), Importe(i+2), [vacía(i+3)], Cabecera(i+4)
                // Las filas de facturas empiezan en i+5 (puede variar si hay fila vacía entre cabecera y datos)
                $j = $i + 3;

                // Saltar fila de cabeceras (contiene "Tipo de documento")
                while ($j < $totalRows) {
                    $testCol = strtolower(trim((string) ($rows[$j][0] ?? '')));
                    if ($testCol === 'tipo de documento') {
                        $j++;
                        break;
                    }
                    $j++;
                }

                // Recolectar filas de facturas hasta encontrar fila de subtotal o nuevo bloque
                $facturas = [];
                while ($j < $totalRows) {
                    $rowJ = $rows[$j];
                    $tipoDoc = strtolower(trim((string) ($rowJ[0] ?? '')));

                    // Fila de factura real
                    if (in_array($tipoDoc, ['factura', 'boleta', 'nota de crédito', 'nota de debito'])) {
                        $facturas[] = [
                            'serie'            => strtoupper(trim((string) ($rowJ[1] ?? ''))),
                            'numero'           => trim((string) ($rowJ[2] ?? '')),
                            'fecha_emision'    => $rowJ[3] ?? null,
                            'importe_total'    => $rowJ[4] ?? 0,
                            'importe_pagado'   => $rowJ[6] ?? 0,
                            'total_recaudacion'=> $rowJ[7] ?? 0,
                        ];
                        $j++;
                        continue;
                    }

                    // Fila de subtotal (solo col H tiene valor numérico, A está vacía)
                    $colA = trim((string) ($rowJ[0] ?? ''));
                    $colH = $rowJ[7] ?? null;
                    if (empty($colA) && is_numeric($colH)) {
                        $j++;
                        break;
                    }

                    // Fila de "TOTAL DE RETENCIONES" → fin del archivo
                    $colE = strtolower(trim((string) ($rowJ[4] ?? '')));
                    if (str_contains($colE, 'total de retenciones')) {
                        break 2;
                    }

                    $j++;
                }

                if (!empty($facturas)) {
                    $bloques[] = [
                        'ruc_emisor'       => $rucEmisor,
                        'razon_social'     => $razonSocial,
                        'porcentaje'       => $porcentaje,
                        'fecha_recaudacion'=> $fechaRecaudacion,
                        'facturas'         => $facturas,
                    ];
                }

                $i = $j;
                continue;
            }

            $i++;
        }

        return $bloques;
    }

    /**
     * Extrae RUC y razón social del texto "RUC 20123456789 - NOMBRE EMPRESA S.A.C."
     */
    private function parsearEmisor(string $texto): array
    {
        // Quitar prefijo "RUC" y espacios extra
        $texto = preg_replace('/\s+/', ' ', trim($texto));

        if (preg_match('/RUC\s+(\d{11})\s*-\s*(.+)/i', $texto, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        // Fallback: sin RUC reconocible
        return ['', $texto];
    }

    /**
     * Convierte valor del Excel a float (soporta "S/ 1234.56", "1,234.56", 1234.56).
     */
    private function parseMonto(mixed $v): float
    {
        if (is_int($v) || is_float($v)) return abs((float) $v);
        $s = trim((string) $v);
        if ($s === '') return 0.0;
        // Quitar símbolos de moneda y espacios
        $s = preg_replace('/[S\/\$\s]/i', '', $s);
        // Normalizar separadores: si tiene coma como decimal
        if (preg_match('/,\d{1,2}$/', $s)) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        } else {
            $s = str_replace(',', '', $s);
        }
        $s = preg_replace('/[^0-9.\-]/', '', $s);
        return abs((float) $s);
    }

    /**
     * Convierte fecha del Excel (string DD/MM/YYYY o número serial) a "Y-m-d".
     */
    private function parsearFecha(mixed $v): ?string
    {
        if (empty($v)) return null;

        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }

        if (is_numeric($v)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m-d');
            } catch (\Throwable) {}
        }

        $s = trim((string) $v);

        // DD/MM/YYYY
        try { return Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d'); } catch (\Throwable) {}
        // YYYY-MM-DD
        try { return Carbon::parse($s)->format('Y-m-d'); } catch (\Throwable) {}

        return null;
    }
}
