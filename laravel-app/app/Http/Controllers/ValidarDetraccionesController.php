<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

/**
 * Valida detracciones desde el Excel oficial SUNAT / Banco de la Nación.
 *
 * Sin POR VALIDAR DETRACCION: el indicador es tipo_recaudacion = 'DETRACCION'
 * con estado PENDIENTE o DIFERENCIA PENDIENTE.
 */
class ValidarDetraccionesController extends Controller
{
    private const COLS_REQUERIDAS = [
        'fecha_pago' => 'Fecha Pago',
        'monto'      => 'Monto Deposito',
        'serie'      => 'Serie de Comprobante',
        'numero'     => 'Numero de Comprobante',
    ];

    public function index()
    {
        return view('facturas.validar_detracciones');
    }

    public function procesar(Request $request)
    {
        set_time_limit(120);
        ini_set('memory_limit', '256M');

        $request->validate([
            'archivo'    => 'required|file|mimes:xlsx,xls|max:10240',
            'sheet_name' => 'nullable|string|max:100',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
        } catch (\Throwable $e) {
            return response()->json(['success'=>false,'error'=>'No se pudo leer el Excel: '.$e->getMessage()],422);
        }

        $sheetNames = $spreadsheet->getSheetNames();
        if (count($sheetNames) > 1 && !$request->filled('sheet_name')) {
            return response()->json(['success'=>false,'multi_sheet'=>true,'sheets'=>$sheetNames,
                'error'=>'El archivo tiene '.count($sheetNames).' hojas. Selecciona la correcta.'],200);
        }

        if ($request->filled('sheet_name')) {
            $idx = array_search($request->input('sheet_name'), $sheetNames, true);
            if ($idx === false) return response()->json(['success'=>false,'error'=>'Hoja no encontrada.'],422);
            $ws = $spreadsheet->getSheet($idx);
        } else {
            $ws = $spreadsheet->getActiveSheet();
        }

        $headerRow  = $ws->rangeToArray('A2:ZZ2', null, true, false, false)[0] ?? [];
        $headerNorm = array_map(fn($h) => mb_strtolower(trim((string)$h)), $headerRow);

        $colMap = [];
        foreach (self::COLS_REQUERIDAS as $key => $label) {
            $idx = array_search(mb_strtolower(trim($label)), $headerNorm, true);
            if ($idx === false) {
                return response()->json(['success'=>false,'error'=>"Columna requerida \"{$label}\" no encontrada en fila 2."],422);
            }
            $colMap[$key] = $idx;
        }

        $dataRows = array_slice($ws->toArray(null, true, false, false), 2);
        if (empty($dataRows)) return response()->json(['success'=>false,'error'=>'El Excel no contiene datos.'],422);

        $validadas     = [];
        $noEncontradas = 0;
        $yaValidadas   = 0;

        DB::beginTransaction();
        try {
            foreach ($dataRows as $row) {
                $serie  = trim((string)($row[$colMap['serie']]  ?? ''));
                $numero = trim((string)($row[$colMap['numero']] ?? ''));
                if ($serie === '' || $numero === '') continue;
                $numero = (int)$numero;

                $fechaPago = $this->parsearFecha($row[$colMap['fecha_pago']] ?? null);
                $monto     = abs((float)($row[$colMap['monto']] ?? 0));

                $factura = DB::table('factura')
                    ->where('serie', $serie)
                    ->where('numero', $numero)
                    ->first();

                if (!$factura) { $noEncontradas++; continue; }

                // Solo procesar facturas con detracción que aún no se validaron
                // (tipo_recaudacion = DETRACCION y estado PENDIENTE o DIFERENCIA PENDIENTE)
                if ($factura->tipo_recaudacion !== 'DETRACCION') { $yaValidadas++; continue; }
                if (!in_array($factura->estado, ['PENDIENTE', 'DIFERENCIA PENDIENTE', 'VENCIDO'])) {
                    $yaValidadas++;
                    continue;
                }

                $montoAbonado     = (float)($factura->monto_abonado ?? 0);
                $importeTotal     = (float)($factura->importe_total ?? 0);
                $recExistente     = DB::table('recaudacion')->where('id_factura',$factura->id_factura)->first();
                $totalRecaudacion = $monto > 0 ? $monto : (float)($recExistente->total_recaudacion ?? 0);
                $montoPendiente   = max(0, $importeTotal - $montoAbonado - $totalRecaudacion);

                // Nuevo estado tras validar la detracción
                if ($montoPendiente <= 0) {
                    $nuevoEstado = 'PAGADA';
                } elseif ($montoAbonado > 0) {
                    $nuevoEstado = 'PAGO PARCIAL';
                } else {
                    // Detracción registrada y validada, pero queda diferencia por cobrar
                    $nuevoEstado = 'DIFERENCIA PENDIENTE';
                }

                DB::table('factura')->where('id_factura',$factura->id_factura)->update([
                    'estado'              => $nuevoEstado,
                    'monto_pendiente'     => $montoPendiente,
                    'fecha_actualizacion' => now(),
                    'fecha_abono'         => $factura->fecha_abono ?? $fechaPago,
                ]);

                DB::table('recaudacion')->updateOrInsert(
                    ['id_factura' => $factura->id_factura],
                    [
                        'porcentaje'        => $recExistente->porcentaje ?? 0,
                        'total_recaudacion' => $totalRecaudacion,
                        'fecha_recaudacion' => $fechaPago,
                    ]
                );

                $cliente = DB::table('cliente')->where('id_cliente',$factura->id_cliente)->value('razon_social');
                $validadas[] = [
                    'id_factura'        => $factura->id_factura,
                    'serie'             => $factura->serie,
                    'numero'            => str_pad($factura->numero,8,'0',STR_PAD_LEFT),
                    'razon_social'      => $cliente ?? '—',
                    'moneda'            => $factura->moneda,
                    'importe_total'     => number_format($importeTotal,2),
                    'monto_detraccion'  => number_format($totalRecaudacion,2),
                    'monto_pendiente'   => number_format($montoPendiente,2),
                    'estado_anterior'   => $factura->estado,
                    'estado_nuevo'      => $nuevoEstado,
                    'fecha_recaudacion' => $fechaPago,
                ];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'error'=>'Error: '.$e->getMessage().' ['.basename($e->getFile()).':'.$e->getLine().']'],500);
        }

        return response()->json([
            'success'         => true,
            'validadas'       => $validadas,
            'total_validadas' => count($validadas),
            'no_encontradas'  => $noEncontradas,
            'ya_validadas'    => $yaValidadas,
            'total_filas'     => count($dataRows),
        ]);
    }

    private function parsearFecha(mixed $v): ?string
    {
        if (empty($v)) return null;
        if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d');
        if (is_numeric($v)) {
            try { return ExcelDate::excelToDateTimeObject((float)$v)->format('Y-m-d'); } catch (\Throwable) {}
        }
        try { return Carbon::createFromFormat('d/m/Y',trim((string)$v))->format('Y-m-d'); } catch (\Throwable) {}
        try { return Carbon::parse((string)$v)->format('Y-m-d'); } catch (\Throwable) {}
        return null;
    }
}
