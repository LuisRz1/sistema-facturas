<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ImportarFacturasController extends Controller
{
    public function index()
    {
        return view('facturas.importar');
    }

    public function importar(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $request->validate([
            'archivo'          => 'required|file|max:10240',
            'tipo_recaudacion' => 'required|in:DETRACCION,RETENCION',
        ], [
            'archivo.required'          => 'Selecciona un archivo Excel.',
            'tipo_recaudacion.required' => 'Selecciona el tipo de recaudación (Detracción o Retención).',
            'tipo_recaudacion.in'       => 'Tipo de recaudación inválido. Debe ser DETRACCION o RETENCION.',
        ]);

        $archivo   = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'])) {
            return back()->with('error', 'El archivo debe ser .xlsx o .xls')->withInput();
        }

        $tipoRecaudacion = $request->input('tipo_recaudacion');

        try {
            $spreadsheet = IOFactory::load($archivo->getPathname());
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el Excel: ' . $e->getMessage())->withInput();
        }

        $hoja  = $spreadsheet->getActiveSheet();
        $filas = $hoja->toArray(null, true, false, true);
        unset($filas[1]); // quitar cabecera

        $idUsuario  = Auth::id();
        $insertadas = 0;
        $omitidas   = 0;
        $duplicadas = 0;
        $errores    = [];
        $numFila    = null;

        DB::beginTransaction();

        try {
            foreach ($filas as $numFila => $f) {

                // ANULADO = SI → omitir
                if (strtoupper(trim((string)($f['AF'] ?? ''))) === 'SI') {
                    $omitidas++;
                    continue;
                }

                // Fila vacía
                if (empty($f['E']) && empty($f['F'])) continue;

                // ── Montos base ───────────────────────────────────────────
                $subtotalGravado  = $this->monto($f['P'] ?? 0);
                $montoIgv         = $this->monto($f['T'] ?? 0);
                $importeTotal     = $this->monto($f['Y'] ?? 0);
                $moneda           = trim((string)($f['N'] ?? 'PEN'));

                // Monto recaudación (columna AE) y porcentaje (columna AC)
                $montoRecaudacion = $this->monto($f['AE'] ?? 0);
                $porcentajeExcel  = $this->monto($f['AC'] ?? 0);

                // Si no hay monto en AE, no hay recaudación para esta fila
                if ($montoRecaudacion <= 0) {
                    $porcentajeExcel = 0;
                }

                // ── Verificar si esta fila tiene el tipo de recaudación ─────
                // Lee columna AI: si dice "SI", "DETRACCION", etc → aplica tipo
                // Si está vacía/NO → sin recaudación
                $indicadorExcel = strtoupper(trim((string)($f['AI'] ?? '')));
                $tieneIndicador = (strpos($indicadorExcel, 'SI') !== false || 
                                  strpos($indicadorExcel, 'DETRACCION') !== false ||
                                  strpos($indicadorExcel, 'RETENCION') !== false);

                // Aplicar tipo de recaudación SOLO si el indicador está presente
                $tipoRecaudacionFila = ($tieneIndicador && $montoRecaudacion > 0) 
                    ? $tipoRecaudacion 
                    : null;

                // ── Estado inicial ─────────────────────────────────────────
                // Todas las facturas importadas inician en PENDIENTE
                // (Detracción sin validar = monto pendiente es el total)
                $estado = 'PENDIENTE';

                // ── Glosa y fechas ─────────────────────────────────────────
                $glosa            = $this->transformarGlosa(trim((string)($f['AG'] ?? '')));
                $fechaEmision     = $this->parsearFecha($f['B'] ?? null);
                $fechaVencimiento = $this->parsearFecha($f['C'] ?? null);

                // ── Cliente: buscar o crear ────────────────────────────────
                $ruc         = trim((string)($f['J'] ?? ''));
                $razonSocial = trim((string)($f['K'] ?? ''));

                if (empty($ruc)) {
                    $errores[] = "Fila {$numFila}: sin RUC, omitida.";
                    $omitidas++;
                    continue;
                }

                $cliente = DB::table('cliente')->where('ruc', $ruc)->first();
                if (!$cliente) {
                    $idCliente = DB::table('cliente')->insertGetId([
                        'ruc'                => $ruc,
                        'razon_social'       => $razonSocial,
                        'estado_contado'     => 'SIN_DATOS',  // columna correcta en BD
                        'fecha_creacion'     => now(),
                    ]);
                } else {
                    $idCliente = $cliente->id_cliente;
                    if (!empty($razonSocial) && $cliente->razon_social !== $razonSocial) {
                        DB::table('cliente')->where('id_cliente', $idCliente)->update([
                            'razon_social'        => $razonSocial,
                            'fecha_actualizacion' => now(),
                        ]);
                    }
                }

                // ── Duplicado ──────────────────────────────────────────────
                $serie  = trim((string)($f['E'] ?? ''));
                $numero = (int) trim((string)($f['F'] ?? '0'));

                if (DB::table('factura')->where('serie', $serie)->where('numero', $numero)->exists()) {
                    $duplicadas++;
                    continue;
                }

                // ── DETECTAR NOTA DE CRÉDITO (serie FC01) ──────────────────
                $esNotaCredito = strtoupper($serie) === 'FC01';
                $serieModificada = null;
                $numeroModificada = null;

                if ($esNotaCredito) {
                    // Leer referencias de la factura que modifica
                    $serieModificada = strtoupper(trim((string)($f['CZ'] ?? '')));  // SERIE DOC MODIFICADO
                    $numeroModificada = (int) trim((string)($f['DA'] ?? '0'));     // NUMERO DOC MODIFICADO

                    // El importe de la nota de crédito es NEGATIVO
                    $importeTotal = -abs($importeTotal);
                    
                    // Si no tiene referencias pero es FC01 → igual se inserta en ANULADO
                    // para poder considerarla y usarla posteriormente
                }

                // ── Monto pendiente inicial ────────────────────────────────
                // Si el estado es PENDIENTE o VENCIDO, el pendiente es el importe total
                // porque aun no se ha validado la recaudacion/detraccion
                if (in_array($estado, ['PENDIENTE', 'VENCIDO'])) {
                    $montoPendiente = $importeTotal;
                } else {
                    $montoPendiente = max(0, $importeTotal - $montoRecaudacion);
                }

                // Para notas de crédito sin factura vinculada: estado = ANULADO
                $estadoFinal = $estado;
                if ($esNotaCredito && (empty($serieModificada) || $numeroModificada <= 0)) {
                    $estadoFinal = 'ANULADO';
                }

                // ── Insertar Factura ───────────────────────────────────────
                $idFactura = DB::table('factura')->insertGetId([
                    'serie'             => $serie,
                    'numero'            => $numero,
                    'tipo_operacion'    => trim((string)($f['H'] ?? '')),
                    'id_cliente'        => $idCliente,
                    'id_usuario'        => $idUsuario,
                    'moneda'            => $moneda,
                    'subtotal_gravado'  => $subtotalGravado,
                    'monto_igv'         => $montoIgv,
                    'importe_total'     => $importeTotal,
                    'estado'            => $estadoFinal,
                    'glosa'             => $glosa,
                    'forma_pago'        => trim((string)($f['AH'] ?? '')),
                    'tipo_recaudacion'  => $tipoRecaudacionFila,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'fecha_emision'     => $fechaEmision,
                    'fecha_creacion'    => now(),
                    'usuario_creacion'  => $idUsuario,
                    'monto_abonado'     => 0.00,
                    'monto_pendiente'   => $montoPendiente,
                ]);

                // ── Procesar nota de crédito si aplica ─────────────────────
                if ($esNotaCredito && !empty($serieModificada) && $numeroModificada > 0) {
                    // Insertar relación en tabla credito solo si tiene referencias válidas
                    DB::table('credito')->insert([
                        'id_factura'           => $idFactura,
                        'serie_doc_modificado' => $serieModificada,
                        'numero_doc_modificado'=> $numeroModificada,
                        'fecha_creacion'       => now(),
                    ]);

                    // Buscar la factura que está siendo anulada
                    $facturaModificada = DB::table('factura')
                        ->where('serie', $serieModificada)
                        ->where('numero', $numeroModificada)
                        ->first();

                    if ($facturaModificada) {
                        // Calcular nuevo pendiente de la factura modificada
                        $nuevoPendiente = $facturaModificada->monto_pendiente + $importeTotal; // suma porque importeTotal es negativo
                        $nuevoPendiente = max(0, $nuevoPendiente);

                        // Si el nuevo pendiente es 0, cambiar estado a ANULADO
                        $estadoNuevo = $nuevoPendiente <= 0 ? 'ANULADO' : $facturaModificada->estado;

                        DB::table('factura')
                            ->where('id_factura', $facturaModificada->id_factura)
                            ->update([
                                'monto_pendiente' => $nuevoPendiente,
                                'estado'          => $estadoNuevo,
                            ]);
                    }
                }

                // ── Insertar recaudación si aplica ─────────────────────────
                if ($montoRecaudacion > 0 && $tipoRecaudacionFila !== null) {
                    DB::table('recaudacion')->insert([
                        'id_factura'        => $idFactura,
                        'porcentaje'        => $porcentajeExcel,
                        'total_recaudacion' => $montoRecaudacion,
                    ]);
                }

                $insertadas++;
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error',
                'Error en fila ' . ($numFila ?? '?') . ': ' . $e->getMessage() .
                ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']'
            )->withInput();
        }

        return redirect()->route('facturas.importar')->with('resumen', [
            'insertadas'       => $insertadas,
            'omitidas'         => $omitidas,
            'duplicadas'       => $duplicadas,
            'errores'          => $errores,
            'tipo_recaudacion' => $tipoRecaudacion,
        ]);
    }

    // ── HELPERS ──────────────────────────────────────────────────────────────

    private function transformarGlosa(string $txt): string
    {
        if (empty($txt)) return '';
        $up = strtoupper($txt);

        if (str_contains($up, 'PLACA')) {
            if (preg_match('/PLACA\s*:?\s*([A-Z0-9]{3}[-]?[A-Z0-9]{3,4})/i', $txt, $m)) {
                return 'Alquiler de carro Placa: ' . strtoupper($m[1]);
            }
            return 'Alquiler de carro Placa: N/D';
        }
        if (str_contains($up, 'AGUA') && str_contains($up, 'TRANSPORT')) return 'Servicio de transporte de agua';
        if (str_contains($up, 'AGUA'))      return 'Suministro de Agua';
        if (str_contains($up, 'TRANSPORT')) return 'Servicio de transporte';
        if (str_contains($up, 'ALQUILER')) {
            if (preg_match('/ALQUILER\s+DE\s+([\wÁÉÍÓÚáéíóúÑñ]+)(?:\s+([\wÁÉÍÓÚáéíóúÑñ]+))?/iu', $txt, $m)) {
                $parte = ucfirst(strtolower($m[1]));
                if (!empty($m[2])) $parte .= ' ' . ucfirst(strtolower($m[2]));
                return 'Alquiler de ' . $parte;
            }
        }

        return trim(preg_replace('/\s+/', ' ', $txt));
    }

    private function monto(mixed $v): float
    {
        if (is_int($v) || is_float($v)) return abs($v);
        $s = trim((string)$v);
        if ($s === '') return 0.0;
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        $s = preg_replace('/[^0-9.]/', '', $s);
        return abs((float)$s);
    }

    private function parsearFecha(mixed $v): ?string
    {
        if (empty($v)) return null;
        if (is_numeric($v)) {
            try { return ExcelDate::excelToDateTimeObject((float)$v)->format('Y-m-d'); }
            catch (\Throwable) {}
        }
        try { return Carbon::createFromFormat('d/m/Y', trim((string)$v))->format('Y-m-d'); }
        catch (\Throwable) {}
        try { return Carbon::parse((string)$v)->format('Y-m-d'); }
        catch (\Throwable) {}
        return null;
    }
}
