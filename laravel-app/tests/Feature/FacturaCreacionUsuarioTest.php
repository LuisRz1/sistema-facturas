<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacturaCreacionUsuarioTest extends TestCase
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
            $table->string('razon_social')->default('CLIENTE DE PRUEBA');
            $table->string('ruc')->nullable();
        });

        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('nombre_usuario')->nullable();
            $table->string('clave_usuario')->nullable();
            $table->string('correo')->nullable();
            $table->string('celular')->nullable();
            $table->unsignedInteger('id_rol')->default(2);
        });

        Schema::create('factura', function (Blueprint $table) {
            $table->increments('id_factura');
            $table->unsignedInteger('id_cliente');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->unsignedInteger('usuario_creacion')->nullable();
            $table->string('serie');
            $table->unsignedInteger('numero');
            $table->string('moneda')->default('PEN');
            $table->string('tipo_operacion')->nullable();
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('subtotal_gravado', 12, 2)->default(0);
            $table->decimal('monto_igv', 12, 2)->default(0);
            $table->decimal('importe_total', 12, 2)->default(0);
            $table->decimal('monto_abonado', 12, 2)->default(0);
            $table->decimal('monto_pendiente', 12, 2)->default(0);
            $table->decimal('monto_cambio', 10, 4)->nullable();
            $table->string('glosa')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('tipo_recaudacion')->nullable();
            $table->string('estado')->default('PENDIENTE');
            $table->boolean('activo')->default(true);
            $table->dateTime('fecha_creacion')->nullable();
            $table->dateTime('fecha_actualizacion')->nullable();
        });

        DB::table('cliente')->insert(['id_cliente' => 1]);
        DB::table('usuario')->insert([
            'id_usuario' => 7,
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'nombre_usuario' => 'aperez',
        ]);
    }

    public function test_la_creacion_manual_registra_al_usuario_autenticado(): void
    {
        $this->actingAs(Usuario::find(7));

        $this->postJson('/facturas', [
            'id_cliente'       => 1,
            'serie'            => 'FF01',
            'numero'           => 100,
            'moneda'           => 'PEN',
            'fecha_emision'    => '2026-09-20',
            'subtotal_gravado' => 100,
            'monto_igv'        => 18,
            'importe_total'    => 118,
        ])->assertOk()->assertJsonPath('success', true);

        $factura = DB::table('factura')->first();
        $this->assertSame(7, (int) $factura->usuario_creacion);
        $this->assertSame(7, (int) $factura->id_usuario);
    }
}
