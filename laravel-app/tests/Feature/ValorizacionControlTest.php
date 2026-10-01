<?php

namespace Tests\Feature;

use App\Http\Controllers\CotizacionExportController;
use App\Services\ValorizacionOcService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ValorizacionControlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible en este entorno.');
        }
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('cliente', function (Blueprint $t) { $t->increments('id_cliente'); });
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
        (require database_path('migrations/2026_09_19_010000_add_oc_hes_to_cotizaciones.php'))->up();
        (require database_path('migrations/2026_09_23_030000_add_oc_per_row_to_cotizaciones.php'))->up();
        (require database_path('migrations/2026_09_23_030100_make_oc_hes_unique_globally.php'))->up();
    }

    public function test_maquinaria_nueva_exige_oc_con_horas_y_permite_adjuntar_archivo_luego(): void
    {
        $this->withoutMiddleware();
        DB::table('cliente')->insert(['id_cliente' => 1]);
        DB::table('maquinaria')->insert(['id_maquinaria' => 1]);
        $data = [
            'tipo_cotizacion' => 'MAQUINARIA', 'id_cliente' => 1, 'id_maquinaria' => 1,
            'numero_valorizacion' => '01', 'orden_compra' => 'OC-120',
            'obra' => 'Prueba', 'periodo_inicio' => '2026-09-01', 'periodo_fin' => '2026-09-30',
            'usa_hes' => 1,
        ];
        $this->postJson('/cotizaciones', $data)->assertUnprocessable();
        $this->assertSame(0, DB::table('cotizacion')->count());

        $this->post('/cotizaciones', $data + ['horas_oc' => 120])->assertRedirect();
        $cotizacion = DB::table('cotizacion')->first();
        $this->assertSame(1, (int)$cotizacion->control_oc_activo);
        $this->assertSame(1, (int)$cotizacion->usa_hes);
        $this->assertSame('OC-120', DB::table('cotizacion_orden_compra')->value('numero'));
        $this->assertNull(DB::table('cotizacion_orden_compra')->value('ruta_documento'));
    }

    public function test_asignacion_manual_de_oc_por_fila_y_horas_sin_oc(): void
    {
        $this->withoutMiddleware();
        $id = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'MAQUINARIA', 'control_oc_activo' => true]);
        $ocId = $this->oc($id, 'OC-120', 120);
        $fila1 = $this->filaMaq($id, 118, 3);
        $this->filaMaq($id, 5, 3);
        $service = app(ValorizacionOcService::class);

        $this->assertSame(123.0, (float) $service->resumen($id)['horas_sin_oc']);

        $this->postJson("/cotizaciones/{$id}/rows/{$fila1}/oc", ['id_orden_compra' => $ocId])->assertOk();
        $resumen = $service->resumen($id);
        $this->assertSame(118.0, (float) $resumen['ordenes']->first()->horas_consumidas);
        $this->assertSame(5.0, (float) $resumen['horas_sin_oc']);

        $this->deleteJson("/cotizaciones/{$id}/rows/{$fila1}/oc")->assertOk();
        $this->assertSame(123.0, (float) $service->resumen($id)['horas_sin_oc']);
    }

    public function test_al_guardar_salto_crea_ajuste_sin_cobro(): void
    {
        Storage::fake('s3');
        $this->withoutMiddleware();
        DB::table('chofer')->insert(['id_chofer' => 1, 'nombres' => 'Chofer']);
        DB::table('maquinaria')->insert(['id_maquinaria' => 1, 'nombre' => 'Volquete']);
        $id = DB::table('cotizacion')->insertGetId([
            'tipo_cotizacion' => 'MAQUINARIA', 'control_oc_activo' => true,
            'obra' => 'Obra', 'usa_hes' => true,
        ]);
        $this->oc($id, 'OC-3', 3);
        $base = [
            'id_chofer' => 1, 'id_maquinaria' => 1, 'fecha' => '2026-09-01',
            'hora_minima' => 3, 'precio_hora' => 10, 'cobrar_fila' => '1',
        ];
        $this->postJson("/cotizaciones/{$id}/rows", $base + ['hora_inicio' => 0, 'hora_fin' => 2])->assertOk();
        $this->postJson("/cotizaciones/{$id}/rows", $base + [
            'hora_inicio' => 4, 'hora_fin' => 6, 'completar_salto' => 1,
        ])->assertOk();

        $this->assertSame(3, DB::table('maquinaria_cotizacion')->where('id_cotizacion', $id)->count());
        $ajuste = DB::table('maquinaria_cotizacion')->where('es_ajuste_horometro', true)->first();
        $this->assertSame(0, (int)$ajuste->es_facturable);
        $this->assertSame(0.0, (float)$ajuste->total_fila);
        // Dos filas facturables de 3 h = 6 h sin OC (el ajuste no cuenta).
        $this->assertSame(6.0, (float) app(ValorizacionOcService::class)->resumen($id)['horas_sin_oc']);
    }

    public function test_hes_agrupa_filas_no_consecutivas_y_no_duplica_asignaciones(): void
    {
        Storage::fake('s3');
        $this->withoutMiddleware();
        $id = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'AGREGADO', 'usa_hes' => true]);
        foreach ([1, 2, 3] as $dia) {
            DB::table('agregado_cotizacion')->insert([
                'id_cotizacion' => $id, 'fecha' => "2026-09-0{$dia}", 'm3' => 10 * $dia,
                'total_fila' => 100, 'es_facturable' => true,
            ]);
        }

        $this->post("/cotizaciones/{$id}/hes", [
            'row_ids' => [1, 3], 'codigo' => 'HES-001',
            'archivo_hes' => UploadedFile::fake()->image('hes.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $hesId = DB::table('cotizacion_hes')->value('id_hes');
        $this->assertSame([1, 3], DB::table('agregado_cotizacion')->where('id_hes', $hesId)->orderBy('id_cotizacion_agr')->pluck('id_cotizacion_agr')->all());

        $this->postJson("/cotizaciones/{$id}/hes", ['row_ids' => [1], 'id_hes' => $hesId])
            ->assertUnprocessable();
        $this->deleteJson("/cotizaciones/{$id}/rows/1/hes")->assertOk();
        $this->assertNull(DB::table('agregado_cotizacion')->where('id_cotizacion_agr', 1)->value('id_hes'));
    }

    public function test_no_cobrar_no_puede_recibir_hes(): void
    {
        Storage::fake('s3');
        $this->withoutMiddleware();
        $id = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'AGREGADO', 'usa_hes' => true]);
        DB::table('agregado_cotizacion')->insert(['id_cotizacion' => $id, 'fecha' => '2026-09-01', 'm3' => 4, 'total_fila' => 0, 'es_facturable' => false]);
        $this->post("/cotizaciones/{$id}/hes", [
            'row_ids' => [1], 'codigo' => 'HES-X',
            'archivo_hes' => UploadedFile::fake()->image('hes.png'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame(0, DB::table('cotizacion_hes')->count());
    }

    public function test_documentos_no_se_pueden_leer_desde_otra_valorizacion(): void
    {
        Storage::fake('s3');
        $this->withoutMiddleware();
        $primera = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'MAQUINARIA']);
        $segunda = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'MAQUINARIA']);
        Storage::disk('s3')->put('cotizaciones/ordenes/prueba.pdf', 'orden');
        Storage::disk('s3')->put('cotizaciones/hes/prueba.pdf', 'hes');
        $oc = DB::table('cotizacion_orden_compra')->insertGetId([
            'id_cotizacion' => $primera, 'numero' => 'OC-1', 'horas_autorizadas' => 3,
            'ruta_documento' => 'cotizaciones/ordenes/prueba.pdf',
        ]);
        $hes = DB::table('cotizacion_hes')->insertGetId([
            'id_cotizacion' => $primera, 'codigo' => 'HES-1',
            'ruta_documento' => 'cotizaciones/hes/prueba.pdf',
        ]);

        $this->get("/cotizaciones/{$primera}/ordenes/{$oc}/documento")->assertOk();
        $this->get("/cotizaciones/{$primera}/hes/{$hes}/documento")->assertOk();
        $this->get("/cotizaciones/{$segunda}/ordenes/{$oc}/documento")->assertNotFound();
        $this->get("/cotizaciones/{$segunda}/hes/{$hes}/documento")->assertNotFound();
    }

    public function test_unicidad_global_de_oc_y_hes(): void
    {
        Storage::fake('s3');
        $this->withoutMiddleware();
        $a = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'MAQUINARIA', 'control_oc_activo' => true, 'usa_hes' => true]);
        $b = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'MAQUINARIA', 'control_oc_activo' => true, 'usa_hes' => true]);
        $this->oc($a, 'OC-1', 10);

        // OC repetida en otra valorización
        $this->postJson("/cotizaciones/{$b}/ordenes", ['numero' => 'OC-1', 'horas_autorizadas' => 5])
            ->assertUnprocessable();

        // HES repetido en otra valorización
        $rowA = $this->filaMaq($a, 2, 2);
        $rowB = $this->filaMaq($b, 2, 2);
        $this->post("/cotizaciones/{$a}/hes", [
            'row_ids' => [$rowA], 'codigo' => 'HES-1', 'archivo_hes' => UploadedFile::fake()->image('h.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->post("/cotizaciones/{$b}/hes", [
            'row_ids' => [$rowB], 'codigo' => 'HES-1', 'archivo_hes' => UploadedFile::fake()->image('h.png'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_no_se_puede_borrar_oc_o_hes_en_uso(): void
    {
        Storage::fake('s3');
        $this->withoutMiddleware();
        $id = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'MAQUINARIA', 'control_oc_activo' => true, 'usa_hes' => true]);
        $ocId = $this->oc($id, 'OC-X', 10);
        $row = $this->filaMaq($id, 2, 2);

        $this->postJson("/cotizaciones/{$id}/rows/{$row}/oc", ['id_orden_compra' => $ocId])->assertOk();
        $this->deleteJson("/cotizaciones/{$id}/ordenes/{$ocId}")->assertUnprocessable();
        $this->deleteJson("/cotizaciones/{$id}/rows/{$row}/oc")->assertOk();
        $this->deleteJson("/cotizaciones/{$id}/ordenes/{$ocId}")->assertOk();
        $this->assertSame(0, DB::table('cotizacion_orden_compra')->count());

        $row2 = $this->filaMaq($id, 2, 2);
        $this->post("/cotizaciones/{$id}/hes", [
            'row_ids' => [$row2], 'codigo' => 'HES-X', 'archivo_hes' => UploadedFile::fake()->image('h.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $hesId = DB::table('cotizacion_hes')->value('id_hes');
        $this->deleteJson("/cotizaciones/{$id}/hes/{$hesId}")->assertUnprocessable();
        $this->deleteJson("/cotizaciones/{$id}/rows/{$row2}/hes")->assertOk();
        $this->deleteJson("/cotizaciones/{$id}/hes/{$hesId}")->assertOk();
        $this->assertSame(0, DB::table('cotizacion_hes')->count());
    }

    public function test_excel_y_pdf_incluyen_oc_y_hes(): void
    {
        $id = DB::table('cotizacion')->insertGetId(['tipo_cotizacion' => 'MAQUINARIA', 'control_oc_activo' => true, 'usa_hes' => true]);
        $ocId = $this->oc($id, 'OC-120', 120);
        $filaId = $this->filaMaq($id, 4, 3);
        DB::table('maquinaria_cotizacion')->where('id_cotizacion_maqu', $filaId)->update([
            'id_orden_compra' => $ocId, 'hora_fin' => 4, 'precio_hora' => 10,
        ]);
        $service = app(ValorizacionOcService::class);
        $fila = DB::table('maquinaria_cotizacion')->where('id_cotizacion_maqu', $filaId)->first();
        $fila->oc_numero = 'OC-120';
        $fila->chofer_nombre = 'Chofer';
        $fila->maquinaria_nombre = 'Maquinaria';
        $fila->codigo_hes = 'HES-001';
        $filas = $service->detallarFilas(collect([$fila]));
        $cotizacion = (object) [
            'id_cotizacion' => $id, 'tipo_cotizacion' => 'MAQUINARIA',
            'control_oc_activo' => true, 'usa_hes' => true,
            'numero_valorizacion' => '01', 'obra' => 'Obra',
            'periodo_inicio' => '2026-09-01', 'periodo_fin' => '2026-09-30',
            'razon_social' => 'Cliente', 'ruc' => '12345678901',
            'maquinaria_nombre' => 'Maquinaria', 'orden_compra' => 'OC-120',
            'total' => 100, 'base_sin_igv' => 84.75, 'total_igv' => 15.25,
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $method = new \ReflectionMethod(CotizacionExportController::class, 'buildSheet');
        $method->invoke(app(CotizacionExportController::class), $sheet, $cotizacion, $filas);
        $this->assertSame('OC / HORAS', $sheet->getCell('O12')->getValue());
        $this->assertSame('HES', $sheet->getCell('P12')->getValue());
        $this->assertStringContainsString('OC-120', $sheet->getCell('O13')->getValue());
        $this->assertSame('HES-001', $sheet->getCell('P13')->getValue());

        $ocResumen = $service->resumen($id);
        $html = view('cotizaciones.print', compact('cotizacion', 'filas', 'ocResumen'))->render();
        $this->assertStringContainsString('OC / HORAS', $html);
        $this->assertStringContainsString('HES-001', $html);
    }

    private function oc(int $id, string $numero, float $horas): int
    {
        return DB::table('cotizacion_orden_compra')->insertGetId([
            'id_cotizacion' => $id, 'numero' => $numero,
            'horas_autorizadas' => $horas, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function filaMaq(int $id, float $trabajadas, float $minimas, bool $facturable = true): int
    {
        return DB::table('maquinaria_cotizacion')->insertGetId([
            'id_cotizacion' => $id, 'fecha' => '2026-09-01',
            'hora_inicio' => DB::table('maquinaria_cotizacion')->where('id_cotizacion', $id)->count(),
            'horas_trabajadas' => $trabajadas, 'hora_minima' => $minimas,
            'total_fila' => $facturable ? 100 : 0, 'es_facturable' => $facturable,
        ]);
    }
}
