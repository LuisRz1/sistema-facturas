<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificacionYAuditoriaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible en este entorno.');
        }
        foreach (['auditoria_accion', 'notificacion_factura', 'factura', 'cliente', 'usuario'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('cliente', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('razon_social');
            $table->string('correo')->nullable();
            $table->string('celular')->nullable();
        });
        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre');
            $table->string('apellido')->nullable();
        });
        Schema::create('factura', function (Blueprint $table) {
            $table->increments('id_factura');
            $table->unsignedInteger('id_cliente');
            $table->string('serie');
            $table->integer('numero');
            $table->string('estado');
            $table->string('moneda')->default('PEN');
            $table->decimal('importe_total', 12, 2);
            $table->decimal('monto_pendiente', 12, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->date('fecha_abono')->nullable();
            $table->string('ruta_comprobante_pago')->nullable();
        });
        Schema::create('notificacion_factura', function (Blueprint $table) {
            $table->increments('id_notificacion');
            $table->unsignedInteger('id_factura');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->unsignedInteger('id_regla')->nullable();
            $table->string('canal');
            $table->string('categoria');
            $table->string('tipo_notificacion');
            $table->integer('numero_intento_dia');
            $table->string('destinatario');
            $table->string('asunto')->nullable();
            $table->text('mensaje');
            $table->string('estado_envio');
            $table->timestamp('fecha_programada')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->text('respuesta_proveedor')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_actualizacion')->nullable();
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

        DB::table('cliente')->insert(['id_cliente' => 1, 'razon_social' => 'Cliente de prueba', 'correo' => 'cliente@example.test', 'celular' => '51999999999']);
        DB::table('factura')->insert([
            'id_factura' => 1, 'id_cliente' => 1, 'serie' => 'FF01', 'numero' => 99,
            'estado' => 'PENDIENTE', 'moneda' => 'PEN', 'importe_total' => 150, 'monto_pendiente' => 150,
            'fecha_vencimiento' => '2026-10-01',
        ]);
    }

    public function test_preview_shows_recipient_and_message_before_any_send(): void
    {
        $response = $this->withoutMiddleware()->getJson('/facturas/1/notificaciones/vista-previa?canal=CORREO&tipo=COBRANZA');

        $response->assertOk()
            ->assertJsonPath('destinatario', 'cliente@example.test')
            ->assertJsonPath('asunto', 'Confirmación de Pago - Factura FF01-99')
            ->assertJsonPath('canal', 'CORREO');
    }

    public function test_notification_endpoint_rejects_unconfirmed_submission(): void
    {
        $this->withoutMiddleware()
            ->post('/facturas/1/enviar-whatsapp-manual')
            ->assertRedirect();

        $this->assertDatabaseCount('notificacion_factura', 0);
    }

    public function test_invoice_history_includes_actor_action_and_timestamp(): void
    {
        DB::table('usuario')->insert(['id_usuario' => 7, 'nombre' => 'Ana', 'apellido' => 'Pérez']);
        DB::table('auditoria_accion')->insert([
            'id_usuario' => 7, 'entidad' => 'FACTURA', 'id_entidad' => 1,
            'accion' => 'PAGO_REGISTRADO', 'detalle' => json_encode(['monto' => 150]),
            'fecha_creacion' => now(),
        ]);

        $this->withoutMiddleware()->getJson('/facturas/1/historial-acciones')
            ->assertOk()
            ->assertJsonPath('acciones.0.usuario', 'Ana Pérez')
            ->assertJsonPath('acciones.0.accion', 'PAGO_REGISTRADO')
            ->assertJsonPath('acciones.0.detalle.monto', 150);
    }
}
