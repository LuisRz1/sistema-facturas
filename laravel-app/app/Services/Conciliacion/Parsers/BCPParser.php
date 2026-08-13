<?php

namespace App\Services\Conciliacion\Parsers;

use App\Services\Conciliacion\MovimientoEstandar;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BCPParser implements IBankStatementParser
{
    /**
     * Codigos UTC del BCP que se IGNORAN (no son pagos de clientes).
     * 0909 = ITF, 4401 = transferencia CCE (cargos internos)
     */
    private const UTC_IGNORABLES = ['0909', '4401', '0101', '4991', '4409', '4936'];

    public function detectar(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Leer metadata rows
            $row1 = $this->getRowCells($sheet, 1);
            $row2 = $this->getRowCells($sheet, 2);
            $row3 = $this->getRowCells($sheet, 3);
            $row5 = $this->getRowCells($sheet, 5);

            // Normalize: remove accents for comparison
            $row1Str = $this->normalizar(strtoupper(implode(' ', $row1)));
            $row2Str = $this->normalizar(strtoupper(implode(' ', $row2)));
            $row5Str = $this->normalizar(strtoupper(implode(' ', $row5)));

            // Detectar BCP: Row 1 tiene "Cuenta", Row 2 tiene "Moneda", Row 5 tiene headers
            $tieneCuenta = str_contains($row1Str, 'CUENTA');
            $tieneMoneda = str_contains($row2Str, 'MONEDA');
            $tieneFechaHeader = str_contains($row5Str, 'FECHA');
            $tieneDescOp = str_contains($row5Str, 'DESCRIPCI') || str_contains($row5Str, 'OPERACI');
            $tieneMonto = str_contains($row5Str, 'MONTO');
            $tieneUTC = str_contains($row5Str, 'UTC');

            if ($tieneCuenta && $tieneMoneda && $tieneFechaHeader && $tieneDescOp && $tieneMonto) {
                $moneda = str_contains($row2Str, 'SOLES') ? 'PEN' : (str_contains($row2Str, 'DOLARES') ? 'USD' : 'PEN');

                // Extract account number from row 1
                $cuenta = '';
                if (preg_match('/(\d{3}-\d{6,10}-\d-\d{2})/', $row1Str, $m)) {
                    $cuenta = $m[1];
                }

                return [
                    'ok' => true,
                    'banco' => 'BCP',
                    'moneda' => $moneda,
                    'cuenta' => $cuenta,
                ];
            }

            return ['ok' => false, 'error' => 'ERR-007: No se detecto formato BCP.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'ERR-002: No se pudo leer el archivo: ' . $e->getMessage()];
        }
    }

    public function validarEstructura(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $row5 = $this->getRowCells($sheet, 5);
            $headerStr = $this->normalizar(strtoupper(implode(' ', $row5)));

            $requeridas = ['FECHA', 'DESCRIPCI', 'MONTO', 'SALDO'];
            $faltantes = [];
            foreach ($requeridas as $col) {
                if (!str_contains($headerStr, $col)) {
                    $faltantes[] = $col;
                }
            }

            return empty($faltantes)
                ? ['ok' => true]
                : ['ok' => false, 'error' => 'ERR-004: Columnas faltantes: ' . implode(', ', $faltantes)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'ERR-002: No se pudo validar el archivo.'];
        }
    }

    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $movimientos = [];
        $rowIndex = 0;

        // Extract account number from row 1
        $row1 = $this->getRowCells($sheet, 1);
        $row1Str = implode(' ', $row1);
        $cuenta = '';
        if (preg_match('/(\d{3}-\d{6,10}-\d-\d{2})/', $row1Str, $m)) {
            $cuenta = $m[1];
        }

        // Extract moneda from row 2
        $row2 = $this->getRowCells($sheet, 2);
        $row2Str = strtoupper(implode(' ', $row2));
        $moneda = str_contains($row2Str, 'SOLES') ? 'PEN' : (str_contains($row2Str, 'DOLARES') ? 'USD' : 'PEN');

        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex++;
            if ($rowIndex <= 5) continue; // Skip metadata + headers

            $cells = $this->getRowCells($sheet, $rowIndex);
            if (empty($cells[0]) && empty($cells[2])) continue; // Empty row

            $mov = new MovimientoEstandar();
            $mov->banco = 'BCP';
            $mov->moneda = $moneda;
            $mov->cuenta_bancaria = $cuenta;

            // ── Mapeo de columnas del BCP real ──
            // A(0): Fecha → fecha_operacion
            // B(1): Fecha valuta → fecha_proceso
            // C(2): Descripción operación → descripcion (contiene nombre del cliente!)
            // D(3): Monto (signed: negativo=cargo, positivo=abono)
            // E(4): Saldo
            // F(5): Sucursal - agencia
            // G(6): Operación - Número → numero_operacion
            // H(7): Operación - Hora → hora
            // I(8): Usuario
            // J(9): UTC → codigo_interno_banco
            // K(10): Referencia2 → referencia (factura numbers!)

            $mov->fecha_operacion = $this->parseFecha($cells[0] ?? null);
            $mov->fecha_proceso = $this->parseFecha($cells[1] ?? null);
            $mov->descripcion = trim($cells[2] ?? '');
            $mov->saldo = $this->parseMonto($cells[4] ?? '0');
            $mov->referencia = trim(
                ($cells[5] ?? '') . ' | ' . ($cells[10] ?? '')
            );
            $mov->numero_operacion = $cells[6] ?? '';
            $mov->hora = $cells[7] ?? null;
            $mov->codigo_interno_banco = $cells[9] ?? '';

            // ── Importe: ya viene con signo en columna D ──
            $importe = $this->parseMonto($cells[3] ?? '0');
            $mov->importe = abs($importe);
            $mov->tipo_movimiento = $importe > 0 ? 'ABONO' : 'CARGO';

            if ($mov->tipo_movimiento === 'ABONO') {
                $mov->abono = abs($importe);
                $mov->cargo = 0;
            } else {
                $mov->cargo = abs($importe);
                $mov->abono = 0;
            }

            // ── Clasificar ──
            $utc = strtoupper($mov->codigo_interno_banco);

            // Siempre ignorar ITF y CCE transfers
            $mov->es_ignorable = in_array($utc, self::UTC_IGNORABLES);

            // Detectar si es transferencia de tercero (potencial pago de cliente)
            $descUpper = strtoupper($mov->descripcion);
            $mov->es_transferencia_tercero =
                $mov->tipo_movimiento === 'ABONO'
                && !$mov->es_ignorable
                && (
                    str_contains($descUpper, 'TRAN.CTAS.TERC')
                    || str_contains($descUpper, 'DE ')
                    || str_contains($descUpper, 'TRANSFERENCIA')
                    || in_array($utc, ['2401', '2701', '1018', '2014', '1001', '2003'])
                );

            $movimientos[] = $mov;
        }

        return $movimientos;
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function normalizar(string $s): string
    {
        return strtr($s, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
        ]);
    }

    private function getRowCells($sheet, int $rowIndex): array
    {
        $cells = [];
        $highestCol = $sheet->getHighestColumn();
        $colIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        for ($col = 1; $col <= $colIndex; $col++) {
            $cell = $sheet->getCell([$col, $rowIndex]);
            $val = trim((string) $cell->getValue());
            $cells[] = $val;
        }
        // Trim trailing empty cells
        while (!empty($cells) && end($cells) === '') {
            array_pop($cells);
        }
        return $cells;
    }

    private function parseFecha(?string $v): ?string
    {
        if (!$v || $v === '0' || $v === '') return null;
        try {
            // BCP usa d/m/Y
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
        if ($v === '' || $v === '0') return 0.0;
        $v = str_replace(',', '', $v);
        return (float) filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
