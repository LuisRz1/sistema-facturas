<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

/**
 * Importa el Excel oficial de Retenciones SUNAT.
 *
 * FLUJO:
 *   1. POST /facturas/importar-retenciones/procesar  → parsea Excel, guarda resultado en sesión, redirige a vista
 *   2. Vista muestra tabla EDITABLE de los resultados detectados
 *   3. POST /facturas/importar-retenciones/confirmar → toma los datos editados y los aplica a la BD
 */
class ImportarRetencionesController extends Controller
{
    // ── PASO 1: Parsear Excel y guardar preview en sesión ─────────────────
    public function importar(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $request->validate(['archivo' => 'required|file|max:10240']);

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

        $hoja  = $spreadsheet->getActiveSheet();
        $rows  = $hoja->toArray(null, true, false, false);
        $bloques = $this->extraerBloques($rows);

        $preview = [];

        foreach ($bloques as $bloque) {
            $fechaRecaudacion = $bloque['fecha_recaudacion'];
            $razonSocial      = $bloque['razon_social'];
            $rucEmisor        = $bloque['ruc_emisor'];
            $porcentaje       = $bloque['porcentaje'];

            foreach ($bloque['facturas'] as $fila) {
                $serie   = strtoupper(trim((string)($fila['serie']  ?? '')));
                $numero  = (int) preg_replace('/\D/', '', trim((string)($fila['numero'] ?? '')));
                $totalRetencion = $this->parseMonto($fila['total_recaudacion']);
                $importePagado  = $this->parseMonto($fila['importe_pagado']);
                $fechaEmision   = $this->parsearFecha($fila['fecha_emision']);
                $importeExcel   = $this->parseMonto($fila['importe_total']);

                // Try to find the factura
                $factura       = ($serie && $numero > 0) ? $this->buscarFactura($serie, $numero) : null;
                $serieRealEnDB = $factura ? $factura->serie : null;

                $preview[] = [
                    // From Excel (editable)
                    'serie_excel'      => $serie,
                    'numero_excel'     => $numero > 0 ? str_pad($numero, 8, '0', STR_PAD_LEFT) : '',
                    'fecha_emision'    => $fechaEmision,
                    'fecha_recaudacion'=> $fechaRecaudacion,
                    'importe_excel'    => $importeExcel,
                    'total_retencion'  => $totalRetencion,
                    'importe_pagado'   => $importePagado,
                    'porcentaje'       => $porcentaje,
                    'emisor'           => $razonSocial,
                    'ruc_emisor'       => $rucEmisor,
                    // Found in DB
                    'id_factura'       => $factura?->id_factura,
                    'serie_db'         => $serieRealEnDB,
                    'estado_actual'    => $factura?->estado,
                    'importe_db'       => $factura?->importe_total,
                    // Status
                    'encontrada'       => $factura !== null,
                ];
            }
        }

        // Store in session for the confirm step
        session(['ret_preview' => $preview]);

        return redirect()->route('facturas.importar')
            ->with('ret_preview', $preview)
            ->with('resumen_tipo', 'retencion_preview');
    }

