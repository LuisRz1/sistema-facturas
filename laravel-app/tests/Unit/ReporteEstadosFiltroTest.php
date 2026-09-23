<?php

namespace Tests\Unit;

use App\Http\Controllers\ReporteController;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReporteEstadosFiltroTest extends TestCase
{
    private function resolver(array $query): array
    {
        $metodo = new ReflectionMethod(ReporteController::class, 'resolverEstadosFiltro');
        $metodo->setAccessible(true);

        return $metodo->invoke(new ReporteController(), Request::create('/reportes/pdf', 'GET', $query));
    }

    private function etiqueta(array $estados): string
    {
        $metodo = new ReflectionMethod(ReporteController::class, 'estadoLabel');
        $metodo->setAccessible(true);

        return $metodo->invoke(new ReporteController(), $estados);
    }

    public function test_sin_filtro_devuelve_todos_los_estados(): void
    {
        $estados = $this->resolver([]);

        foreach (['PENDIENTE', 'VENCIDO', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE', 'POR VALIDAR DETRACCION', 'PAGADA', 'ANULADO'] as $estado) {
            $this->assertContains($estado, $estados);
        }
        $this->assertSame('TODOS LOS ESTADOS', $this->etiqueta($estados));
    }

    public function test_estado_vacio_equivale_a_todos(): void
    {
        $this->assertSame($this->resolver([]), $this->resolver(['estado' => '']));
    }

    public function test_estado_unico_se_respeta(): void
    {
        $this->assertSame(['PAGADA'], $this->resolver(['estado' => 'PAGADA']));
    }

    public function test_pendiente_agrega_anulado_para_notas_de_credito(): void
    {
        $estados = $this->resolver(['estado' => 'PENDIENTE']);

        $this->assertContains('PENDIENTE', $estados);
        $this->assertContains('ANULADO', $estados);
        $this->assertNotSame('TODOS LOS ESTADOS', $this->etiqueta($estados));
    }

    public function test_estados_multiples_se_respetan(): void
    {
        $estados = $this->resolver(['estados' => ['VENCIDO', 'PAGADA']]);

        $this->assertContains('VENCIDO', $estados);
        $this->assertContains('PAGADA', $estados);
        $this->assertNotContains('PENDIENTE', $estados);
    }
}
