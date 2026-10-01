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

    public function test_detallar_filas_marca_la_oc_unica_y_las_horas_sin_oc(): void
    {
        $service = new ValorizacionOcService();

        $conOc = (object) [
            'id_orden_compra' => 5, 'oc_numero' => 'OC-1',
            'horas_trabajadas' => 2, 'hora_minima' => 3,
            'total_fila' => 100, 'es_facturable' => true, 'es_ajuste_horometro' => false,
        ];
        $sinOc = (object) [
            'id_orden_compra' => null,
            'horas_trabajadas' => 4, 'hora_minima' => 0,
            'total_fila' => 100, 'es_facturable' => true, 'es_ajuste_horometro' => false,
        ];

        $filas = $service->detallarFilas(collect([$conOc, $sinOc]), true);

        $this->assertSame('OC-1', $filas[0]->oc_asignaciones->first()->numero);
        $this->assertSame(3.0, (float) $filas[0]->oc_asignaciones->first()->horas_asignadas);
        $this->assertSame(0.0, (float) $filas[0]->horas_sin_oc);

        $this->assertTrue($filas[1]->oc_asignaciones->isEmpty());
        $this->assertSame(4.0, (float) $filas[1]->horas_sin_oc);
    }

    public function test_control_inactivo_no_reporta_horas_sin_oc(): void
    {
        $service = new ValorizacionOcService();
        $fila = (object) [
            'id_orden_compra' => null,
            'horas_trabajadas' => 4, 'hora_minima' => 0,
            'total_fila' => 100, 'es_facturable' => true, 'es_ajuste_horometro' => false,
        ];

        $resultado = $service->detallarFilas(collect([$fila]), false);

        $this->assertSame(0.0, (float) $resultado[0]->horas_sin_oc);
        $this->assertTrue($resultado[0]->oc_asignaciones->isEmpty());
    }
}