    // ── PASO 2: Confirmar y aplicar los datos editados ────────────────────
    public function confirmar(Request $request)
    {
        $filas = $request->input('filas', []);

        if (empty($filas)) {
            return back()->with('error', 'No hay filas para confirmar.');
        }

        $procesadas      = 0;
        $duplicadas      = 0;
        $noEncontradas   = 0;
        $clientesCreados = 0;
        $facturasCreadas = 0;
        $errores         = [];
        $resultados      = [];
        $facturasVistas  = [];

        $idUsuarioActual = Auth::id();
        if (!$idUsuarioActual) {
            $idUsuarioActual = DB::table('usuario')->min('id_usuario');
        }

        DB::beginTransaction();
        try {
            foreach ($filas as $idx => $fila) {
                // Skip rows marked to skip
                if (!empty($fila['omitir'])) continue;

                $serie  = strtoupper(trim($fila['serie']  ?? ''));
                $numero = (int) preg_replace('/\D/', '', $fila['numero'] ?? '');

                if (empty($serie) || $numero <= 0) continue;

                $totalRetencion   = (float)($fila['total_retencion']   ?? 0);
                $importePagado    = (float)($fila['importe_pagado']     ?? 0);
                $importeExcel     = (float)($fila['importe_excel']      ?? 0);
                $porcentaje       = (float)($fila['porcentaje']         ?? 0);
                $fechaRecaudacion = $fila['fecha_recaudacion'] ?: null;
                $fechaEmision     = $fila['fecha_emision'] ?: null;
                $rucEmisor        = trim($fila['ruc_emisor']   ?? '');
                $razonSocial      = trim($fila['emisor']       ?? '');
                $facturaCreadaEnFila = false;

                $claveFactura = strtoupper($serie) . '-' . str_pad((string)$numero, 8, '0', STR_PAD_LEFT);
                if (isset($facturasVistas[$claveFactura])) {
                    $duplicadas++;
                    $resultados[] = [
                        'serie'             => $serie,
                        'numero'            => str_pad($numero, 8, '0', STR_PAD_LEFT),
                        'emisor'            => $razonSocial,
                        'ruc_emisor'        => $rucEmisor,
                        'importe_excel'     => $importeExcel,
                        'accion'            => 'DUPLICADA_EN_ARCHIVO',
                        'retencion'         => $totalRetencion,
                        'importe_pagado'    => $importePagado,
                        'fecha_emision'     => $fechaEmision,
                        'fecha_recaudacion' => $fechaRecaudacion,
                        'estado_anterior'   => null,
                    ];
                    continue;
                }
                $facturasVistas[$claveFactura] = true;

                // Create/find client by RUC if present.
                $idCliente = null;
                if (!empty($rucEmisor)) {
                    $clienteExistente = DB::table('cliente')->where('ruc', $rucEmisor)->first();
                    if (!$clienteExistente) {
                        $idCliente = DB::table('cliente')->insertGetId([
                            'ruc'            => $rucEmisor,
                            'razon_social'   => $razonSocial,
                            'estado_contado' => 'SIN_DATOS',
                            'fecha_creacion' => now(),
                        ]);
                        $clientesCreados++;
                    } else {
                        $idCliente = $clienteExistente->id_cliente;
                    }
                }

                if (!$idCliente) {
                    $idCliente = DB::table('cliente')->where('ruc', '00000000000')->value('id_cliente')
                        ?: DB::table('cliente')->min('id_cliente');

                    if (!$idCliente) {
                        $idCliente = DB::table('cliente')->insertGetId([
                            'ruc'            => '00000000000',
                            'razon_social'   => 'CLIENTE GENERICO',
                            'estado_contado' => 'SIN_DATOS',
                            'fecha_creacion' => now(),
                        ]);
                        $clientesCreados++;
                    }
                }

                // Find factura (may have been corrected in the edit form)
                $factura = $this->buscarFactura($serie, $numero);

                if (!$factura) {
                    $factura = $this->crearFacturaDesdeRetencion(
                        serie: $serie,
                        numero: $numero,
                        fechaEmision: $fechaEmision,
                        importeExcel: $importeExcel,
                        totalRetencion: $totalRetencion,
                        idCliente: $idCliente,
                        idUsuario: $idUsuarioActual,
                    );

                    if (!$factura) {
                        $noEncontradas++;
                        $errores[] = "No se pudo crear factura: {$serie}-" . str_pad($numero, 8, '0', STR_PAD_LEFT) . " ({$razonSocial})";
                        $resultados[] = [
                            'serie'             => $serie,
                            'numero'            => str_pad($numero, 8, '0', STR_PAD_LEFT),
                            'emisor'            => $razonSocial,
                            'ruc_emisor'        => $rucEmisor,
                            'importe_excel'     => $importeExcel,
                            'accion'            => 'NO_IMPORTADA',
                            'retencion'         => $totalRetencion,
                            'importe_pagado'    => $importePagado,
                            'fecha_emision'     => $fechaEmision,
                            'fecha_recaudacion' => $fechaRecaudacion,
                            'estado_anterior'   => null,
                        ];
                        continue;
                    }

                    $facturasCreadas++;
                    $facturaCreadaEnFila = true;
                }

                if (!$facturaCreadaEnFila) {
                    $recaudacionExistente = DB::table('recaudacion')
                        ->where('id_factura', $factura->id_factura)
                        ->exists();

                    if ($recaudacionExistente) {
                        $duplicadas++;
                        $resultados[] = [
                            'serie'             => $factura->serie,
                            'numero'            => str_pad($numero, 8, '0', STR_PAD_LEFT),
                            'emisor'            => $razonSocial,
                            'ruc_emisor'        => $rucEmisor,
                            'importe'           => (float)($factura->importe_total ?? 0),
                            'importe_excel'     => $importeExcel,
                            'accion'            => 'DUPLICADA_EXISTENTE',
                            'retencion'         => $totalRetencion,
                            'importe_pagado'    => $importePagado,
                            'fecha_emision'     => $fechaEmision,
                            'fecha_recaudacion' => $fechaRecaudacion,
                            'estado_anterior'   => $factura->estado,
                        ];
                        continue;
                    }
                }

                $importeFactura = (float) ($factura->importe_total ?? 0);
                if ($importeFactura <= 0 && $importeExcel > 0) {
                    $importeFactura = $importeExcel;
                    DB::table('factura')
                        ->where('id_factura', $factura->id_factura)
                        ->update([
                            'importe_total'       => $importeExcel,
                            'fecha_actualizacion' => now(),
                        ]);
                    $factura->importe_total = $importeExcel;
                }

                // Upsert recaudacion
                DB::table('recaudacion')->updateOrInsert(
                    ['id_factura' => $factura->id_factura],
                    [
                        'porcentaje'        => $porcentaje,
                        'total_recaudacion' => $totalRetencion,
                        'fecha_recaudacion' => $fechaRecaudacion,
                    ]
                );

                $montoPendiente = max(0, $importeFactura - (float)$factura->monto_abonado - $totalRetencion);
                $estadoNuevo = $this->calcularEstadoRetencion(
                    factura: $factura,
                    montoPendiente: $montoPendiente,
                    totalRetencion: $totalRetencion,
                    fechaRecaudacion: $fechaRecaudacion,
                );

                DB::table('factura')
                    ->where('id_factura', $factura->id_factura)
                    ->update([
                        'tipo_recaudacion'    => 'RETENCION',
                        'monto_pendiente'     => $montoPendiente,
                        'estado'              => $estadoNuevo,
                        'fecha_actualizacion' => now(),
                    ]);

                $procesadas++;
                $resultados[] = [
                    'serie'              => $factura->serie,
                    'numero'             => str_pad($numero, 8, '0', STR_PAD_LEFT),
                    'emisor'             => $razonSocial,
                    'importe'            => $importeFactura,
                    'importe_excel'      => $importeExcel,
                    'retencion'          => $totalRetencion,
                    'importe_pagado'     => $importePagado,
                    'fecha_emision'      => $fechaEmision,
                    'fecha_recaudacion'  => $fechaRecaudacion,
                    'estado_anterior'    => $factura->estado,
                    'estado_nuevo'       => $estadoNuevo,
                    'accion'             => $facturaCreadaEnFila
                        ? 'FACTURA_CREADA_Y_RETENCION_REGISTRADA'
                        : 'RETENCION_REGISTRADA',
                    'serie_real'         => ($factura->serie !== $serie) ? $factura->serie : null,
                    'ruc_emisor'         => $rucEmisor,
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar: ' . $e->getMessage())->withInput();
        }

        session()->forget('ret_preview');

        return redirect()->route('facturas.importar')->with('resumen', [
            'procesadas'       => $procesadas,
            'duplicadas'       => $duplicadas,
            'omitidas'         => 0,
            'no_encontradas'   => $noEncontradas,
            'clientes_creados' => $clientesCreados,
            'facturas_creadas' => $facturasCreadas,
            'errores'          => $errores,
            'resultados'       => $resultados,
        ])->with('resumen_tipo', 'retencion');
    }

    private function crearFacturaDesdeRetencion(
        string $serie,
        int $numero,
        ?string $fechaEmision,
        float $importeExcel,
        float $totalRetencion,
        ?int $idCliente,
        ?int $idUsuario
    ): ?object {
        if (!$idCliente || !$idUsuario) {
            return null;
        }

        $importeTotal = $importeExcel > 0 ? $importeExcel : max(0, $totalRetencion);
        $montoPendiente = max(0, $importeTotal - $totalRetencion);

        $idFactura = DB::table('factura')->insertGetId([
            'serie'             => $serie,
            'numero'            => $numero,
            'tipo_operacion'    => 'VENTA',
            'id_cliente'        => $idCliente,
            'id_usuario'        => $idUsuario,
            'moneda'            => 'PEN',
            'subtotal_gravado'  => max(0, $importeTotal / 1.18),
            'monto_igv'         => max(0, $importeTotal - ($importeTotal / 1.18)),
            'importe_total'     => $importeTotal,
            'estado'            => $montoPendiente > 0 ? 'PENDIENTE' : 'PAGADA',
            'glosa'             => 'FACTURA CREADA DESDE IMPORTACION RETENCIONES',
            'forma_pago'        => 'TRANSFERENCIA',
            'tipo_recaudacion'  => 'RETENCION',
            'fecha_vencimiento' => $fechaEmision,
            'fecha_emision'     => $fechaEmision,
            'fecha_creacion'    => now(),
            'usuario_creacion'  => $idUsuario,
            'monto_abonado'     => 0,
            'monto_pendiente'   => $montoPendiente,
        ]);

        return DB::table('factura')->where('id_factura', $idFactura)->first();
    }

    private function calcularEstadoRetencion(
        object $factura,
        float $montoPendiente,
        float $totalRetencion,
        ?string $fechaRecaudacion
    ): string {
        $retencionRegistrada = $totalRetencion > 0 && !empty($fechaRecaudacion);
        if ($retencionRegistrada) {
            return $montoPendiente <= 0 ? 'PAGADA' : 'DIFERENCIA PENDIENTE';
        }

        $montoAbonado = (float)($factura->monto_abonado ?? 0);
        if ($montoAbonado <= 0) {
            if (!empty($factura->fecha_vencimiento) && $factura->fecha_vencimiento < now()->toDateString()) {
                return 'VENCIDO';
            }
            return 'PENDIENTE';
        }

        return $montoPendiente <= 0 ? 'PAGADA' : 'PAGO PARCIAL';
    }

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function buscarFactura(string $serie, int $numero): ?object
    {
        $f = DB::table('factura')->where('serie', $serie)->where('numero', $numero)->first();
        if ($f) return $f;

        foreach ($this->generarVariantesSerie($serie) as $v) {
            $f = DB::table('factura')->where('serie', $v)->where('numero', $numero)->first();
            if ($f) return $f;
        }

        return DB::table('factura')
            ->whereRaw('UPPER(serie) = ?', [strtoupper($serie)])
            ->where('numero', $numero)
            ->first() ?: null;
    }

    private function generarVariantesSerie(string $serie): array
    {
        $variantes = [];
        if (!preg_match('/^([A-Z]+)(\d+)$/', strtoupper($serie), $m)) return $variantes;
        $letras = $m[1]; $num = (int)$m[2];
        $altLetras = [$letras];
        if (strlen($letras) >= 2 && count(array_unique(str_split($letras))) === 1) {
            $altLetras[] = $letras[0];
        } else {
            $altLetras[] = str_repeat($letras, 2);
        }
        $pads = array_unique([(string)$num, str_pad($num,2,'0',STR_PAD_LEFT), str_pad($num,3,'0',STR_PAD_LEFT), str_pad($num,4,'0',STR_PAD_LEFT)]);
        foreach ($altLetras as $alt) {
            foreach ($pads as $pad) {
                $v = $alt . $pad;
                if ($v !== strtoupper($serie)) $variantes[] = $v;
            }
        }
        return array_unique($variantes);
    }

    private function extraerBloques(array $rows): array
    {
        $bloques = []; $totalRows = count($rows); $i = 0;
        while ($i < $totalRows) {
            $row  = $rows[$i];
            $colB = strtolower(trim((string)($row[1] ?? '')));
            if ($colB === 'emisor:') {
                $emisorTexto = trim((string)($row[2] ?? ''));
                [$rucEmisor, $razonSocial] = $this->parsearEmisor($emisorTexto);
                $filaReceptor     = $rows[$i + 1] ?? [];
                $fechaRecaudacion = $this->parsearFecha($filaReceptor[8] ?? null);
                $filaTasa   = $rows[$i + 2] ?? [];
                $porcentaje = (float)trim((string)($filaTasa[5] ?? '0'));
                $j = $i + 3;
                while ($j < $totalRows) {
                    if (str_contains(strtolower(trim((string)($rows[$j][0] ?? ''))), 'tipo')) { $j++; break; }
                    $j++;
                }
                $facturas = [];
                while ($j < $totalRows) {
                    $rowJ    = $rows[$j];
                    $tipoDoc = strtolower(trim((string)($rowJ[0] ?? '')));
                    if (in_array($tipoDoc, ['factura','boleta','nota de crédito','nota de credito','nota de debito','nota de débito'])) {
                        $facturas[] = [
                            'serie'             => strtoupper(trim((string)($rowJ[1] ?? ''))),
                            'numero'            => trim((string)($rowJ[2] ?? '')),
                            'fecha_emision'     => $rowJ[3] ?? null,
                            'importe_total'     => $rowJ[4] ?? 0,
                            'importe_pagado'    => $rowJ[6] ?? 0,
                            'total_recaudacion' => $rowJ[7] ?? 0,
                        ];
                        $j++; continue;
                    }
                    $colA    = trim((string)($rowJ[0] ?? ''));
                    $colH    = $rowJ[7] ?? null;
                    $colHStr = trim((string)($colH ?? ''));
                    if (empty($colA) && $colH !== null && $colH !== '' && (is_numeric($colH) || str_starts_with($colHStr, '='))) { $j++; break; }
                    if (str_contains(strtolower(trim((string)($rowJ[4] ?? ''))), 'total de retenciones')) break 2;
                    $j++;
                }
                if (!empty($facturas)) {
                    $bloques[] = ['ruc_emisor'=>$rucEmisor,'razon_social'=>$razonSocial,'porcentaje'=>$porcentaje,'fecha_recaudacion'=>$fechaRecaudacion,'facturas'=>$facturas];
                }
                $i = $j; continue;
            }
            $i++;
        }
        return $bloques;
    }

    private function parsearEmisor(string $texto): array
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));
        if (preg_match('/RUC\s+(\d{11})\s*-\s*(.+)/i', $texto, $m)) return [trim($m[1]), trim($m[2])];
        return ['', $texto];
    }

    private function parseMonto(mixed $v): float
    {
        if (is_int($v) || is_float($v)) return abs((float)$v);
        $s = trim((string)$v);
        if ($s === '') return 0.0;
        $s = preg_replace('/[S\/\$\s]/i', '', $s);
        if (preg_match('/,\d{1,2}$/', $s)) { $s = str_replace(['.', ','], ['', '.'], $s); }
        else { $s = str_replace(',', '', $s); }
        return abs((float)preg_replace('/[^0-9.\-]/', '', $s));
    }

    private function parsearFecha(mixed $v): ?string
    {
        if (empty($v)) return null;
        if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d');
        if (is_numeric($v)) {
            try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$v)->format('Y-m-d'); } catch (\Throwable) {}
        }
        $s = trim((string)$v);
        try { return Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d'); } catch (\Throwable) {}
        try { return Carbon::parse($s)->format('Y-m-d'); } catch (\Throwable) {}
        return null;
    }
}
