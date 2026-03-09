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
    // Monto mínimo para aplicar detracción o autodetracción
    private const MINIMO_RECAUDACION = 700.00;

    public function index()
    {
        return view('facturas.importar');
    }

    public function importar(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $request->validate([
            'archivo' => 'required|file|max:10240',
        ], [
            'archivo.required' => 'Selecciona un archivo Excel.',
        ]);

        $archivo   = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'])) {
            return back()->with('error', 'El archivo debe ser .xlsx o .xls');
        }

        try {
            $spreadsheet = IOFactory::load($archivo->getPathname());
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo leer el Excel: ' . $e->getMessage());
        }

        $hoja  = $spreadsheet->getActiveSheet();
        // $formatData = false → valores numéricos crudos (159360.0, no "159.360,00")
        $filas = $hoja->toArray(null, true, false, true);
        unset($filas[1]); // quitar cabecera

        $idUsuario  = Auth::id() ?? 1;
        $insertadas = 0;
        $omitidas   = 0;
        $duplicadas = 0;
        $errores    = [];

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

                // ── Montos ───────────────────────────────────────────────────
                // phpspreadsheet devuelve los valores numéricos nativos (int/float).
                // El formato visual "159.360,00" del Excel es solo presentación;
                // el valor almacenado ya es 159360. abs() cubre los negativos del tipo 07.
                $tipo            = trim((string)($f['D'] ?? '01'));
                $subtotalGravado = $this->monto($f['P'] ?? 0);
                $montoIgv        = $this->monto($f['T'] ?? 0);
                $importeTotal    = $this->monto($f['Y'] ?? 0);

                // ENUM: PAGADA | PENDIENTE | POR_VENCER | VENCIDA | ANULADA | OBSERVADA
                $estado = ($tipo === '07') ? 'PENDIENTE' : 'PAGADA';

                // ── Detracción / Autodetracción ──────────────────────────────
                // REGLA: solo aplica recaudación si importe_total > 700 PEN
                $moneda      = trim((string)($f['N'] ?? 'PEN'));
                $tieneDetrac = strtoupper(trim((string)($f['AD'] ?? ''))) === 'SI';
                $aplicaRecaudacion = ($moneda === 'PEN' && $importeTotal > self::MINIMO_RECAUDACION);

                $tipoRecaudacion = null;
                $importeDetrac   = 0;
                $totalAutodetrac = null;

                if ($aplicaRecaudacion) {
                    if ($tieneDetrac) {
                        $tipoRecaudacion = 'DETRACCION';
                        $importeDetrac   = $this->monto($f['AE'] ?? 0);
                    } else {
                        $tipoRecaudacion = 'AUTODETRACCION';
                        $totalAutodetrac = round($importeTotal * 0.10, 2);
                    }
                }

                // ── Glosa y fechas ───────────────────────────────────────────
                $glosa            = $this->transformarGlosa(trim((string)($f['AG'] ?? '')));
                $fechaEmision     = $this->parsearFecha($f['B'] ?? null);
                $fechaVencimiento = $this->parsearFecha($f['C'] ?? null);

                // ── Cliente: buscar o crear ──────────────────────────────────
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
                        'ruc'              => $ruc,
                        'razon_social'     => $razonSocial,
                        'estado_contacto'  => 'SIN_DATOS',
                        'fecha_creacion'   => now(),
                        'usuario_creacion' => $idUsuario,
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

                // ── Duplicado ────────────────────────────────────────────────
                $serie  = trim((string)($f['E'] ?? ''));
                $numero = (int) trim((string)($f['F'] ?? '0'));

                if (DB::table('factura')->where('serie', $serie)->where('numero', $numero)->exists()) {
                    $duplicadas++;
                    continue;
                }

                // ── Insertar Factura ─────────────────────────────────────────
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
                    'tipo_recaudacion'  => $tipoRecaudacion,   // null si total <= 700
                    'fecha_vencimiento' => $fechaVencimiento,
                    'fecha_emision'     => $fechaEmision,
                    'fecha_creacion'    => now(),
                    'usuario_creacion'  => $idUsuario,
                ]);

                // ── Tabla hija solo si aplica recaudación ────────────────────
                if ($aplicaRecaudacion) {
                    if ($tieneDetrac) {
                        DB::table('detraccion')->insert([
                            'id_factura'       => $idFactura,
                            'porcentaje'       => 10.00,
                            'total_detraccion' => $importeDetrac,
                        ]);
                    } else {
                        DB::table('autodetraccion')->insert([
                            'id_factura'           => $idFactura,
                            'porcentaje'           => 10.00,
                            'total_autodetraccion' => $totalAutodetrac,
                        ]);
                    }
                }

                $insertadas++;
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error',
                'Error en fila ' . ($numFila ?? '?') . ': ' . $e->getMessage() .
                ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']'
            );
        }

        return redirect()->route('facturas.importar')->with('resumen', [
            'insertadas' => $insertadas,
            'omitidas'   => $omitidas,
            'duplicadas' => $duplicadas,
            'errores'    => $errores,
        ]);
    }

    // ── HELPERS ──────────────────────────────────────────────────────────────

    private function transformarGlosa(string $txt): string
    {
        if (empty($txt)) return '';
        $up = strtoupper($txt);

        // 1. PLACA
        if (str_contains($up, 'PLACA')) {
            if (preg_match('/PLACA\s*:?\s*([A-Z0-9]{3}[-]?[A-Z0-9]{3,4})/i', $txt, $m)) {
                return 'Alquiler de carro Placa: ' . strtoupper($m[1]);
            }
            return 'Alquiler de carro Placa: N/D';
        }

        // 2. AGUA + TRANSPORTE (ambas palabras presentes)
        if (str_contains($up, 'AGUA') && str_contains($up, 'TRANSPORT')) {
            return 'Servicio de transporte de agua';
        }

        // 3. Solo AGUA
        if (str_contains($up, 'AGUA')) {
            return 'Suministro de Agua';
        }

        // 4. Solo TRANSPORTE
        if (str_contains($up, 'TRANSPORT')) {
            return 'Servicio de transporte';
        }

        // 5. ALQUILER DE ...
        if (str_contains($up, 'ALQUILER')) {
            if (preg_match('/ALQUILER\s+DE\s+([\wÁÉÍÓÚáéíóúÑñ]+)(?:\s+([\wÁÉÍÓÚáéíóúÑñ]+))?/iu', $txt, $m)) {
                $parte = ucfirst(strtolower($m[1]));
                if (!empty($m[2])) $parte .= ' ' . ucfirst(strtolower($m[2]));
                return 'Alquiler de ' . $parte;
            }
        }

        // 6. Texto original limpio
        return trim(preg_replace('/\s+/', ' ', $txt));
    }

    /**
     * Parsea montos desde phpspreadsheet.
     *
     * phpspreadsheet devuelve los valores numéricos de Excel como int/float nativos,
     * ignorando el formato visual de celda ("159.360,00" → 159360).
     * Solo en casos donde la celda sea texto con formato europeo se aplica
     * la conversión manual: quitar puntos de miles y cambiar coma decimal por punto.
     */
    private function monto(mixed $v): float
    {
        // Valor numérico nativo (caso normal con phpspreadsheet)
        if (is_int($v) || is_float($v)) {
            return abs($v);
        }

        $s = trim((string)$v);
        if ($s === '' || $s === null) return 0.0;

        // Formato europeo: "159.360,00" → quitar puntos → "159360,00" → cambiar coma → "159360.00"
        // Detectamos si tiene coma (separador decimal europeo)
        if (str_contains($s, ',')) {
            // Quitar puntos de miles, reemplazar coma decimal por punto
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }

        // Quitar cualquier carácter no numérico excepto punto y signo
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
