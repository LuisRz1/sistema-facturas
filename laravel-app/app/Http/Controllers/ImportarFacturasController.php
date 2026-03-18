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
            'archivo'         => 'required|file|max:10240',
            'tipo_recaudacion'=> 'required|in:DETRACCION,RETENCION,NINGUNA',
        ], [
            'archivo.required'          => 'Selecciona un archivo Excel.',
            'tipo_recaudacion.required' => 'Selecciona el tipo de recaudación del archivo.',
            'tipo_recaudacion.in'       => 'Tipo de recaudación inválido.',
        ]);

        $archivo   = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'])) {
            return back()->with('error', 'El archivo debe ser .xlsx o .xls');
        }

        // Tipo de recaudación indicado por el usuario para TODAS las filas del archivo
        $tipoRecaudacion = $request->input('tipo_recaudacion');
        if ($tipoRecaudacion === 'NINGUNA') {
            $tipoRecaudacion = null;
        }

        try {
            $spreadsheet = IOFactory::load($archivo->getPathname());
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el Excel: ' . $e->getMessage());
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
                $subtotalGravado = $this->monto($f['P'] ?? 0);
                $montoIgv        = $this->monto($f['T'] ?? 0);
                $importeTotal    = $this->monto($f['Y'] ?? 0);
                $moneda          = trim((string)($f['N'] ?? 'PEN'));

                // ── Monto de recaudación del Excel (columna AE) ───────────
                // El porcentaje viene de la columna AC
                $montoRecaudacion  = $this->monto($f['AE'] ?? 0);
                $porcentajeExcel   = $this->monto($f['AC'] ?? 0);

                // Si el tipo seleccionado es NINGUNA o el monto es 0, no hay recaudación
                if (!$tipoRecaudacion || $montoRecaudacion <= 0) {
                    $montoRecaudacion = 0;
                    $porcentajeExcel  = 0;
                }

                // ── Estado inicial ─────────────────────────────────────────
                // DETRACCION → POR VALIDAR DETRACCION (pendiente de confirmar)
                // RETENCION / sin recaudación → PENDIENTE
                $estado = ($tipoRecaudacion === 'DETRACCION') ? 'POR VALIDAR DETRACCION' : 'PENDIENTE';

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
                    // Crear cliente. La columna en BD es estado_contado (sin 'a' al final)
                    $idCliente = DB::table('cliente')->insertGetId([
                        'ruc'                 => $ruc,
                        'razon_social'        => $razonSocial,
                        'estado_contado'      => 'SIN_DATOS',
                        'fecha_creacion'      => now(),
                    ]);
                } else {
                    $idCliente = $cliente->id_cliente;
                    // Actualizar razón social si cambió
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

                // ── Monto pendiente inicial ────────────────────────────────
                // El cliente debe abonar el importe menos lo que ya retiene el banco (recaudación)
                $montoPendiente = max(0, $importeTotal - $montoRecaudacion);

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
                    'estado'            => $estado,
                    'glosa'             => $glosa,
                    'forma_pago'        => trim((string)($f['AH'] ?? '')),
                    'tipo_recaudacion'  => $tipoRecaudacion,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'fecha_emision'     => $fechaEmision,
                    'fecha_creacion'    => now(),
                    'usuario_creacion'  => $idUsuario,
                    'monto_abonado'     => 0.00,
                    'monto_pendiente'   => $montoPendiente,
                ]);

                // ── Insertar recaudación si aplica ─────────────────────────
                if ($tipoRecaudacion && $montoRecaudacion > 0) {
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
            'tipo_recaudacion' => $tipoRecaudacion ?? 'NINGUNA',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

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
        if (str_contains($up, 'AGUA') && str_contains($up, 'TRANSPORT')) {
            return 'Servicio de transporte de agua';
        }
        if (str_contains($up, 'AGUA')) {
            return 'Suministro de Agua';
        }
        if (str_contains($up, 'TRANSPORT')) {
            return 'Servicio de transporte';
        }
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
