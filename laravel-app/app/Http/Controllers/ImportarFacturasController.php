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

        if (empty($filas)) {
            return back()->with('error', 'El archivo está vacío.')->withInput();
        }

        $encabezados = $filas[1] ?? [];
        [$formatoValido, $mensajeFormato] = $this->validarFormatoNubefact($encabezados);
        if (!$formatoValido) {
            return back()->with('error', $mensajeFormato)->withInput();
        }

        // Leer encabezados para mapear dinámicamente las columnas
        $colSerieModificado  = null;
        $colNumeroModificado = null;

        foreach ($encabezados as $columna => $valor) {
            $nombreEncabezado = strtoupper(trim((string)$valor));
            if ($nombreEncabezado === 'SERIE DOC MODIFICADO') {
                $colSerieModificado = $columna;
            }
            if ($nombreEncabezado === 'NUMERO DOC MODIFICADO') {
                $colNumeroModificado = $columna;
            }
        }

        unset($filas[1]); // quitar cabecera

        $idUsuario        = Auth::id();
        $insertadas       = 0;
        $omitidas         = 0;
        $duplicadas       = 0;
        $errores          = [];
        $numFila          = null;
        $fechasImportadas = []; // Para determinar el rango de fechas a mostrar

        DB::beginTransaction();

        try {
            foreach ($filas as $numFila => $f) {

                $esAnulado = strtoupper(trim((string)($f['AF'] ?? ''))) === 'SI';

                // Fila vacía
                if (empty($f['E']) && empty($f['F'])) continue;

                // ── Montos base ───────────────────────────────────────────
                $subtotalGravado  = $this->monto($f['P'] ?? 0);
                $montoIgv         = $this->monto($f['T'] ?? 0);
                $importeTotal     = $this->monto($f['Y'] ?? 0);
                $moneda           = trim((string)($f['N'] ?? 'PEN'));

                $montoRecaudacion = $this->monto($f['AE'] ?? 0);
                $porcentajeExcel  = $this->monto($f['AC'] ?? 0);

                if ($montoRecaudacion <= 0) {
                    $porcentajeExcel = 0;
                }

                $indicadorExcel = strtoupper(trim((string)($f['AI'] ?? '')));
                $tieneIndicador = (strpos($indicadorExcel, 'SI') !== false ||
                    strpos($indicadorExcel, 'DETRACCION') !== false ||
                    strpos($indicadorExcel, 'RETENCION') !== false);

                $tipoRecaudacionFila = ($tieneIndicador && $montoRecaudacion > 0)
                    ? $tipoRecaudacion
                    : null;

                $estado = 'PENDIENTE';

                $glosa            = $this->transformarGlosa(trim((string)($f['AG'] ?? '')));
                $fechaEmision     = $this->parsearFecha($f['B'] ?? null);
                $fechaVencimiento = $this->parsearFecha($f['C'] ?? null);

                $ruc         = trim((string)($f['J'] ?? ''));
                $razonSocial = trim((string)($f['K'] ?? ''));

                if (empty($ruc)) {
                    $errores[] = "Fila {$numFila}: sin RUC, omitida.";
                    $omitidas++;
                    continue;
                }

                $cliente = DB::table('cliente')->where('ruc', $ruc)->first();
                if (!$cliente) {
                    $tipoCliente = $this->inferirTipoCliente($ruc);
                    $idCliente = DB::table('cliente')->insertGetId([
                        'ruc'            => $ruc,
                        'razon_social'   => $razonSocial,
                        'tipo_cliente'   => $tipoCliente,
                        'estado_contado' => 'SIN_DATOS',
                        'fecha_creacion' => now(),
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

                $serie  = trim((string)($f['E'] ?? ''));
                $numero = (int) trim((string)($f['F'] ?? '0'));

                if (DB::table('factura')->where('serie', $serie)->where('numero', $numero)->exists()) {
                    $duplicadas++;
                    continue;
                }

                $esNotaCredito   = strtoupper($serie) === 'FC01';
                $serieModificada  = null;
                $numeroModificada = null;

                if ($esNotaCredito) {
                    $serieModificada = !is_null($colSerieModificado)
                        ? strtoupper(trim((string)($f[$colSerieModificado] ?? '')))
                        : '';
                    $numeroModificada = !is_null($colNumeroModificado)
                        ? (int) trim((string)($f[$colNumeroModificado] ?? '0'))
                        : 0;

                    $importeTotal = -abs($importeTotal);
                }

                $estadoFinal = $estado;
                if ($esAnulado) {
                    $estadoFinal = 'ANULADO';
                } elseif ($esNotaCredito && (empty($serieModificada) || $numeroModificada <= 0)) {
                    $estadoFinal = 'ANULADO';
                }

                if ($estadoFinal === 'ANULADO') {
                    $montoPendiente = 0;
                } elseif (in_array($estado, ['PENDIENTE', 'VENCIDO'])) {
                    $montoPendiente = $importeTotal;
                } else {
                    $montoPendiente = max(0, $importeTotal - $montoRecaudacion);
                }

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

                if ($esNotaCredito && !empty($serieModificada) && $numeroModificada > 0) {
                    DB::table('credito')->insert([
                        'id_factura'            => $idFactura,
                        'serie_doc_modificado'  => $serieModificada,
                        'numero_doc_modificado' => $numeroModificada,
                        'fecha_creacion'        => now(),
                    ]);
                }

                if ($montoRecaudacion > 0 && $tipoRecaudacionFila !== null) {
                    DB::table('recaudacion')->insert([
                        'id_factura'        => $idFactura,
                        'porcentaje'        => $porcentajeExcel,
                        'total_recaudacion' => $montoRecaudacion,
                    ]);
                }

                // Guardar la fecha de emisión para determinar el rango de la redirección
                if ($fechaEmision) {
                    $fechasImportadas[] = $fechaEmision;
                }

                $insertadas++;
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $this->mensajeErrorImportacionControlado($encabezados))->withInput();
        }

        // Si se insertaron facturas, redirigir a la lista con el rango de fechas importadas
        if ($insertadas > 0 && !empty($fechasImportadas)) {
            $filtroDesde = min($fechasImportadas);
            $filtroHasta = max($fechasImportadas);

            return redirect()->route('facturas.index', [
                'fecha_desde' => $filtroDesde,
                'fecha_hasta' => $filtroHasta,
            ])->with('resumen_importacion', [
                'insertadas'       => $insertadas,
                'omitidas'         => $omitidas,
                'duplicadas'       => $duplicadas,
                'errores'          => $errores,
                'tipo_recaudacion' => $tipoRecaudacion,
            ]);
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

    private function inferirTipoCliente(string $documento): string
    {
        $doc = preg_replace('/\D/', '', (string) $documento);
        return strlen($doc) === 8 ? 'PERSONA NATURAL' : 'PERSONA JURIDICA';
    }

    private function validarFormatoNubefact(array $encabezados): array
    {
        $reglas = [
            'B' => [
                'label' => 'Fecha de Emision',
                'alternativas' => [['FECHA']],
            ],
            'E' => [
                'label' => 'Serie',
                'alternativas' => [['SERIE']],
            ],
            'F' => [
                'label' => 'Numero',
                'alternativas' => [['NUMERO']],
            ],
            'J' => [
                'label' => 'RUC / Documento del cliente',
                'alternativas' => [['RUC'], ['DOCUMENTO', 'ADQUIRIENTE']],
            ],
            'K' => [
                'label' => 'Razon Social / Cliente',
                'alternativas' => [['RAZON'], ['DENOMINACION'], ['CLIENTE'], ['ADQUIRIENTE']],
            ],
            'Y' => [
                'label' => 'Importe Total',
                'alternativas' => [['IMPORTE'], ['TOTAL']],
            ],
        ];

        $faltantes = [];
        foreach ($reglas as $col => $rule) {
            $actualRaw = trim((string)($encabezados[$col] ?? ''));
            $actualNorm = $this->normalizarEncabezado($actualRaw);
            $cumple = false;

            foreach ($rule['alternativas'] as $altTokens) {
                $okTokens = true;
                foreach ($altTokens as $token) {
                    if (!str_contains($actualNorm, $this->normalizarEncabezado($token))) {
                        $okTokens = false;
                        break;
                    }
                }
                if ($okTokens) {
                    $cumple = true;
                    break;
                }
            }

            if (!$cumple) {
                $faltantes[] = "{$col}={$rule['label']} (detectado: " . ($actualRaw !== '' ? $actualRaw : 'VACIO') . ')';
            }
        }

        if (!empty($faltantes)) {
            $mensaje = 'Las columnas no coinciden con el formato esperado de Facturas (Nubefact). '
                . 'Columnas requeridas: B=Fecha Emision, E=Serie, F=Numero, J=RUC/Documento, K=Razon Social/Cliente, Y=Importe Total. '
                . 'Diferencias detectadas: ' . implode(' | ', $faltantes);

            return [false, $mensaje];
        }

        return [true, null];
    }

    private function normalizarEncabezado(string $value): string
    {
        $txt = strtoupper(trim($value));
        $txt = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'N'],
            $txt
        );
        $txt = preg_replace('/[^A-Z0-9\s]/', ' ', $txt);
        $txt = preg_replace('/\s+/', ' ', $txt);
        return trim((string) $txt);
    }

    private function mensajeErrorImportacionControlado(array $encabezados): string
    {
        $requeridas = [
            'B: Fecha Emision',
            'E: Serie',
            'F: Numero',
            'J: RUC/Documento cliente',
            'K: Razon Social/Cliente',
            'Y: Importe Total',
        ];

        $detCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y'];
        $detectadas = [];
        foreach ($detCols as $col) {
            $valor = trim((string)($encabezados[$col] ?? ''));
            if ($valor !== '') {
                $detectadas[] = $col . ': ' . $valor;
            }
        }

        return 'Archivo incorrecto. El archivo debe tener columnas del formato Facturas (Excel de Ventas). '
            . 'Columnas requeridas: ' . implode(', ', $requeridas) . '. '
            . 'Columnas detectadas: ' . (!empty($detectadas) ? implode(' | ', $detectadas) : 'No se detectaron encabezados en la fila 1');
    }
}
