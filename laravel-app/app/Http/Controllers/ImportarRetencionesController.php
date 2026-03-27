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
 * Por cada bloque de retención:
 *   Fila Emisor    → col B = "Emisor:"  col C = "RUC XXXXXXXX - NOMBRE EMPRESA"
 *   Fila Receptor  → col I (8) = fecha de pago de la retención (fecha_recaudacion)
 *   Fila Importe   → col F (5) = porcentaje tasa
 *   Cabecera       → "Tipo de documento | Serie | Número | ..."
 *   Filas factura  → hasta fila subtotal (col A vacía + col H con valor/fórmula)
 *
 * Columnas de cada fila de factura (0-based):
 *   1 = Serie, 2 = Número, 3 = Fecha emisión, 4 = Total comprobante,
 *   6 = Importe pagado (referencial), 7 = Retención S/
 */
class ImportarRetencionesController extends Controller
{
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

        // Sin calcular fórmulas para detectar filas subtotal (=6.37+26.77)
        $hoja = $spreadsheet->getActiveSheet();
        $rows = $hoja->toArray(null, true, false, false); // 0-indexed

        $procesadas      = 0;
        $noEncontradas   = 0;
        $clientesCreados = 0;
        $errores         = [];
        $resultados      = [];

        DB::beginTransaction();

