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

    #[DataProvider('casosDeConversionAbono')]
    public function test_convierte_el_abono_a_la_moneda_de_la_factura(
        float $monto,
        string $monedaPago,
        string $monedaFactura,
        ?float $montoCambio,
        ?float $esperado,
    ): void {
        $resultado = (new SaldoFacturaService())->montoAbonoEnMonedaFactura(
            $monto,
            $monedaPago,
            $monedaFactura,
            $montoCambio,
        );

        if ($esperado === null) {
            $this->assertNull($resultado);
            return;
        }

        $this->assertEqualsWithDelta($esperado, $resultado, 0.001);
    }

    public static function casosDeConversionAbono(): array
    {
        return [
            'misma moneda no convierte' => [375.00, 'PEN', 'PEN', 3.75, 375.00],
            'USD igual no convierte' => [100.00, 'USD', 'USD', null, 100.00],
            'soles a USD con TC' => [375.00, 'PEN', 'USD', 3.75, 100.00],
            'soles a USD sin TC devuelve null' => [375.00, 'PEN', 'USD', null, null],
            'soles a USD TC cero devuelve null' => [375.00, 'PEN', 'USD', 0.0, null],
            'USD a soles con TC' => [100.00, 'USD', 'PEN', 3.75, 375.00],
            'redondeo a dos decimales' => [100.00, 'PEN', 'USD', 3.00, 33.33],
        ];
    }
}
