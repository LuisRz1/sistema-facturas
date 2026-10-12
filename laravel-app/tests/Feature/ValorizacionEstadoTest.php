<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Estado manual (COMPLETO / PENDIENTE) de una valorización:
 * valor por defecto, endpoint PATCH /cotizaciones/{id}/estado con y sin
 * datos incompletos, confirmación explícita y filtro del listado.
 */
class ValorizacionEstadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible en este entorno.');
        }
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('cliente', function (Blueprint $t) {
            $t->increments('id_cliente');
            $t->string('razon_social')->nullable();
            $t->string('ruc')->nullable();
            $t->boolean('activo')->default(true);
        });
        Schema::create('maquinaria', function (Blueprint $t) {
            $t->increments('id_maquinaria'); $t->string('nombre')->nullable(); $t->string('numero_maquina')->nullable();
        });
        Schema::create('agregado', function (Blueprint $t) {
            $t->increments('id_agregado'); $t->string('nombre')->nullable(); $t->string('numero_agregado')->nullable();
        });
        Schema::create('chofer', function (Blueprint $t) {
            $t->increments('id_chofer'); $t->string('nombres')->nullable();
            $t->string('apellido_paterno')->nullable(); $t->string('apellido_materno')->nullable();
        });
        Schema::create('cotizacion', function (Blueprint $t) {
            $t->increments('id_cotizacion');
            $t->unsignedInteger('id_cliente')->nullable();
            $t->unsignedInteger('id_maquinaria')->nullable();
            $t->unsignedInteger('id_agregado')->nullable();
            $t->string('tipo_cotizacion');
            $t->string('numero_valorizacion')->nullable();
            $t->string('orden_compra')->nullable();
            $t->string('obra')->nullable();
            $t->date('periodo_inicio')->nullable();
            $t->date('periodo_fin')->nullable();
            $t->decimal('base_sin_igv', 12, 2)->default(0);
            $t->decimal('total_igv', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->boolean('activo')->default(true);
            $t->dateTime('fecha_creacion')->nullable();
            $t->dateTime('fecha_actualizacion')->nullable();
        });
        Schema::create('maquinaria_cotizacion', function (Blueprint $t) {
            $t->increments('id_cotizacion_maqu');
            $t->unsignedInteger('id_cotizacion');
            $t->unsignedInteger('id_chofer')->nullable();
            $t->unsignedInteger('id_maquinaria')->nullable();
            $t->date('fecha');
            $t->string('placa')->nullable();
            $t->string('obra_maquina')->nullable();
            $t->decimal('hora_inicio', 12, 2);
            $t->decimal('hora_fin', 12, 2)->nullable();
            $t->decimal('horas_trabajadas', 12, 2);
            $t->decimal('hora_minima', 12, 2);
            $t->decimal('precio_hora', 12, 2)->nullable();
            $t->decimal('total_fila', 12, 2);
            $t->string('n_parte_diario')->nullable();
            $t->string('numero_factura')->nullable();
            $t->string('ruta_parte_diario')->nullable();
            $t->boolean('activo')->default(true);
            $t->dateTime('fecha_creacion')->nullable();
            $t->dateTime('fecha_actualizacion')->nullable();
        });
        Schema::create('agregado_cotizacion', function (Blueprint $t) {
            $t->increments('id_cotizacion_agr');
            $t->unsignedInteger('id_cotizacion');
            $t->unsignedInteger('id_chofer')->nullable();
            $t->unsignedInteger('id_agregado')->nullable();
            $t->date('fecha');
            $t->string('placa')->nullable();
            $t->string('obra_agregado')->nullable();
            $t->decimal('m3', 12, 2);
            $t->decimal('precio_m3', 12, 2)->nullable();
            $t->decimal('total_fila', 12, 2);
            $t->string('n_parte_diario')->nullable();
            $t->string('numero_factura')->nullable();
            $t->string('grr')->nullable();
            $t->string('ruta_parte_diario')->nullable();
            $t->string('ruta_grr')->nullable();
            $t->boolean('activo')->default(true);
            $t->dateTime('fecha_creacion')->nullable();
            $t->dateTime('fecha_actualizacion')->nullable();
        });
        Schema::create('usuario', function (Blueprint $t) {
            $t->increments('id_usuario');
            $t->string('nombre');
            $t->string('apellido')->nullable();
            $t->string('nombre_usuario')->nullable();
            $t->string('clave_usuario')->nullable();
            $t->unsignedInteger('id_rol')->default(2);
        });
        (require database_path('migrations/2026_09_19_010000_add_oc_hes_to_cotizaciones.php'))->up();
        (require database_path('migrations/2026_09_23_030000_add_oc_per_row_to_cotizaciones.php'))->up();
        (require database_path('migrations/2026_09_23_030100_make_oc_hes_unique_globally.php'))->up();
        (require database_path('migrations/2026_10_12_000000_add_estado_to_cotizacion.php'))->up();
    }

    public function test_valorizacion_nueva_queda_pendiente_por_defecto(): void
    {
        $this->withoutMiddleware();
        DB::table('cliente')->insert(['id_cliente' => 1]);
        DB::table('maquinaria')->insert(['id_maquinaria' => 1]);

        $this->post('/cotizaciones', [
            'tipo_cotizacion'     => 'MAQUINARIA',
            'id_cliente'          => 1,
            'id_maquinaria'       => 1,
            'numero_valorizacion' => '01',
            'orden_compra'        => 'OC-EST-01',
            'obra'                => 'Prueba',
            'periodo_inicio'      => '2026-09-01',
            'periodo_fin'         => '2026-09-30',
            'horas_oc'            => 120,
        ])->assertRedirect();

        $this->assertDatabaseHas('cotizacion', [
            'numero_valorizacion' => '01',
            'estado_valorizacion' => 'PENDIENTE',
        ]);
    }

    public function test_pendiente_se_aplica_aunque_haya_faltantes(): void
    {
        $this->withoutMiddleware();
        DB::table('cliente')->insert(['id_cliente' => 1]);
        $id = $this->crearCotizacion();

        // Sin filas ni OC: la valorización tiene faltantes.
        $this->patchJson("/cotizaciones/{$id}/estado", ['estado' => 'COMPLETO', 'confirmado' => 1])
            ->assertOk()->assertJsonPath('estado', 'COMPLETO');
        $this->assertDatabaseHas('cotizacion', ['id_cotizacion' => $id, 'estado_valorizacion' => 'COMPLETO']);

        $response = $this->patchJson("/cotizaciones/{$id}/estado", ['estado' => 'PENDIENTE']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('estado', 'PENDIENTE');
        $this->assertArrayNotHasKey('requiere_confirmacion', $response->json());
        $this->assertDatabaseHas('cotizacion', ['id_cotizacion' => $id, 'estado_valorizacion' => 'PENDIENTE']);
    }

    public function test_completo_con_faltantes_exige_confirmacion_y_no_cambia_estado(): void
    {
        $this->withoutMiddleware();
        DB::table('cliente')->insert(['id_cliente' => 1]);
        $id = $this->crearCotizacion();

        $response = $this->patchJson("/cotizaciones/{$id}/estado", ['estado' => 'COMPLETO']);

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('requiere_confirmacion', true)
            ->assertJsonPath('estado', 'PENDIENTE');
        $this->assertNotEmpty($response->json('faltantes'));
        $this->assertDatabaseHas('cotizacion', ['id_cotizacion' => $id, 'estado_valorizacion' => 'PENDIENTE']);
    }

    public function test_completo_con_confirmacion_aplica_el_estado(): void
    {
        $this->withoutMiddleware();
        DB::table('cliente')->insert(['id_cliente' => 1]);
        $id = $this->crearCotizacion();

        $response = $this->patchJson("/cotizaciones/{$id}/estado", ['estado' => 'COMPLETO', 'confirmado' => 1]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('estado', 'COMPLETO');
        $this->assertDatabaseHas('cotizacion', ['id_cotizacion' => $id, 'estado_valorizacion' => 'COMPLETO']);
    }

    public function test_valorizacion_completa_pasa_a_completo_sin_confirmacion(): void
    {
        $this->withoutMiddleware();
        DB::table('cliente')->insert(['id_cliente' => 1]);
        DB::table('maquinaria')->insert(['id_maquinaria' => 1]);
        DB::table('chofer')->insert(['id_chofer' => 1, 'nombres' => 'Juan']);
        $id = $this->crearCotizacion(['control_oc_activo' => true, 'usa_hes' => false]);

        $ocId = DB::table('cotizacion_orden_compra')->insertGetId([
            'id_cotizacion'      => $id,
            'numero'             => 'OC-COMPLETA-01',
            'horas_autorizadas'  => 120,
            'ruta_documento'     => 'cotizaciones/ordenes/oc.pdf',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('maquinaria_cotizacion')->insert([
            'id_cotizacion'      => $id,
            'id_chofer'          => 1,
            'id_maquinaria'      => 1,
            'fecha'              => '2026-09-01',
            'hora_inicio'        => 8,
            'hora_fin'           => 10,
            'horas_trabajadas'   => 2,
            'hora_minima'        => 2,
            'precio_hora'        => 50,
            'total_fila'         => 100,
            'ruta_parte_diario'  => 'partes/p1.pdf',
            'es_facturable'      => true,
            'id_orden_compra'    => $ocId,
            'activo'             => true,
        ]);

        $response = $this->patchJson("/cotizaciones/{$id}/estado", ['estado' => 'COMPLETO']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('estado', 'COMPLETO');
        $this->assertDatabaseHas('cotizacion', ['id_cotizacion' => $id, 'estado_valorizacion' => 'COMPLETO']);
    }

    public function test_listado_filtra_por_estado_completo(): void
    {
        $this->withoutMiddleware();
        DB::table('cliente')->insert([
            'id_cliente' => 1, 'razon_social' => 'Cliente Uno', 'ruc' => '20123456789', 'activo' => true,
        ]);
        DB::table('usuario')->insert(['id_usuario' => 1, 'nombre' => 'Admin', 'id_rol' => 2]);

        $this->crearCotizacion([
            'numero_valorizacion' => 'VAL-COMPLETA-01',
            'estado_valorizacion' => 'COMPLETO',
            'control_oc_activo'   => false,
        ]);
        $this->crearCotizacion([
            'numero_valorizacion' => 'VAL-PENDIENTE-02',
            'estado_valorizacion' => 'PENDIENTE',
            'control_oc_activo'   => false,
        ]);

        $this->actingAs(Usuario::find(1));
        $this->withViewErrors([]);

        $response = $this->get('/cotizaciones?estado=COMPLETO');

        $response->assertOk();
        $response->assertSee('VAL-COMPLETA-01');
        $response->assertDontSee('VAL-PENDIENTE-02');
    }

    private function crearCotizacion(array $overrides = []): int
    {
        return DB::table('cotizacion')->insertGetId(array_merge([
            'id_cliente'          => 1,
            'tipo_cotizacion'     => 'MAQUINARIA',
            'numero_valorizacion' => 'VAL-' . uniqid(),
            'obra'                => 'Obra de prueba',
            'periodo_inicio'      => '2026-09-01',
            'periodo_fin'         => '2026-09-30',
            'control_oc_activo'   => true,
            'usa_hes'             => false,
            'activo'              => true,
            'base_sin_igv'        => 0,
            'total_igv'           => 0,
            'total'               => 0,
        ], $overrides));
    }
}
