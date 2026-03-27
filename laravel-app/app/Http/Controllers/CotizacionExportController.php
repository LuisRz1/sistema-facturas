<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Handles Excel export for cotizaciones.
 *
 * Merge the exportExcel() and exportExcelBulk() methods into CotizacionController.php
 * and add the routes below to web.php:
 *
 *   Route::get('/cotizaciones/{id}/export-excel',
 *       [CotizacionController::class, 'exportExcel'])->name('cotizaciones.export-excel');
 *   Route::post('/cotizaciones/export-excel-bulk',
 *       [CotizacionController::class, 'exportExcelBulk'])->name('cotizaciones.export-excel-bulk');
 */
class CotizacionExportController extends Controller
{
    // ── Single cotización export ───────────────────────────────────────────
    public function exportExcel(int $id)
    {
        $cotizacion = $this->getCotizacionWithDetails($id);
        if (!$cotizacion) abort(404);

        $filas       = $this->getFilas($cotizacion);
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($cotizacion->numero_valorizacion . '-' . $cotizacion->obra, 0, 31));

        $this->buildSheet($sheet, $cotizacion, $filas);

        $filename = 'Valorizacion_'
            . preg_replace('/[^A-Za-z0-9\-_]/', '_', $cotizacion->numero_valorizacion)
            . '_' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $cotizacion->obra)
            . '_' . now()->format('Ymd') . '.xlsx';

        return $this->streamDownload($spreadsheet, $filename);
    }

    // ── Bulk export (POST with array of ids or filter params) ─────────────
    public function exportExcelBulk(Request $request)
    {
        $ids = $request->input('ids', []);

        // If no specific IDs, export all matching filters
        if (empty($ids)) {
            $query = DB::table('cotizacion')->where('activo', 1);
            if ($request->filled('tipo'))        $query->where('tipo_cotizacion', $request->tipo);
            if ($request->filled('id_cliente'))  $query->where('id_cliente', $request->id_cliente);
            if ($request->filled('fecha_desde')) $query->where('periodo_inicio', '>=', $request->fecha_desde);
            if ($request->filled('fecha_hasta')) $query->where('periodo_fin', '<=', $request->fecha_hasta);
            $ids = $query->pluck('id_cotizacion')->toArray();
        }

        if (empty($ids)) {
            return back()->with('error', 'No hay cotizaciones que exportar.');
        }

        $spreadsheet = new Spreadsheet();
        $first = true;

        foreach ($ids as $id) {
            $cotizacion = $this->getCotizacionWithDetails($id);
            if (!$cotizacion) continue;

            $filas = $this->getFilas($cotizacion);

            if ($first) {
                $sheet = $spreadsheet->getActiveSheet();
                $first = false;
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $sheetTitle = substr(
                $cotizacion->numero_valorizacion . '-' . Str::limit($cotizacion->obra, 15, ''),
                0, 31
            );
            $sheet->setTitle($sheetTitle);
            $this->buildSheet($sheet, $cotizacion, $filas);
        }

        $filename = 'Valorizaciones_CRC_' . now()->format('Ymd_Hi') . '.xlsx';
        return $this->streamDownload($spreadsheet, $filename);
    }

    // ── Build one sheet matching the provided Excel format ────────────────
    private function buildSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        object $cotizacion,
        \Illuminate\Support\Collection $filas
    ): void {
        $esMaquinaria = $cotizacion->tipo_cotizacion === 'MAQUINARIA';
        $itemNombre   = $esMaquinaria
            ? ($cotizacion->maquinaria_nombre ?? 'MAQUINARIA')
            : ($cotizacion->agregado_nombre   ?? 'AGREGADO');

        $periodoInicio = \Carbon\Carbon::parse($cotizacion->periodo_inicio)
            ->locale('es')->isoFormat('D [DE] MMMM [DEL] Y');
        $periodoFin    = \Carbon\Carbon::parse($cotizacion->periodo_fin)
            ->locale('es')->isoFormat('D [DE] MMMM [DEL] Y');

        // ── Column widths ────────────────────────────────────────────────
        if ($esMaquinaria) {
            $colDefs = [
                'B'=>10,'C'=>22,'D'=>30,'E'=>14,'F'=>18,'G'=>14,
                'H'=>8,'I'=>8,'J'=>16,'K'=>14,'L'=>10,'M'=>12,
            ];
            $lastCol = 'M';
        } else {
            $colDefs = [
                'B'=>10,'C'=>22,'D'=>30,'E'=>12,'F'=>16,'G'=>14,
                'H'=>8,'I'=>10,'J'=>12,'K'=>12,
            ];
            $lastCol = 'K';
        }
        foreach ($colDefs as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->getColumnDimension('A')->setWidth(3);

        // ── Row heights ──────────────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(28);
        $sheet->getRowDimension(5)->setRowHeight(6);
        $sheet->getRowDimension(9)->setRowHeight(6);

        // ── Row 1-4: Header block ────────────────────────────────────────
        // Left: logo placeholder + company name
        $sheet->mergeCells('B1:D4');
        $sheet->setCellValue('B1', "CONSORCIO RODRIGUEZ CABALLERO SAC");
        $sheet->getStyle('B1')
            ->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('B1')
            ->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM)
            ->setWrapText(true);

        // Right: big name
        $sheet->mergeCells("F1:{$lastCol}1");
        $sheet->setCellValue('F1', 'CONSORCIO RODRIGUEZ CABALLERO');
        $sheet->getStyle("F1:{$lastCol}1")
            ->getFont()->setBold(true)->setSize(16);
        $this->styleCenter($sheet, "F1:{$lastCol}1");

        $sheet->mergeCells("F2:{$lastCol}2");
        $sheet->setCellValue('F2', 'RUC:20482304665');
        $sheet->getStyle("F2:{$lastCol}2")
            ->getFont()->setBold(true)->setSize(12);
        $this->styleCenter($sheet, "F2:{$lastCol}2");

        $sheet->mergeCells("F3:{$lastCol}3");
        $sheet->setCellValue('F3', 'Abastecimiento de agua en cisternas, venta de agregados para la construcción, alquiler maquinaria pesada y otras actividades de transporte.');
        $sheet->getStyle("F3:{$lastCol}3")
            ->getFont()->setSize(7)->getColor()->setRGB('0070C0');
        $this->styleCenter($sheet, "F3:{$lastCol}3");
        $sheet->getStyle("F3:{$lastCol}3")->getAlignment()->setWrapText(true);

        // ── Row 6: VALORIZACION + PERIODO ────────────────────────────────
        $tipoLabel = $esMaquinaria ? 'ALQUILER DE ' : '';
        $valLabel  = strtoupper($tipoLabel . $itemNombre) . ' - ' . $cotizacion->numero_valorizacion;

        $sheet->setCellValue('B6', 'VALORIZACION:');
        $sheet->getStyle('B6')->getFont()->setBold(true)->setUnderline(true);

        $sheet->mergeCells('C6:E6');
        $sheet->setCellValue('C6', $valLabel);
        $sheet->getStyle('C6:E6')
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
        $sheet->getStyle('C6:E6')->getFont()->setBold(true);

        $sheet->setCellValue('F6', 'PERIODO');
        $sheet->getStyle('F6')->getFont()->setBold(true)->setUnderline(true);
        $sheet->setCellValue('G6', strtoupper($periodoInicio));
        $sheet->setCellValue('H6', 'AL');
        $this->styleCenter($sheet, 'H6');
        $sheet->mergeCells("I6:{$lastCol}6");
        $sheet->setCellValue('I6', strtoupper($periodoFin));

        // ── Row 7: EMPRESA + RUC ─────────────────────────────────────────
        $sheet->setCellValue('B7', 'EMPRESA:');
        $sheet->getStyle('B7')->getFont()->setBold(true)->setUnderline(true);
        $sheet->mergeCells('C7:E7');
        $sheet->setCellValue('C7', strtoupper($cotizacion->razon_social));

        $sheet->setCellValue('F7', 'RUC:');
        $sheet->getStyle('F7')->getFont()->setBold(true)->setUnderline(true);
        $sheet->setCellValue('G7', $cotizacion->ruc);

        // ── Row 8: OBRA ──────────────────────────────────────────────────
        $sheet->setCellValue('B8', 'OBRA:');
        $sheet->getStyle('B8')->getFont()->setBold(true)->setUnderline(true);
        $sheet->mergeCells('C8:E8');
        $sheet->setCellValue('C8', strtoupper($cotizacion->obra));

        // ── Row 10: Company title ─────────────────────────────────────────
        $sheet->mergeCells("B10:{$lastCol}10");
        $sheet->setCellValue('B10', strtoupper($cotizacion->razon_social));
        $this->styleCenter($sheet, "B10:{$lastCol}10");
        $sheet->getStyle("B10:{$lastCol}10")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("B10:{$lastCol}10")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("B10:{$lastCol}10")
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
        $sheet->getRowDimension(10)->setRowHeight(20);

        // ── Row 11: Item name ─────────────────────────────────────────────
        $sheet->mergeCells("B11:{$lastCol}11");
        $sheet->setCellValue('B11', '1.-' . strtoupper($itemNombre));
        $this->styleCenter($sheet, "B11:{$lastCol}11");
        $sheet->getStyle("B11:{$lastCol}11")->getFont()->setBold(true);
        $sheet->getStyle("B11:{$lastCol}11")
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF99');
        $sheet->getStyle("B11:{$lastCol}11")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(11)->setRowHeight(18);

        // ── Row 12: Column headers ────────────────────────────────────────
        $hdrRow = 12;
        if ($esMaquinaria) {
            $headers = ['FECHA','CHOFER','CISTERNA/VOLQUETE/MAQUINARIA','PLACA/DESCRIPCION','OBRA','N° PARTE DIARIO','HI','HT','HORAS TRABAJADAS','HORAS MINIMAS','PRECIO','TOTAL'];
            $cols    = ['B','C','D','E','F','G','H','I','J','K','L','M'];
        } else {
            $headers = ['FECHA','CHOFER','DETALLE','PLACA','OBRA','N° PARTE DIARIO','M3','PRECIO','TOTAL','GRR'];
            $cols    = ['B','C','D','E','F','G','H','I','J','K'];
        }

        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $hdrRow, $h);
        }

        $hdrRange = "B{$hdrRow}:{$lastCol}{$hdrRow}";
        $sheet->getStyle($hdrRange)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($hdrRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('404040');
        $sheet->getStyle($hdrRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle($hdrRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension($hdrRow)->setRowHeight(30);

        // ── Data rows ─────────────────────────────────────────────────────
        $dataStart = $hdrRow + 1;
        $r = $dataStart;

        foreach ($filas as $f) {
            if ($esMaquinaria) {
                $vals = [
                    \Carbon\Carbon::parse($f->fecha)->format('d/m/Y'),
                    strtoupper($f->chofer_nombre),
                    strtoupper($f->maquinaria_nombre),
                    strtoupper($f->placa ?? ''),
                    strtoupper($f->obra_maquina ?? $cotizacion->obra),
                    $f->n_parte_diario ?? '',
                    (float) $f->hora_inicio,
                    (float) $f->hora_fin,
                    (float) $f->horas_trabajadas,
                    (int)   $f->hora_minima,
                    (float) $f->precio_hora,
                    (float) $f->total_fila,
                ];
                // Numeric cols alignment right
                $numCols = ['H','I','J','K','L','M'];
            } else {
                $vals = [
                    \Carbon\Carbon::parse($f->fecha)->format('d/m/Y'),
                    strtoupper($f->chofer_nombre),
                    strtoupper($f->agregado_nombre),
                    strtoupper($f->placa ?? ''),
                    strtoupper($f->obra_agregado ?? $cotizacion->obra),
                    $f->n_parte_diario ?? '',
                    (float) $f->m3,
                    (float) $f->precio_m3,
                    (float) $f->total_fila,
                    strtoupper($f->grr ?? ''),
                ];
                $numCols = ['H','I','J'];
            }

            foreach ($vals as $i => $val) {
                $sheet->setCellValue($cols[$i] . $r, $val);
            }

            foreach ($numCols as $nc) {
                $sheet->getStyle($nc . $r)
                    ->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle($nc . $r)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $sheet->getStyle("B{$r}:{$lastCol}{$r}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("B{$r}:{$lastCol}{$r}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($r)->setRowHeight(14);
            $r++;
        }

        // ── TOTAL row ─────────────────────────────────────────────────────
        $totalRow = $r;
        $sheet->getRowDimension($totalRow)->setRowHeight(18);

        if ($esMaquinaria) {
            $sheet->mergeCells("B{$totalRow}:I{$totalRow}");
            $sheet->setCellValue("B{$totalRow}", 'TOTAL');
            $this->styleCenter($sheet, "B{$totalRow}");
            $sheet->setCellValue("J{$totalRow}", round($filas->sum('horas_trabajadas'), 2));
            $sheet->getStyle("J{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->mergeCells("L{$totalRow}:M{$totalRow}");
            $sheet->setCellValue("L{$totalRow}", 'S/   ' . number_format($cotizacion->total, 2));
            $sheet->getStyle("L{$totalRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        } else {
            $sheet->mergeCells("B{$totalRow}:G{$totalRow}");
            $sheet->setCellValue("B{$totalRow}", 'TOTAL');
            $this->styleCenter($sheet, "B{$totalRow}");
            $sheet->setCellValue("H{$totalRow}", round($filas->sum('m3'), 0));
            $sheet->getStyle("H{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->mergeCells("J{$totalRow}:K{$totalRow}");
            $sheet->setCellValue("J{$totalRow}", 'S/   ' . number_format($cotizacion->total, 2));
            $sheet->getStyle("J{$totalRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("B{$totalRow}:{$lastCol}{$totalRow}")
            ->getFont()->setBold(true);
        $sheet->getStyle("B{$totalRow}:{$lastCol}{$totalRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // ── Summary box: BASE / IGV / TOTAL ──────────────────────────────
        $s1 = $totalRow + 2;
        $s2 = $s1 + 1;
        $s3 = $s2 + 1;

        if ($esMaquinaria) {
            $valCol = 'J'; $lblCol = 'L'; $numCol = 'M';
        } else {
            $valCol = 'H'; $lblCol = 'I'; $numCol = 'J';
        }

        // Optional: repeat total on left
        $sheet->setCellValue("{$valCol}{$s1}", (float) $cotizacion->total);
        $sheet->getStyle("{$valCol}{$s1}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue("{$valCol}{$s2}", 0);
        $sheet->getStyle("{$valCol}{$s2}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Summary labels + values
        foreach ([
                     [$s1, 'BASE', (float) $cotizacion->base_sin_igv],
                     [$s2, 'IGV',  (float) $cotizacion->total_igv],
                     [$s3, 'TOTAL',(float) $cotizacion->total],
                 ] as [$sr, $lbl, $val]) {
            $sheet->setCellValue("{$lblCol}{$sr}", $lbl);
            $sheet->getStyle("{$lblCol}{$sr}")->getFont()->setBold(true);
            $sheet->setCellValue("{$numCol}{$sr}", $val);
            $sheet->getStyle("{$numCol}{$sr}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("{$numCol}{$sr}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$lblCol}{$sr}:{$numCol}{$sr}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        $sheet->getStyle("{$numCol}{$s3}")->getFont()->setBold(true);
        $sheet->getStyle("{$numCol}{$s3}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFC0');
    }

    // ── DB helpers ─────────────────────────────────────────────────────────
    private function getCotizacionWithDetails(int $id): ?object
    {
        return DB::table('cotizacion as c')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->leftJoin('maquinaria as m', 'm.id_maquinaria', '=', 'c.id_maquinaria')
            ->leftJoin('agregado as a', 'a.id_agregado', '=', 'c.id_agregado')
            ->where('c.id_cotizacion', $id)
            ->where('c.activo', 1)
            ->select('c.*', 'cl.razon_social', 'cl.ruc',
                'm.nombre as maquinaria_nombre',
                'a.nombre as agregado_nombre')
            ->first();
    }

    private function getFilas(object $cotizacion): \Illuminate\Support\Collection
    {
        if ($cotizacion->tipo_cotizacion === 'MAQUINARIA') {
            return DB::table('maquinaria_cotizacion as mc')
                ->join('chofer as ch', 'ch.id_chofer', '=', 'mc.id_chofer')
                ->join('maquinaria as m', 'm.id_maquinaria', '=', 'mc.id_maquinaria')
                ->where('mc.id_cotizacion', $cotizacion->id_cotizacion)
                ->where('mc.activo', 1)
                ->select('mc.*', 'ch.nombres as chofer_nombre', 'm.nombre as maquinaria_nombre')
                ->orderBy('mc.fecha')->orderBy('mc.hora_inicio')
                ->get();
        }
        return DB::table('agregado_cotizacion as ac')
            ->join('chofer as ch', 'ch.id_chofer', '=', 'ac.id_chofer')
            ->join('agregado as a', 'a.id_agregado', '=', 'ac.id_agregado')
            ->where('ac.id_cotizacion', $cotizacion->id_cotizacion)
            ->where('ac.activo', 1)
            ->select('ac.*', 'ch.nombres as chofer_nombre', 'a.nombre as agregado_nombre')
            ->orderBy('ac.fecha')
            ->get();
    }

    private function styleCenter(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function streamDownload(Spreadsheet $spreadsheet, string $filename)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cot_');
        (new Xlsx($spreadsheet))->save($tempFile);
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
