<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacturaEdicionFechasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible en este entorno.');
        }

        Schema::create('factura', function (Blueprint $table) {
            $table->increments('id_factura');
            $table->string('serie')->default('FF01');
            $table->unsignedInteger('numero')->default(1);
            $table->string('estado')->default('PENDIENTE');
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('glosa')->nullable();
            $table->string('forma_pago')->nullable();
            $table->decimal('importe_total', 12, 2)->default(0);
            $table->decimal('monto_igv', 12, 2)->default(0);
            $table->decimal('subtotal_gravado', 12, 2)->default(0);
            $table->dateTime('fecha_actualizacion')->nullable();
        });
        Schema::create('auditoria_accion', function (Blueprint $table) {
            $table->increments('id_auditoria');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->string('entidad');
            $table->unsignedInteger('id_entidad')->nullable();
            $table->string('accion');
            $table->json('detalle')->nullable();
            $table->string('ip_origen')->nullable();
            $table->timestamp('fecha_creacion');
        });

        foreach (['ANULADO', 'POR VALIDAR DETRACCION', 'PAGO PARCIAL'] as $index => $estado) {
            DB::table('factura')->insert([
                'id_factura' => $index + 1,
                'serie' => 'FF01',
                'numero' => $index + 1,
                'estado' => $estado,
                'fecha_emision' => '2026-09-01',
                'fecha_vencimiento' => '2026-09-15',
            ]);
        }
    }

    public function test_edita_fechas_en_estados_que_antes_eran_rechazados(): void
    {
        foreach ([1 => 'ANULADO', 2 => 'POR VALIDAR DETRACCION', 3 => 'PAGO PARCIAL'] as $id => $estado) {
            $this->withoutMiddleware()->putJson("/facturas/{$id}", [
                'fecha_emision' => '2026-09-02',
                'fecha_vencimiento' => '2026-09-30',
                'estado' => $estado,
            ])->assertOk()->assertJsonPath('success', true);

            $this->assertDatabaseHas('factura', [
                'id_factura' => $id,
                'estado' => $estado,
                'fecha_emision' => '2026-09-02',
                'fecha_vencimiento' => '2026-09-30',
            ]);
        }
    }

    public function test_normaliza_el_alias_anulada_y_rechaza_un_rango_invalido(): void
    {
        $this->withoutMiddleware()->putJson('/facturas/1', [
            'fecha_emision' => '2026-09-02',
            'fecha_vencimiento' => '2026-09-30',
            'estado' => 'ANULADA',
        ])->assertOk();
        $this->assertDatabaseHas('factura', ['id_factura' => 1, 'estado' => 'ANULADO']);

        $this->withoutMiddleware()->putJson('/facturas/2', [
            'fecha_emision' => '2026-09-20',
            'fecha_vencimiento' => '2026-09-10',
            'estado' => 'POR VALIDAR DETRACCION',
        ])->assertStatus(422)->assertJsonValidationErrors('fecha_vencimiento');
    }
}
