<?php

namespace App\Services\Conciliacion\Parsers;

use App\Services\Conciliacion\MovimientoEstandar;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InterbankParser implements IBankStatementParser
{
    private const MOVIMIENTOS_IGNORABLES = [
        'ITF', 'COMISION', 'MANTENIMIENTO DE CUENTA', 'N/D VARIOS',
    ];

    public function detectar(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $firstRow = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $firstRow[] = strtoupper(trim((string) $cell->getValue()));
                }
            }
            $headerStr = implode(' ', $firstRow);

            $tieneFechaOp = str_contains($headerStr, 'FECHA DE OP');
            $tieneMovimiento = str_contains($headerStr, 'MOVIMIENTO');
            $tieneAbonos = str_contains($headerStr, 'ABONOS') || str_contains($headerStr, 'CARGOS');

            if ($tieneFechaOp && $tieneMovimiento && $tieneAbonos) {
                return ['ok' => true, 'banco' => 'IBK', 'moneda' => 'PEN'];
            }

            return ['ok' => false, 'error' => 'ERR-007: No se detecto formato Interbank.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'ERR-002: No se pudo leer el archivo.'];
        }
    }

    public function validarEstructura(string $filePath): array
    {
        return ['ok' => true];
    }

    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $movimientos = [];
        $isFirst = true;

        foreach ($sheet->getRowIterator() as $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            if (empty($cells[0])) {
                continue;
            }

            $mov = new MovimientoEstandar();
            $mov->fecha_operacion = $this->parseFecha($cells[0] ?? null);    // FECHA DE OP
            $mov->fecha_proceso = $this->parseFecha($cells[1] ?? null);      // FECHA DE PROC
            $mov->tipo_movimiento_raw = strtoupper($cells[2] ?? '');          // MOVIMIENTO
            $mov->referencia = $cells[3] ?? '';                               // DETALLE
            $mov->numero_operacion = $cells[4] ?? '';                         // CODIGO DE OPERACION

            // Signo explicito en columnas separadas
            $abono = $this->parseMonto($cells[7] ?? '0');
            $cargo = $this->parseMonto($cells[8] ?? '0');

            if ($abono > 0) {
                $mov->tipo_movimiento = 'ABONO';
                $mov->importe = $abono;
                $mov->abono = $abono;
                $mov->cargo = 0;
            } else {
                $mov->tipo_movimiento = 'CARGO';
                $mov->importe = $cargo;
                $mov->cargo = $cargo;
                $mov->abono = 0;
            }

            $mov->saldo = $this->parseMonto($cells[9] ?? '0');
            $mov->banco = 'IBK';

            // Clasificar si es ignorable
            $mov->es_ignorable = false;
            foreach (self::MOVIMIENTOS_IGNORABLES as $ignorable) {
                if (str_contains($mov->tipo_movimiento_raw, $ignorable)) {
                    $mov->es_ignorable = true;
                    break;
                }
            }

            $mov->es_transferencia_tercero =
                str_contains($mov->tipo_movimiento_raw, 'ABONO TRANSFERENCIA')
                && ! empty($mov->referencia);

            $movimientos[] = $mov;
        }

        return $movimientos;
    }

    private function parseFecha(?string $v): ?string
    {
        if (! $v || $v === '0') {
            return null;
        }
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d');
        } catch (\Throwable $e) {
            try {
                return \Carbon\Carbon::parse($v)->format('Y-m-d');
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }

    private function parseMonto(string $v): float
    {
        $v = trim($v);
        if ($v === '' || $v === '0') {
            return 0.0;
        }
        $v = str_replace(',', '', $v);
        return (float) filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
