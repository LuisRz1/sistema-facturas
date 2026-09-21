<?php

namespace Tests\Unit;

use App\Services\ValorizacionOcService;
use PHPUnit\Framework\TestCase;

class ValorizacionOcServiceTest extends TestCase
{
    private function fila(int $id, float $trabajadas, float $minimas, ?bool $facturable = true, bool $ajuste = false): object
    {
        return (object) [
            'id_cotizacion_maqu' => $id,
            'horas_trabajadas' => $trabajadas,
            'hora_minima' => $minimas,
            'total_fila' => $facturable === false ? 0 : 100,
            'es_facturable' => $facturable,
            'es_ajuste_horometro' => $ajuste,
        ];
    }

    public function test_usa_horas_facturables_y_excluye_ajustes_y_filas_sin_cobro(): void
    {
        $this->assertSame(300, ValorizacionOcService::horasFacturables($this->fila(1, 2, 3)));
        $this->assertSame(425, ValorizacionOcService::horasFacturables($this->fila(2, 4.25, 3)));
        $this->assertSame(0, ValorizacionOcService::horasFacturables($this->fila(3, 5, 3, false)));
        $this->assertSame(0, ValorizacionOcService::horasFacturables($this->fila(4, 5, 3, true, true)));
    }

    public function test_reparte_una_fila_entre_dos_oc_y_deja_exceso_sin_asignar(): void
    {
        $ordenes = collect([
            (object) ['id_orden_compra' => 10, 'horas_autorizadas' => 120],
            (object) ['id_orden_compra' => 20, 'horas_autorizadas' => 30],
        ]);
        $filas = collect([
            $this->fila(1, 118, 3),
            $this->fila(2, 5, 3),
            $this->fila(3, 29, 3),
            $this->fila(4, 10, 3, false),
        ]);

        $this->assertSame([
            ['id_cotizacion_maqu' => 1, 'id_orden_compra' => 10, 'horas_asignadas' => 118],
            ['id_cotizacion_maqu' => 2, 'id_orden_compra' => 10, 'horas_asignadas' => 2],
            ['id_cotizacion_maqu' => 2, 'id_orden_compra' => 20, 'horas_asignadas' => 3],
            ['id_cotizacion_maqu' => 3, 'id_orden_compra' => 20, 'horas_asignadas' => 27],
        ], ValorizacionOcService::repartir($ordenes, $filas));
    }

    public function test_recalcula_los_decimales_sin_deriva(): void
    {
        $ordenes = collect([(object) ['id_orden_compra' => 1, 'horas_autorizadas' => 0.3]]);
        $filas = collect([$this->fila(1, 0.1, 0), $this->fila(2, 0.2, 0)]);
        $this->assertSame(30, (int) round(array_sum(array_column(ValorizacionOcService::repartir($ordenes, $filas), 'horas_asignadas')) * 100));
    }
}
