<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizarPendientesFacturasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible en este entorno.');
        }

        Schema::create('factura', function (Blueprint $table) {
            $table->increments('id_factura');
            $table->string('serie');
            $table->integer('numero');
            $table->string('moneda')->default('PEN');
            $table->string('estado')->default('PENDIENTE');
            $table->decimal('importe_total', 12, 2);
            $table->decimal('monto_abonado', 12, 2)->default(0);
            $table->decimal('monto_pendiente', 12, 2)->default(0);
            $table->string('tipo_recaudacion')->nullable();
            $table->decimal('monto_cambio', 10, 4)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTime('fecha_actualizacion')->nullable();
        });

        Schema::create('recaudacion', function (Blueprint $table) {
            $table->unsignedInteger('id_factura')->primary();
            $table->decimal('total_recaudacion', 12, 2);
            $table->date('fecha_recaudacion')->nullable();
            $table->boolean('activo')->default(true);
        });
    }

    public function test_dry_run_reporta_el_cambio_sin_modificar_la_factura(): void
    {
        $this->insertarFacturaConRecaudacion();

        $this->artisan('facturas:normalizar-pendientes')
            ->expectsOutputToContain('Facturas a actualizar: 1')
            ->expectsOutputToContain('Reducción: 10.00')
            ->expectsOutputToContain('Vista previa.')
            ->assertSuccessful();

        $this->assertSame('110.00', DB::table('factura')->value('monto_pendiente'));
    }

    public function test_apply_actualiza_solo_el_pendiente_cuando_recaudacion_no_esta_confirmada(): void
    {
        $this->insertarFacturaConRecaudacion();

        $this->artisan('facturas:normalizar-pendientes', ['--apply' => true])
            ->expectsOutputToContain('Normalización aplicada a 1 facturas.')
            ->assertSuccessful();

        $factura = DB::table('factura')->first();
        $this->assertSame('100.00', $factura->monto_pendiente);
        $this->assertSame('100.00', $factura->importe_total);
        $this->assertSame('0.00', $factura->monto_abonado);
        $this->assertSame('PENDIENTE', $factura->estado);
        $this->assertSame('10.00', DB::table('recaudacion')->value('total_recaudacion'));
    }

    private function insertarFacturaConRecaudacion(): void
    {
        DB::table('factura')->insert([
            'serie' => 'FF01',
            'numero' => 1,
            'moneda' => 'PEN',
            'estado' => 'PENDIENTE',
            'importe_total' => 100.00,
            'monto_abonado' => 0,
            'monto_pendiente' => 110.00,
            'tipo_recaudacion' => 'DETRACCION',
            'activo' => true,
        ]);

        DB::table('recaudacion')->insert([
            'id_factura' => 1,
            'total_recaudacion' => 10.00,
            'fecha_recaudacion' => null,
            'activo' => true,
        ]);
    }
}
