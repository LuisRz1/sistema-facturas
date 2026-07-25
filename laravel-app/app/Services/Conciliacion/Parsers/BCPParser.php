<?php

namespace App\Services\Conciliacion\Parsers;

use App\Services\Conciliacion\MovimientoEstandar;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BCPParser implements IBankStatementParser
{
    private const COLUMNAS_OBLIGATORIAS = ['FECHA PROC', 'FECHA VALOR', 'DESCRIPCION', 'TIPO', 'CARGO/ABONO', 'SALDO'];

    private const TIPOS_IGNORABLES = [
        '0909', // ITF
        '0101', // Comision mantenimiento
        '4991', // Envio estado de cuenta
        '4409', // Comision tarjeta
        '4936', // Comision tarjeta
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

            $tieneFechaProc = str_contains($headerStr, 'FECHA PROC');
            $tieneNumOp = str_contains($headerStr, 'NUM OP');
            $tieneTipo = str_contains($headerStr, 'TIPO');

            if ($tieneFechaProc && $tieneNumOp && $tieneTipo) {
                $moneda = str_contains($headerStr, 'DOLARES') ? 'USD' : 'PEN';
                return ['ok' => true, 'banco' => 'BCP', 'moneda' => $moneda];
            }

            return ['ok' => false, 'error' => 'ERR-007: No se detecto formato BCP.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'ERR-002: No se pudo leer el archivo.'];
        }
    }

    public function validarEstructura(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $headers = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $headers[] = strtoupper(trim((string) $cell->getValue()));
                }
            }

            $faltantes = [];
            foreach (self::COLUMNAS_OBLIGATORIAS as $col) {
                $encontrada = false;
                foreach ($headers as $h) {
                    if (str_contains($h, $col)) {
                        $encontrada = true;
                        break;
                    }
                }
                if (! $encontrada) {
                    $faltantes[] = $col;
                }
            }

            return empty($faltantes)
                ? ['ok' => true]
                : ['ok' => false, 'error' => 'ERR-004: Columnas faltantes: '.implode(', ', $faltantes)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'ERR-002: No se pudo validar el archivo.'];
        }
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

            if (empty($cells[0]) && empty($cells[1])) {
                continue;
            }

            $mov = new MovimientoEstandar();
            $mov->fecha_proceso = $this->parseFecha($cells[0] ?? null);
            $mov->fecha_operacion = $this->parseFecha($cells[1] ?? null);
            $mov->descripcion = $cells[2] ?? '';
            $mov->referencia = trim(
                ($cells[3] ?? '').' '.($cells[4] ?? '').' '.($cells[5] ?? '')
            );
            $mov->numero_operacion = $cells[6] ?? '';
            $mov->hora = $cells[7] ?? null;
            $mov->codigo_interno_banco = $cells[9] ?? '';

            // Importe y tipo — el signo viene como sufijo "-" en CARGO/ABONO
            $cargoAbonoRaw = $cells[10] ?? '0';
            $importe = $this->parseMonto($cargoAbonoRaw);
            $mov->importe = abs($importe);
            $mov->tipo_movimiento = $importe >= 0 ? 'ABONO' : 'CARGO';

            if ($mov->tipo_movimiento === 'ABONO') {
                $mov->abono = abs($importe);
                $mov->cargo = 0;
            } else {
                $mov->cargo = abs($importe);
                $mov->abono = 0;
            }

            $mov->saldo = $this->parseMonto($cells[11] ?? '0');
            $mov->banco = 'BCP';

            // Clasificar si es ignorable
            $mov->es_ignorable = in_array($mov->codigo_interno_banco, self::TIPOS_IGNORABLES);

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
        // BCP usa formato con coma para miles y posible "-" al final para cargos
        $esNegativo = str_ends_with($v, '-');
        $v = rtrim($v, '-');
        $v = str_replace(',', '', $v);
        $monto = (float) filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        return $esNegativo ? -$monto : $monto;
    }
}
