<?php

namespace Tests\Unit;

use App\Services\SaldoFacturaService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SaldoFacturaServiceTest extends TestCase
{
    #[DataProvider('casosDeSaldo')]
    public function test_calcula_el_saldo_con_la_regla_unificada(array $input, float $esperado): void
    {
        $saldo = (new SaldoFacturaService())->calcular(...$input);

        $this->assertSame($esperado, $saldo);
    }

    public static function casosDeSaldo(): array
    {
        return [
            'sin recaudacion' => [[
                'importeTotal' => 118.00,
            ], 118.00],
            'recaudacion sin confirmar no descuenta' => [[
                'importeTotal' => 118.00,
                'totalRecaudacion' => 12.00,
                'tipoRecaudacion' => 'DETRACCION',
            ], 118.00],
            'recaudacion PEN confirmada descuenta' => [[
                'importeTotal' => 118.00,
                'montoAbonado' => 20.00,
                'totalRecaudacion' => 12.00,
                'fechaRecaudacion' => '2026-09-19',
                'tipoRecaudacion' => 'DETRACCION',
            ], 86.00],
            'recaudacion USD confirmada usa tipo de cambio' => [[
                'importeTotal' => 100.00,
                'totalRecaudacion' => 36.90,
                'fechaRecaudacion' => '2026-09-19',
                'moneda' => 'USD',
                'montoCambio' => 3.69,
                'tipoRecaudacion' => 'DETRACCION',
            ], 90.00],
            'USD sin tipo de cambio no mezcla monedas' => [[
                'importeTotal' => 100.00,
                'totalRecaudacion' => 36.90,
                'fechaRecaudacion' => '2026-09-19',
                'moneda' => 'USD',
                'tipoRecaudacion' => 'DETRACCION',
            ], 100.00],
            'pago directo parcial' => [[
                'importeTotal' => 118.00,
                'montoAbonado' => 25.00,
            ], 93.00],
            'nota de credito conserva importe negativo' => [[
                'importeTotal' => -118.00,
            ], -118.00],
            'factura anulada queda en cero' => [[
                'importeTotal' => 118.00,
                'estado' => 'ANULADO',
            ], 0.00],
            'autodetraccion no descuenta' => [[
                'importeTotal' => 118.00,
                'totalRecaudacion' => 12.00,
                'fechaRecaudacion' => '2026-09-19',
                'tipoRecaudacion' => 'AUTODETRACCION',
            ], 118.00],
        ];
    }
}
