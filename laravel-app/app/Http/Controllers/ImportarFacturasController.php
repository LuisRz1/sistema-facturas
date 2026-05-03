<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

/**
 * Importación de facturas desde el Excel de Nubefact.
 *
 * CAMBIOS v2 (nuevo formato Nubefact):
 *  - Columnas desplazadas: ahora los datos empiezan en columna A (antes B).
 *  - ¿ANULADO?  → columna AI  (antes ANULADO en AF)
 *  - ¿DETRACCIÓN? → columna AB (antes en AI)
 *  - IMPORTE DE DETRACCIÓN → columna AC (antes AE)
 *  - TOTAL RETENCIÓN → columna Z  (nueva; si > 0 fuerza tipo RETENCION)
 *  - DOC MODIFICADO - SERIE  → nombre anterior: "SERIE DOC MODIFICADO"
 *  - DOC MODIFICADO - NUMERO → nombre anterior: "NUMERO DOC MODIFICADO"
 */
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
            'archivo' => 'required|file|max:10240',
        ], [
            'archivo.required' => 'Selecciona un archivo Excel.',
        ]);

        $archivo   = $request->file('archivo');
        $extension = strtolower($archivo->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls'])) {
            return back()->with('error', 'El archivo debe ser .xlsx o .xls')->withInput();
        }

        $tipoRecaudacion = $request->input('tipo_recaudacion', 'DETRACCION');

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

        // ── Detectar dinámicamente columnas de documento modificado ──────────
        // Soporta nombre nuevo ("DOC MODIFICADO - SERIE") y anterior ("SERIE DOC MODIFICADO")
        $colSerieModificado  = null;
        $colNumeroModificado = null;
        $colDetalleItems     = null;

        foreach ($encabezados as $columna => $valor) {
            $nombreEncabezado = strtoupper(trim((string)$valor));
            $nombreNormalizado = $this->normalizarEncabezado((string)$valor);

            if (in_array($nombreEncabezado, ['DOC MODIFICADO - SERIE', 'SERIE DOC MODIFICADO'])) {
                $colSerieModificado = $columna;
            }
            if (in_array($nombreEncabezado, ['DOC MODIFICADO - NUMERO', 'NUMERO DOC MODIFICADO'])) {
                $colNumeroModificado = $columna;
            }

            // Tomar la glosa desde el encabezado real de detalle y no por letra fija.
            if (str_contains($nombreNormalizado, 'DETALLE') &&
                (str_contains($nombreNormalizado, 'LINEAS') || str_contains($nombreNormalizado, 'LINEA')) &&
                str_contains($nombreNormalizado, 'ITEM')) {
                $colDetalleItems = $columna;
            }
        }

        unset($filas[1]); // quitar cabecera

        $idUsuario        = Auth::id();
        $insertadas       = 0;
        $omitidas         = 0;
        $duplicadas       = 0;
        $errores          = [];
        $numFila          = null;
        $fechasImportadas = [];

        // Crear registro de sincronización
        $nombreArchivo = $archivo->getClientOriginalName();
        $idSincronizacion = DB::table('sincronizacion_nubefact')->insertGetId([
            'fecha_inicio'               => now(),
            'estado'                     => 'EN_PROCESO',
            'nombre_archivo'             => $nombreArchivo,
            'total_registros_recibidos'  => 0,
            'total_registros_procesados' => 0,
            'total_registros_error'      => 0,
            'activo'                     => 1,
        ]);

        DB::beginTransaction();

        try {
            foreach ($filas as $numFila => $f) {

                // ── ¿ANULADO? → columna AI (nuevo) ──────────────────────────
                $esAnulado = strtoupper(trim((string)($f['AI'] ?? ''))) === 'SI';

                // Fila vacía
                if (empty($f['D']) && empty($f['E'])) continue;

                // ── Montos ───────────────────────────────────────────────────
                $subtotalGravado  = $this->monto($f['N'] ?? 0);   // GRAVADA
                $montoIgv         = $this->monto($f['R'] ?? 0);   // IGV
                $importeTotal     = $this->monto($f['V'] ?? 0);   // TOTAL
                $moneda           = trim((string)($f['J'] ?? 'PEN')); // MONEDA

                // ── Recaudación ──────────────────────────────────────────────
                // TOTAL RETENCIÓN (Z): si > 0 fuerza tipo RETENCION para esta fila
                $totalRetencionExcel  = $this->monto($f['Z'] ?? 0);
                // ¿DETRACCIÓN? (AB): 'SI' indica que hay detracción
                $indicadorDetraccion  = strtoupper(trim((string)($f['AB'] ?? '')));
                $tieneDetraccion      = (strpos($indicadorDetraccion, 'SI') !== false);
                // IMPORTE DE DETRACCIÓN (AC)
                $importeDetraccion    = $this->monto($f['AC'] ?? 0);

                // Determinar tipo y monto de recaudación para esta fila
                if ($totalRetencionExcel > 0) {
                    // Fila con retención explícita → forzar RETENCION
                    $tipoRecaudacionFila = 'RETENCION';
                    $montoRecaudacion    = $totalRetencionExcel;
                    $porcentajeExcel     = $importeTotal > 0
                        ? round(($totalRetencionExcel / $importeTotal) * 100, 2)
                        : 0;
                } elseif ($tieneDetraccion && $importeDetraccion > 0) {
                    // Fila con detracción → usar tipo seleccionado en el formulario
                    $tipoRecaudacionFila = $tipoRecaudacion;
                    $montoRecaudacion    = $importeDetraccion;
                    $porcentajeExcel     = $importeTotal > 0
                        ? round(($importeDetraccion / $importeTotal) * 100, 2)
                        : 0;
                } else {
                    $tipoRecaudacionFila = null;
                    $montoRecaudacion    = 0;
                    $porcentajeExcel     = 0;
                }

                $estado = 'PENDIENTE';

                $detalleItems     = !is_null($colDetalleItems)
                    ? trim((string)($f[$colDetalleItems] ?? ''))
                    : trim((string)($f['AG'] ?? ''));
                $glosa            = $this->transformarGlosa($detalleItems);
                $fechaEmision     = $this->parsearFecha($f['A'] ?? null); // FECHA E
                $fechaVencimiento = $this->parsearFecha($f['B'] ?? null); // FECHA V

                $ruc         = trim((string)($f['G'] ?? '')); // RUC
                $razonSocial = trim((string)($f['H'] ?? '')); // DENOMINACIÓN

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

                $serie  = trim((string)($f['D'] ?? ''));   // SERIE
                $numero = (int) trim((string)($f['E'] ?? '0')); // NÚMERO

                if (DB::table('factura')->where('serie', $serie)->where('numero', $numero)->where('activo', 1)->exists()) {
                    $duplicadas++;
                    continue;
                }

                $esNotaCredito    = strtoupper($serie) === 'FC01';
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

                $tipoOperacion = trim((string)($f['I'] ?? '')); // TIPO DE OPERACIÓN

                // ¿Existe con activo=0? → Reactivar y actualizar en vez de insertar
                $facturaDesactivada = DB::table('factura')
                    ->where('serie', $serie)
                    ->where('numero', $numero)
                    ->where('activo', 0)
                    ->first();

                if ($facturaDesactivada) {
                    $idFactura = $facturaDesactivada->id_factura;
                    DB::table('factura')->where('id_factura', $idFactura)->update([
                        'tipo_operacion'    => $tipoOperacion,
                        'id_cliente'        => $idCliente,
                        'id_usuario'        => $idUsuario,
                        'moneda'            => $moneda,
                        'subtotal_gravado'  => $subtotalGravado,
                        'monto_igv'         => $montoIgv,
                        'importe_total'     => $importeTotal,
                        'estado'            => $estadoFinal,
                        'glosa'             => $glosa,
                        'forma_pago'        => trim((string)($f['AE'] ?? '')),
                        'tipo_recaudacion'  => $tipoRecaudacionFila,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'fecha_emision'     => $fechaEmision,
                        'monto_abonado'     => 0.00,
                        'monto_pendiente'   => $montoPendiente,
                        'activo'            => 1,
                        'fecha_actualizacion' => now(),
                    ]);
                    // Limpiar recaudacion y credito anteriores para reescribir limpios
                    DB::table('recaudacion')->where('id_factura', $idFactura)->delete();
                    DB::table('credito')->where('id_factura', $idFactura)->delete();
                    $accionSinc = 'REACTIVADA';
                } else {
                    $idFactura = DB::table('factura')->insertGetId([
                        'serie'             => $serie,
                        'numero'            => $numero,
                        'tipo_operacion'    => $tipoOperacion,
                        'id_cliente'        => $idCliente,
                        'id_usuario'        => $idUsuario,
                        'moneda'            => $moneda,
                        'subtotal_gravado'  => $subtotalGravado,
                        'monto_igv'         => $montoIgv,
                        'importe_total'     => $importeTotal,
                        'estado'            => $estadoFinal,
                        'glosa'             => $glosa,
                        'forma_pago'        => trim((string)($f['AE'] ?? '')),
                        'tipo_recaudacion'  => $tipoRecaudacionFila,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'fecha_emision'     => $fechaEmision,
                        'fecha_creacion'    => now(),
                        'usuario_creacion'  => $idUsuario,
                        'monto_abonado'     => 0.00,
                        'monto_pendiente'   => $montoPendiente,
                    ]);
                    $accionSinc = 'INSERTADA';
                }

                // Vincular factura al registro de sincronización
                DB::table('sincronizacion_factura')->insert([
                    'id_sincronizacion' => $idSincronizacion,
                    'id_factura'        => $idFactura,
                    'accion'            => $accionSinc,
                    'fecha_registro'    => now(),
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

                if ($fechaEmision) {
                    $fechasImportadas[] = $fechaEmision;
                }

                $insertadas++;
            }

            DB::commit();

            // Actualizar registro de sincronización con los totales finales
            $totalFilas = count($filas) - $omitidas - $duplicadas;
            DB::table('sincronizacion_nubefact')
                ->where('id_sincronizacion', $idSincronizacion)
                ->update([
                    'fecha_fin'                  => now(),
                    'estado'                     => empty($errores) ? 'COMPLETADO' : 'CON_ERRORES',
                    'total_registros_recibidos'  => count($filas),
                    'total_registros_procesados' => $insertadas,
                    'total_registros_error'      => count($errores),
                ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            DB::table('sincronizacion_nubefact')
                ->where('id_sincronizacion', $idSincronizacion)
                ->update(['estado' => 'ERROR', 'fecha_fin' => now()]);
            return back()->with('error', $this->mensajeErrorImportacionControlado($encabezados))->withInput();
        }

        if ($insertadas > 0 && !empty($fechasImportadas)) {
            $filtroDesde = min($fechasImportadas);
            $filtroHasta = max($fechasImportadas);

            return redirect()->route('facturas.index', [
                'fecha_desde' => $filtroDesde,
                'fecha_hasta' => $filtroHasta,
            ])->with('resumen_importacion', [
                'insertadas'          => $insertadas,
                'omitidas'            => $omitidas,
                'duplicadas'          => $duplicadas,
                'errores'             => $errores,
                'tipo_recaudacion'    => $tipoRecaudacion,
                'id_sincronizacion'   => $idSincronizacion,
            ]);
        }

        return redirect()->route('facturas.importar')->with('resumen', [
            'insertadas'          => $insertadas,
            'omitidas'            => $omitidas,
            'duplicadas'          => $duplicadas,
            'errores'             => $errores,
            'tipo_recaudacion'    => $tipoRecaudacion,
            'id_sincronizacion'   => $idSincronizacion,
        ]);
    }

    // ── HELPERS ──────────────────────────────────────────────────────────────

    private function transformarGlosa(string $txt): string
    {
        if (empty($txt)) return '';

        $texto = trim((string) preg_replace('/\s+/u', ' ', str_replace(["\r", "\n", "\t"], ' ', $txt)));
        $normalizado = $this->normalizarEncabezado($texto);

        if (str_contains($normalizado, 'PLACA')) {
            if (preg_match('/PLACA\s*:?\s*([A-Z0-9]{3}[- ]?[A-Z0-9]{3,4})/i', $texto, $m)) {
                $placa = strtoupper(str_replace(' ', '-', trim((string) $m[1])));
                return 'Alquiler de carro Placa: ' . $placa;
            }
            return 'Alquiler de carro Placa: N/D';
        }

        if (str_contains($normalizado, 'ALQUILER')) {
            if (preg_match('/ALQUILER\s+DE\s+([A-Z0-9ÁÉÍÓÚÑ]+)(?:\s+([A-Z0-9ÁÉÍÓÚÑ]+))?/iu', $texto, $m)) {
                $parte = ucfirst(strtolower((string) $m[1]));
                if (!empty($m[2])) {
                    $parte .= ' ' . ucfirst(strtolower((string) $m[2]));
                }
                return 'Alquiler de ' . $parte;
            }
            return 'Alquiler';
        }

        $tieneAgua = str_contains($normalizado, 'AGUA');
        $tieneTransporte = str_contains($normalizado, 'TRANSPORTE') || str_contains($normalizado, 'TRANSPORT');

        if ($tieneTransporte && $tieneAgua) {
            return 'Servicio de transporte de agua';
        }
        if ($tieneAgua) {
            return 'Suministro de Agua';
        }
        if ($tieneTransporte) {
            return 'Servicio de transporte';
        }

        return $texto;
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

    /**
     * Valida que el Excel corresponda al formato de Nubefact (versión nueva o anterior).
     *
     * Nuevo formato (desde 2025): columnas empiezan en A.
     *   A=FECHA E, D=SERIE, E=NÚMERO, G=RUC, H=DENOMINACIÓN, V=TOTAL
     *
     * Formato anterior: columnas empezaban en B.
     *   B=Fecha, E=Serie, F=Numero, J=RUC, K=RazonSocial, Y=Total
     */
    private function validarFormatoNubefact(array $encabezados): array
    {
        // ── Detectar versión del formato por columna A ─────────────────────
        $colA = strtoupper(trim((string)($encabezados['A'] ?? '')));
        $colB = strtoupper(trim((string)($encabezados['B'] ?? '')));

        // Nuevo formato: A contiene "FECHA"
        $esNuevoFormato = str_contains($colA, 'FECHA');
        // Formato anterior: B contiene "FECHA" (y A está vacío o no es fecha)
        $esFormatoAnterior = !$esNuevoFormato && str_contains($colB, 'FECHA');

        if (!$esNuevoFormato && !$esFormatoAnterior) {
            // Intentar detectar por presencia de columnas clave
            $esNuevoFormato = !empty($encabezados['D']) && !empty($encabezados['G']);
        }

        if ($esNuevoFormato) {
            $reglas = [
                'A' => ['label' => 'Fecha de Emision',        'alternativas' => [['FECHA']]],
                'D' => ['label' => 'Serie',                   'alternativas' => [['SERIE']]],
                'E' => ['label' => 'Numero',                  'alternativas' => [['NÚMERO'], ['NUMERO']]],
                'G' => ['label' => 'RUC / Documento cliente', 'alternativas' => [['RUC'], ['DOCUMENTO']]],
                'H' => ['label' => 'Denominacion / Cliente',  'alternativas' => [['DENOMINACIÓN'], ['DENOMINACION']]],
                'V' => ['label' => 'Total',                   'alternativas' => [['TOTAL']]],
            ];
        } else {
            // Formato anterior
            $reglas = [
                'B' => ['label' => 'Fecha de Emision',        'alternativas' => [['FECHA']]],
                'E' => ['label' => 'Serie',                   'alternativas' => [['SERIE']]],
                'F' => ['label' => 'Numero',                  'alternativas' => [['NUMERO']]],
                'J' => ['label' => 'RUC / Documento cliente', 'alternativas' => [['RUC'], ['DOCUMENTO', 'ADQUIRIENTE']]],
                'K' => ['label' => 'Razon Social / Cliente',  'alternativas' => [['RAZON'], ['DENOMINACION'], ['CLIENTE'], ['ADQUIRIENTE']]],
                'Y' => ['label' => 'Importe Total',           'alternativas' => [['IMPORTE'], ['TOTAL']]],
            ];
        }

        $faltantes = [];
        foreach ($reglas as $col => $rule) {
            $actualRaw  = trim((string)($encabezados[$col] ?? ''));
            $actualNorm = $this->normalizarEncabezado($actualRaw);
            $cumple     = false;

            foreach ($rule['alternativas'] as $altTokens) {
                $okTokens = true;
                foreach ($altTokens as $token) {
                    if (!str_contains($actualNorm, $this->normalizarEncabezado($token))) {
                        $okTokens = false;
                        break;
                    }
                }
                if ($okTokens) { $cumple = true; break; }
            }

            if (!$cumple) {
                $faltantes[] = "{$col}={$rule['label']} (detectado: " . ($actualRaw !== '' ? $actualRaw : 'VACIO') . ')';
            }
        }

        if (!empty($faltantes)) {
            $mensaje = 'Las columnas no coinciden con el formato esperado de Facturas (Nubefact). '
                . 'Diferencias detectadas: ' . implode(' | ', $faltantes);
            return [false, $mensaje];
        }

        return [true, null];
    }

    private function normalizarEncabezado(string $value): string
    {
        $txt = strtoupper(trim($value));
        $txt = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', '¿', '?'],
            ['A', 'E', 'I', 'O', 'U', 'N', '',  ''],
            $txt
        );
        $txt = preg_replace('/[^A-Z0-9\s]/', ' ', $txt);
        $txt = preg_replace('/\s+/', ' ', $txt);
        return trim((string) $txt);
    }

    private function mensajeErrorImportacionControlado(array $encabezados): string
    {
        // Detectar qué formato tiene el archivo
        $colA = strtoupper(trim((string)($encabezados['A'] ?? '')));
        $esNuevo = str_contains($colA, 'FECHA');

        if ($esNuevo) {
            $requeridas = [
                'A: Fecha Emision', 'D: Serie', 'E: Numero',
                'G: RUC/Documento cliente', 'H: Denominacion/Cliente', 'V: Total',
            ];
        } else {
            $requeridas = [
                'B: Fecha Emision', 'E: Serie', 'F: Numero',
                'J: RUC/Documento cliente', 'K: Razon Social/Cliente', 'Y: Importe Total',
            ];
        }

        $detCols = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y'];
        $detectadas = [];
        foreach ($detCols as $col) {
            $valor = trim((string)($encabezados[$col] ?? ''));
            if ($valor !== '') $detectadas[] = $col . ': ' . $valor;
        }

        return 'Archivo incorrecto. El archivo debe ser el Excel de Ventas exportado desde Nubefact. '
            . 'Columnas requeridas: ' . implode(', ', $requeridas) . '. '
            . 'Columnas detectadas: ' . (!empty($detectadas) ? implode(' | ', $detectadas) : 'No se detectaron encabezados en la fila 1');
    }
}