        try {
            $bloques = $this->extraerBloques($rows);

            foreach ($bloques as $bloque) {
                $fechaRecaudacion = $bloque['fecha_recaudacion'];
                $razonSocial      = $bloque['razon_social'];
                $rucEmisor        = $bloque['ruc_emisor'];
                $porcentaje       = $bloque['porcentaje'];

                // ── Buscar o crear el cliente emisor usando el RUC del Excel ─────
                $idClienteEmisor = null;
                if (!empty($rucEmisor)) {
                    $clienteExistente = DB::table('cliente')->where('ruc', $rucEmisor)->first();
                    if ($clienteExistente) {
                        $idClienteEmisor = $clienteExistente->id_cliente;
                        // Actualizar razón social si cambió
                        if (!empty($razonSocial) && $clienteExistente->razon_social !== $razonSocial) {
                            DB::table('cliente')
                                ->where('id_cliente', $idClienteEmisor)
                                ->update([
                                    'razon_social'        => $razonSocial,
                                    'fecha_actualizacion' => now(),
                                ]);
                        }
                    } else {
                        // Crear nuevo cliente con los datos del emisor
                        $idClienteEmisor = DB::table('cliente')->insertGetId([
                            'ruc'            => $rucEmisor,
                            'razon_social'   => $razonSocial,
                            'estado_contado' => 'SIN_DATOS',
                            'fecha_creacion' => now(),
                        ]);
                        $clientesCreados++;
                    }
                }

                foreach ($bloque['facturas'] as $fila) {
                    $serie    = strtoupper(trim((string) ($fila['serie']   ?? '')));
                    $numeroRaw = trim((string) ($fila['numero'] ?? ''));
                    $numero   = (int) preg_replace('/\D/', '', $numeroRaw);

                    if (empty($serie) || $numero <= 0) {
                        continue;
                    }

                    $totalRetencion = $this->parseMonto($fila['total_recaudacion']);
                    $importePagado  = $this->parseMonto($fila['importe_pagado']);
                    $fechaEmision   = $this->parsearFecha($fila['fecha_emision']);

                    // ── Buscar factura: exacto → variantes de serie ───────────────
                    $factura       = $this->buscarFactura($serie, $numero);
                    $serieRealEnDB = $factura ? $factura->serie : null;

                    if (!$factura) {
                        $noEncontradas++;
                        $errores[] = "No encontrada: {$serie}-" . str_pad($numero, 8, '0', STR_PAD_LEFT)
                            . " ({$razonSocial})";
                        $resultados[] = [
                            'serie'            => $serie,
                            'numero'           => str_pad($numero, 8, '0', STR_PAD_LEFT),
                            'emisor'           => $razonSocial,
                            'ruc_emisor'       => $rucEmisor,
                            'importe'          => null,
                            'retencion'        => $totalRetencion,
                            'importe_pagado'   => $importePagado,
                            'fecha_emision'    => $fechaEmision,
                            'fecha_recaudacion'=> $fechaRecaudacion,
                            'estado_anterior'  => null,
                            'estado_nuevo'     => null,
                            'accion'           => 'NO_ENCONTRADA',
                            'serie_real'       => null,
                        ];
                        continue;
                    }

                    $estadoActual = $factura->estado;
                    $importeTotal = (float) ($factura->importe_total ?? 0);
                    $montoAbonado = (float) ($factura->monto_abonado ?? 0);

                    // ── Upsert en tabla recaudacion ──────────────────────────────
                    DB::table('recaudacion')->updateOrInsert(
                        ['id_factura' => $factura->id_factura],
                        [
                            'porcentaje'        => $porcentaje,
                            'total_recaudacion' => $totalRetencion,
                            'fecha_recaudacion' => $fechaRecaudacion,
                        ]
                    );

                    // Recalcular monto pendiente
                    $montoPendiente = max(0, $importeTotal - $montoAbonado - $totalRetencion);

                    // Actualizar factura: solo tipo_recaudacion y monto_pendiente
                    // NO se cambia el estado (requiere validación manual del pago)
                    DB::table('factura')
                        ->where('id_factura', $factura->id_factura)
                        ->update([
                            'tipo_recaudacion'    => 'RETENCION',
                            'monto_pendiente'     => $montoPendiente,
                            'fecha_actualizacion' => now(),
                        ]);

                    $procesadas++;
                    $resultados[] = [
                        'serie'             => $factura->serie,
                        'numero'            => str_pad($numero, 8, '0', STR_PAD_LEFT),
                        'emisor'            => $razonSocial,
                        'ruc_emisor'        => $rucEmisor,
                        'importe'           => $importeTotal,
                        'retencion'         => $totalRetencion,
                        'importe_pagado'    => $importePagado,
                        'fecha_emision'     => $fechaEmision,
                        'fecha_recaudacion' => $fechaRecaudacion,
                        'estado_anterior'   => $estadoActual,
                        'estado_nuevo'      => $estadoActual,
                        'accion'            => 'RETENCION_REGISTRADA',
                        // Si la serie en DB difiere de la del Excel, mostramos nota
                        'serie_real'        => ($serieRealEnDB !== $serie) ? $serieRealEnDB : null,
                    ];
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error',
                'Error al procesar: ' . $e->getMessage() .
                ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']'
            )->withInput();
        }

        return redirect()->route('facturas.importar')->with('resumen', [
            'procesadas'       => $procesadas,
            'duplicadas'       => 0,
            'omitidas'         => 0,
            'no_encontradas'   => $noEncontradas,
            'clientes_creados' => $clientesCreados,
            'errores'          => $errores,
            'resultados'       => $resultados,
        ])->with('resumen_tipo', 'retencion');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Busca una factura por serie+numero con fallback a variantes de serie.
     * El Excel SUNAT puede usar "FF01" mientras el sistema tiene "F001", etc.
     */
    private function buscarFactura(string $serie, int $numero): ?object
    {
        // 1. Búsqueda exacta
        $f = DB::table('factura')->where('serie', $serie)->where('numero', $numero)->first();
        if ($f) return $f;

        // 2. Variantes de la serie
        foreach ($this->generarVariantesSerie($serie) as $variante) {
            $f = DB::table('factura')->where('serie', $variante)->where('numero', $numero)->first();
            if ($f) return $f;
        }

        // 3. Case-insensitive como último recurso
        return DB::table('factura')
            ->whereRaw('UPPER(serie) = ?', [strtoupper($serie)])
            ->where('numero', $numero)
            ->first() ?: null;
    }

    /**
     * Genera variantes de serie para tolerancia de formato.
     * FF01 → [F01, F001, F0001]  |  F001 → [FF01, FF001]  |  FF01 → [F01, F001]
     */
    private function generarVariantesSerie(string $serie): array
    {
        $variantes = [];
        $s = strtoupper($serie);

        if (!preg_match('/^([A-Z]+)(\d+)$/', $s, $m)) {
            return $variantes;
        }

        $letras  = $m[1];
        $digitos = $m[2];
        $num     = (int) $digitos;

        // Variantes de letras
        $altLetras = [$letras];
        if (strlen($letras) >= 2 && count(array_unique(str_split($letras))) === 1) {
            // FF → F, FFF → F
            $altLetras[] = $letras[0];
        } else {
            // F → FF
            $altLetras[] = str_repeat($letras, 2);
        }

        // Variantes de padding de dígitos
        $pads = array_unique([
            (string) $num,
            str_pad($num, 2, '0', STR_PAD_LEFT),
            str_pad($num, 3, '0', STR_PAD_LEFT),
            str_pad($num, 4, '0', STR_PAD_LEFT),
        ]);

        foreach ($altLetras as $alt) {
            foreach ($pads as $pad) {
                $v = $alt . $pad;
                if ($v !== $s) {
                    $variantes[] = $v;
                }
            }
        }

        return array_unique($variantes);
    }

    /**
     * Recorre todas las filas del Excel e identifica los bloques de retención.
     * Detección: fila donde col B (índice 1) == "emisor:"
     */
    private function extraerBloques(array $rows): array
    {
        $bloques   = [];
        $totalRows = count($rows);
        $i         = 0;

        while ($i < $totalRows) {
            $row  = $rows[$i];
            $colB = strtolower(trim((string) ($row[1] ?? '')));

            if ($colB === 'emisor:') {
                // RUC + razón social del emisor
                $emisorTexto = trim((string) ($row[2] ?? ''));
                [$rucEmisor, $razonSocial] = $this->parsearEmisor($emisorTexto);

                // Receptor (i+1) → col I (8) = fecha de pago de la retención
                $filaReceptor     = $rows[$i + 1] ?? [];
                $fechaRecaudacion = $this->parsearFecha($filaReceptor[8] ?? null);

                // Importe (i+2) → col F (5) = porcentaje tasa
                $filaTasa   = $rows[$i + 2] ?? [];
                $porcentaje = (float) trim((string) ($filaTasa[5] ?? '0'));

                // Avanzar hasta la cabecera de facturas
                $j = $i + 3;
                while ($j < $totalRows) {
                    $testCol = strtolower(trim((string) ($rows[$j][0] ?? '')));
                    if (str_contains($testCol, 'tipo')) {
                        $j++;
                        break;
                    }
                    $j++;
                }

                // Recolectar filas de facturas hasta subtotal o nuevo bloque
                $facturas = [];
                while ($j < $totalRows) {
                    $rowJ    = $rows[$j];
                    $tipoDoc = strtolower(trim((string) ($rowJ[0] ?? '')));

                    if (in_array($tipoDoc, [
                        'factura', 'boleta', 'nota de crédito', 'nota de credito',
                        'nota de debito', 'nota de débito',
                    ])) {
                        $facturas[] = [
                            'serie'             => strtoupper(trim((string) ($rowJ[1] ?? ''))),
                            'numero'            => trim((string) ($rowJ[2] ?? '')),
                            'fecha_emision'     => $rowJ[3] ?? null,
                            'importe_total'     => $rowJ[4] ?? 0,
                            'importe_pagado'    => $rowJ[6] ?? 0,
                            'total_recaudacion' => $rowJ[7] ?? 0,
                        ];
                        $j++;
                        continue;
                    }

                    // Subtotal: col A vacía + col H con número o fórmula
                    $colA    = trim((string) ($rowJ[0] ?? ''));
                    $colH    = $rowJ[7] ?? null;
                    $colHStr = trim((string) ($colH ?? ''));
                    if (
                        empty($colA) && $colH !== null && $colH !== ''
                        && (is_numeric($colH) || str_starts_with($colHStr, '='))
                    ) {
                        $j++;
                        break;
                    }

                    // Fin del archivo
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
     * Extrae RUC y razón social de "RUC 20XXXXXXXXX - NOMBRE EMPRESA S.A.C."
     * Retorna ['20XXXXXXXXX', 'NOMBRE EMPRESA S.A.C.']
     */
    private function parsearEmisor(string $texto): array
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));

        if (preg_match('/RUC\s+(\d{11})\s*-\s*(.+)/i', $texto, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return ['', $texto];
    }

    /**
     * Convierte valor del Excel a float.
     * Soporta: "S/ 1234.56", "1,234.56", 1234.56
     */
    private function parseMonto(mixed $v): float
    {
        if (is_int($v) || is_float($v)) return abs((float) $v);
        $s = trim((string) $v);
        if ($s === '') return 0.0;
        $s = preg_replace('/[S\/\$\s]/i', '', $s);
        if (preg_match('/,\d{1,2}$/', $s)) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        } else {
            $s = str_replace(',', '', $s);
        }
        $s = preg_replace('/[^0-9.\-]/', '', $s);
        return abs((float) $s);
    }

    /**
     * Convierte fecha del Excel a "Y-m-d".
     * Soporta: "DD/MM/YYYY", número serial Excel, DateTimeInterface.
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

        try { return Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d'); } catch (\Throwable) {}
        try { return Carbon::parse($s)->format('Y-m-d'); } catch (\Throwable) {}

        return null;
    }
}
