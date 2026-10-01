<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventDuplicateSubmission;
use Illuminate\Http\Request;
use Tests\TestCase;

class PreventDuplicateSubmissionTest extends TestCase
{
    private function crear(array $datos): Request
    {
        return Request::create('/x', 'POST', $datos, [], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    public function test_bloquea_el_segundo_envio_identico(): void
    {
        $mw = new PreventDuplicateSubmission();
        $next = fn () => response('ok');

        $this->assertSame(200, $mw->handle($this->crear(['monto' => 10]), $next)->getStatusCode());
        $this->assertSame(409, $mw->handle($this->crear(['monto' => 10]), $next)->getStatusCode());
    }

    public function test_permite_envios_distintos(): void
    {
        $mw = new PreventDuplicateSubmission();
        $next = fn () => response('ok');

        $this->assertSame(200, $mw->handle($this->crear(['monto' => 10]), $next)->getStatusCode());
        $this->assertSame(200, $mw->handle($this->crear(['monto' => 20]), $next)->getStatusCode());
    }

    public function test_no_bloquea_metodos_safe(): void
    {
        $mw = new PreventDuplicateSubmission();
        $next = fn () => response('ok');

        $this->assertSame(200, $mw->handle(Request::create('/x', 'GET'), $next)->getStatusCode());
        $this->assertSame(200, $mw->handle(Request::create('/x', 'GET'), $next)->getStatusCode());
    }

    public function test_libera_el_bloqueo_cuando_la_operacion_falla(): void
    {
        $mw = new PreventDuplicateSubmission();
        $nextFalla = fn () => response()->json(['message' => 'invalido'], 422);
        $nextOk = fn () => response('ok');

        $this->assertSame(422, $mw->handle($this->crear(['monto' => 10]), $nextFalla)->getStatusCode());
        $this->assertSame(200, $mw->handle($this->crear(['monto' => 10]), $nextOk)->getStatusCode());
    }
}
