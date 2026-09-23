<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonedaPagoMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible en este entorno.');
        }

        Schema::create('pago_factura', function (Blueprint $table) {
            $table->increments('id_pago');
            $table->unsignedInteger('id_factura');
            $table->decimal('monto_pagado', 12, 2);
            $table->date('fecha_pago')->nullable();
        });
    }

    public function test_agrega_y_quita_las_columnas_de_moneda_del_pago(): void
    {
        $migration = require database_path('migrations/2026_09_23_020000_add_moneda_pago_to_pago_factura.php');

        $migration->up();

        $this->assertTrue(Schema::hasColumn('pago_factura', 'moneda_pago'));
        $this->assertTrue(Schema::hasColumn('pago_factura', 'monto_original'));
        $this->assertTrue(Schema::hasColumn('pago_factura', 'monto_cambio_pago'));

        // Idempotente: ejecutar de nuevo no debe fallar.
        $migration->up();
        $this->assertTrue(Schema::hasColumn('pago_factura', 'moneda_pago'));

        $migration->down();

        $this->assertFalse(Schema::hasColumn('pago_factura', 'moneda_pago'));
        $this->assertFalse(Schema::hasColumn('pago_factura', 'monto_original'));
        $this->assertFalse(Schema::hasColumn('pago_factura', 'monto_cambio_pago'));
    }
}
