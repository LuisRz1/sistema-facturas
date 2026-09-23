<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PagosFacturasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible en este entorno.');
        }
        $this->withoutMiddleware();

        Schema::create('cliente', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('tipo_cliente')->default('PERSONA JURIDICA');
        });
        Schema::create('factura', function (Blueprint $table) {
            $table->increments('id_factura');
            $table->unsignedInteger('id_cliente');
            $table->string('serie')->default('FF01');
            $table->unsignedInteger('numero');
            $table->string('moneda')->default('PEN');
            $table->string('estado')->default('PENDIENTE');
            $table->string('tipo_recaudacion')->nullable();
            $table->decimal('importe_total', 12, 2);
            $table->decimal('monto_abonado', 12, 2)->default(0);
            $table->decimal('monto_pendiente', 12, 2)->default(0);
            $table->decimal('monto_cambio', 10, 4)->nullable();
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->dateTime('fecha_actualizacion')->nullable();
            $table->boolean('activo')->default(true);
        });
        Schema::create('pago_factura', function (Blueprint $table) {
            $table->increments('id_pago');
            $table->unsignedInteger('id_factura');
            $table->decimal('monto_pagado', 12, 2);
            $table->string('moneda_pago', 3)->nullable();
            $table->decimal('monto_original', 12, 2)->nullable();
            $table->decimal('monto_cambio_pago', 10, 4)->nullable();
            $table->date('fecha_pago')->nullable();
            $table->string('cuenta_pago')->nullable();
            $table->string('ruta_comprobante_pago')->nullable();
            $table->string('numero_operacion')->nullable();
            $table->string('banco_origen')->nullable();
            $table->string('forma_pago')->nullable();
            $table->text('observacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_actualizacion')->nullable();
        });
        Schema::create('recaudacion', function (Blueprint $table) {
            $table->unsignedInteger('id_factura')->primary();
            $table->decimal('porcentaje', 8, 2)->default(0);
            $table->decimal('total_recaudacion', 12, 2)->default(0);
            $table->date('fecha_recaudacion')->nullable();
            $table->boolean('activo')->default(true);
        });

        DB::table('cliente')->insert([
            ['id_cliente' => 1], ['id_cliente' => 2],
        ]);
    }

    public function test_abonos_parciales_acumulan_hasta_pagar_la_factura(): void
    {
        $id = $this->factura(1, 100);

        $this->postJson("/facturas/{$id}/pago", ['monto_pagado' => 30])->assertOk()
            ->assertJsonPath('monto_pendiente', 70)->assertJsonPath('estado', 'DIFERENCIA PENDIENTE');
        $this->postJson("/facturas/{$id}/pago", ['monto_pagado' => 20])->assertOk()
            ->assertJsonPath('monto_abonado', 50)->assertJsonPath('monto_pendiente', 50);
        $this->postJson("/facturas/{$id}/pago", ['monto_pagado' => 50])->assertOk()
            ->assertJsonPath('monto_abonado', 100)->assertJsonPath('monto_pendiente', 0)
            ->assertJsonPath('estado', 'PAGADA');

        $this->assertSame(3, DB::table('pago_factura')->where('id_factura', $id)->where('activo', 1)->count());
    }

    public function test_editar_y_eliminar_un_abono_recalcula_el_saldo(): void
    {
        $id = $this->factura(1, 100);
        $this->postJson("/facturas/{$id}/pago", ['monto_pagado' => 40])->assertOk();
        $pagoId = DB::table('pago_factura')->value('id_pago');

        $this->putJson("/facturas/{$id}/pagos/{$pagoId}", [
            'monto_pagado' => 25, 'fecha_pago' => '2026-09-20',
        ])->assertOk()->assertJsonPath('monto_pendiente', 75);
        $this->deleteJson("/facturas/{$id}/pagos/{$pagoId}")->assertOk()
            ->assertJsonPath('monto_abonado', 0)->assertJsonPath('monto_pendiente', 100);
        $this->assertSame(0, (int) DB::table('pago_factura')->where('id_pago', $pagoId)->value('activo'));
    }

    public function test_pago_masivo_acepta_abonos_parciales_y_completa_en_otro_pago(): void
    {
        $primera = $this->factura(1, 100);
        $segunda = $this->factura(1, 80);

        $this->pagoMasivo(1, [[$primera, 40], [$segunda, 80]], 120)->assertOk()
            ->assertJsonPath('facturas_actualizadas', 2);
        $this->assertSaldo($primera, 40, 60, 'DIFERENCIA PENDIENTE');
        $this->assertSaldo($segunda, 80, 0, 'PAGADA');

        $this->pagoMasivo(1, [[$primera, 60]], 60)->assertOk();
        $this->assertSaldo($primera, 100, 0, 'PAGADA');
        $this->assertSame(3, DB::table('pago_factura')->where('activo', 1)->count());
    }

    public function test_pago_masivo_rechaza_exceso_duplicados_y_otro_cliente_sin_escrituras(): void
    {
        $primera = $this->factura(1, 100);
        $segunda = $this->factura(2, 80);

        $this->pagoMasivo(1, [[$primera, 101]], 101)->assertUnprocessable();
        $this->pagoMasivo(1, [[$primera, 40], [$primera, 20]], 60)->assertUnprocessable();
        $this->pagoMasivo(1, [[$primera, 40], [$segunda, 20]], 60)->assertUnprocessable();

        $this->assertSame(0, DB::table('pago_factura')->count());
        $this->assertSaldo($primera, 0, 100, 'PENDIENTE');
        $this->assertSaldo($segunda, 0, 80, 'PENDIENTE');
    }

    public function test_recaudacion_confirmada_se_descontara_y_no_se_duplica_en_los_abonos(): void
    {
        $id = $this->factura(1, 100, 'USD', 'DETRACCION', 3);
        DB::table('recaudacion')->insert([
            'id_factura' => $id, 'total_recaudacion' => 30,
            'fecha_recaudacion' => '2026-09-20', 'activo' => true,
        ]);
        DB::table('factura')->where('id_factura', $id)->update(['monto_pendiente' => 90]);

        $this->postJson("/facturas/{$id}/pago", ['monto_pagado' => 40])->assertOk()
            ->assertJsonPath('monto_pendiente', 50);
        $this->pagoMasivo(1, [[$id, 50]], 50)->assertOk();
        $this->assertSaldo($id, 90, 0, 'PAGADA');
    }

    public function test_confirmar_recaudacion_usd_guarda_el_tipo_de_cambio_y_descuenta_el_equivalente(): void
    {
        $id = $this->factura(1, 3465.09, 'USD', 'DETRACCION');

        $this->postJson("/facturas/{$id}/pago", [
            'monto_pagado' => 0,
            'tipo_recaudacion' => 'DETRACCION',
            'total_recaudacion' => 1299.00,
            'porcentaje_recaudacion' => 346.40,
            'monto_cambio' => 3.75,
            'fecha_recaudacion' => '2025-12-30',
            'validar_detraccion' => true,
        ])->assertOk()
            ->assertJsonPath('monto_pendiente', 3118.69)
            ->assertJsonPath('estado', 'DIFERENCIA PENDIENTE');

        $factura = DB::table('factura')->where('id_factura', $id)->first();
        $this->assertEqualsWithDelta(3.75, (float) $factura->monto_cambio, 0.0001);
        $this->assertEqualsWithDelta(3118.69, (float) $factura->monto_pendiente, 0.001);
    }

    public function test_desconfirmar_recaudacion_limpia_la_fecha_y_restablece_el_pendiente(): void
    {
        $id = $this->factura(1, 100, 'PEN', 'DETRACCION');
        DB::table('recaudacion')->insert([
            'id_factura' => $id,
            'total_recaudacion' => 10,
            'porcentaje' => 10,
            'fecha_recaudacion' => '2026-09-20',
            'activo' => true,
        ]);
        DB::table('factura')->where('id_factura', $id)->update([
            'monto_pendiente' => 90,
            'estado' => 'DIFERENCIA PENDIENTE',
        ]);

        $this->postJson("/facturas/{$id}/pago", [
            'monto_pagado' => 0,
            'tipo_recaudacion' => 'DETRACCION',
            'total_recaudacion' => 10,
            'porcentaje_recaudacion' => 10,
            'fecha_recaudacion' => '',
            'validar_detraccion' => false,
        ])->assertOk()
            ->assertJsonPath('monto_pendiente', 100)
            ->assertJsonPath('estado', 'PENDIENTE');

        $this->assertNull(DB::table('recaudacion')->where('id_factura', $id)->value('fecha_recaudacion'));
    }

    public function test_pago_masivo_no_suma_monedas_distintas_como_una_transferencia(): void
    {
        $pen = $this->factura(1, 100);
        $usd = $this->factura(1, 80, 'USD');

        $this->pagoMasivo(1, [[$pen, 20], [$usd, 30]], 50)->assertUnprocessable();
        $this->assertSame(0, DB::table('pago_factura')->count());
    }

    public function test_abono_individual_no_admite_monto_superior_al_pendiente(): void
    {
        $id = $this->factura(1, 100);

        $this->postJson("/facturas/{$id}/pago", ['monto_pagado' => 101])->assertUnprocessable();
        $this->assertSame(0, DB::table('pago_factura')->count());
        $this->assertSaldo($id, 0, 100, 'PENDIENTE');
    }

    public function test_editar_abono_no_puede_sobrepasar_el_saldo_disponible(): void
    {
        $id = $this->factura(1, 100);
        $this->postJson("/facturas/{$id}/pago", ['monto_pagado' => 40])->assertOk();
        $pagoId = DB::table('pago_factura')->value('id_pago');

        $this->putJson("/facturas/{$id}/pagos/{$pagoId}", [
            'monto_pagado' => 101, 'fecha_pago' => '2026-09-20',
        ])->assertUnprocessable();
        $this->assertSaldo($id, 40, 60, 'DIFERENCIA PENDIENTE');
        $this->assertEqualsWithDelta(40, (float) DB::table('pago_factura')->where('id_pago', $pagoId)->value('monto_pagado'), 0.001);
    }

    public function test_abono_en_soles_se_convierte_a_usd_con_el_tipo_de_cambio(): void
    {
        $id = $this->factura(1, 100, 'USD', null, 3.75);

        $this->postJson("/facturas/{$id}/pago", [
            'monto_pagado'   => 375,
            'monto_original' => 375,
            'moneda_pago'    => 'PEN',
            'monto_cambio'   => 3.75,
            'fecha_pago'     => '2026-09-20',
        ])->assertOk()
            ->assertJsonPath('monto_pendiente', 0)
            ->assertJsonPath('estado', 'PAGADA');

        $pago = DB::table('pago_factura')->where('id_factura', $id)->first();
        $this->assertEqualsWithDelta(100, (float) $pago->monto_pagado, 0.001);
        $this->assertSame('PEN', $pago->moneda_pago);
        $this->assertEqualsWithDelta(375, (float) $pago->monto_original, 0.001);
        $this->assertEqualsWithDelta(3.75, (float) $pago->monto_cambio_pago, 0.0001);
        $this->assertSaldo($id, 100, 0, 'PAGADA');
    }

    public function test_abono_en_soles_sin_tipo_de_cambio_es_rechazado(): void
    {
        $id = $this->factura(1, 100, 'USD');

        $this->postJson("/facturas/{$id}/pago", [
            'monto_pagado'   => 375,
            'monto_original' => 375,
            'moneda_pago'    => 'PEN',
            'fecha_pago'     => '2026-09-20',
        ])->assertUnprocessable();

        $this->assertSame(0, DB::table('pago_factura')->count());
        $this->assertSaldo($id, 0, 100, 'PENDIENTE');
    }

    public function test_editar_abono_convierte_soles_a_usd(): void
    {
        $id = $this->factura(1, 100, 'USD', null, 3.75);
        $this->postJson("/facturas/{$id}/pago", [
            'monto_pagado' => 100, 'fecha_pago' => '2026-09-20',
        ])->assertOk()->assertJsonPath('estado', 'PAGADA');
        $pagoId = DB::table('pago_factura')->value('id_pago');

        $this->putJson("/facturas/{$id}/pagos/{$pagoId}", [
            'monto_pagado'   => 375,
            'monto_original' => 375,
            'moneda_pago'    => 'PEN',
            'monto_cambio'   => 3.75,
            'fecha_pago'     => '2026-09-20',
        ])->assertOk()->assertJsonPath('monto_pendiente', 0);

        $pago = DB::table('pago_factura')->where('id_pago', $pagoId)->first();
        $this->assertEqualsWithDelta(100, (float) $pago->monto_pagado, 0.001);
        $this->assertSame('PEN', $pago->moneda_pago);
        $this->assertEqualsWithDelta(375, (float) $pago->monto_original, 0.001);
    }

    public function test_pago_masivo_convierte_soles_a_usd(): void
    {
        $id = $this->factura(1, 100, 'USD', null, 3.75);

        $this->postJson('/facturas/pago-masivo/procesar', [
            'id_cliente'   => 1,
            'monto_total'  => 375,
            'fecha_abono'  => '2026-09-20',
            'moneda_pago'  => 'PEN',
            'monto_cambio' => 3.75,
            'detalles'     => [['id_factura' => $id, 'monto' => 375]],
        ])->assertOk()->assertJsonPath('facturas_actualizadas', 1);

        $pago = DB::table('pago_factura')->where('id_factura', $id)->first();
        $this->assertEqualsWithDelta(100, (float) $pago->monto_pagado, 0.001);
        $this->assertSame('PEN', $pago->moneda_pago);
        $this->assertEqualsWithDelta(375, (float) $pago->monto_original, 0.001);
        $this->assertSaldo($id, 100, 0, 'PAGADA');
    }

    public function test_abono_en_dolares_para_factura_en_soles_se_convierte(): void
    {
        $id = $this->factura(1, 1000, 'PEN', null, 3.75);

        $this->postJson("/facturas/{$id}/pago", [
            'monto_pagado'   => 100,
            'monto_original' => 100,
            'moneda_pago'    => 'USD',
            'monto_cambio'   => 3.75,
            'fecha_pago'     => '2026-09-20',
        ])->assertOk()->assertJsonPath('monto_pendiente', 625);

        $this->assertSaldo($id, 375, 625, 'DIFERENCIA PENDIENTE');
    }

    private function factura(int $cliente, float $importe, string $moneda = 'PEN', ?string $tipoRecaudacion = null, ?float $cambio = null): int
    {
        return DB::table('factura')->insertGetId([
            'id_cliente' => $cliente,
            'numero' => DB::table('factura')->count() + 1,
            'moneda' => $moneda,
            'tipo_recaudacion' => $tipoRecaudacion,
            'monto_cambio' => $cambio,
            'importe_total' => $importe,
            'monto_pendiente' => $importe,
            'fecha_emision' => '2026-09-20',
            'fecha_vencimiento' => '2026-10-20',
            'activo' => true,
        ]);
    }

    private function pagoMasivo(int $cliente, array $filas, float $total): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/facturas/pago-masivo/procesar', [
            'id_cliente' => $cliente, 'monto_total' => $total,
            'fecha_abono' => '2026-09-20',
            'detalles' => array_map(fn ($fila) => ['id_factura' => $fila[0], 'monto' => $fila[1]], $filas),
        ]);
    }

    private function assertSaldo(int $id, float $abonado, float $pendiente, string $estado): void
    {
        $factura = DB::table('factura')->where('id_factura', $id)->first();
        $this->assertEqualsWithDelta($abonado, (float) $factura->monto_abonado, 0.001);
        $this->assertEqualsWithDelta($pendiente, (float) $factura->monto_pendiente, 0.001);
        $this->assertSame($estado, $factura->estado);
    }
}
