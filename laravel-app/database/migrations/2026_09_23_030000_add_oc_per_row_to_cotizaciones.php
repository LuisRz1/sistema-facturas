<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una OC por fila de maquinaria: se agrega `maquinaria_cotizacion.id_orden_compra`
 * (simétrico a `id_hes`), se migran las asignaciones automáticas existentes
 * (eligiendo la OC dominante por fila) y se elimina la tabla puente antigua.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('maquinaria_cotizacion') && !Schema::hasColumn('maquinaria_cotizacion', 'id_orden_compra')) {
            Schema::table('maquinaria_cotizacion', function (Blueprint $table) {
                $table->unsignedBigInteger('id_orden_compra')->nullable()->after('id_hes')->index();
            });
        }

        // Backfill: de la tabla puente, la OC con más horas asignadas por fila.
        if (Schema::hasTable('maquinaria_cotizacion_oc') && Schema::hasColumn('maquinaria_cotizacion', 'id_orden_compra')) {
            $dominantes = [];
            $asignaciones = DB::table('maquinaria_cotizacion_oc')
                ->orderByDesc('horas_asignadas')
                ->get(['id_cotizacion_maqu', 'id_orden_compra']);

            foreach ($asignaciones as $asignacion) {
                if (!isset($dominantes[$asignacion->id_cotizacion_maqu])) {
                    $dominantes[$asignacion->id_cotizacion_maqu] = $asignacion->id_orden_compra;
                }
            }

            foreach ($dominantes as $idFila => $idOc) {
                DB::table('maquinaria_cotizacion')
                    ->where('id_cotizacion_maqu', $idFila)
                    ->update(['id_orden_compra' => $idOc]);
            }
        }

        Schema::dropIfExists('maquinaria_cotizacion_oc');
    }

    public function down(): void
    {
        if (!Schema::hasTable('maquinaria_cotizacion_oc')) {
            Schema::create('maquinaria_cotizacion_oc', function (Blueprint $table) {
                $table->bigIncrements('id_asignacion');
                $table->unsignedBigInteger('id_cotizacion_maqu')->index();
                $table->unsignedBigInteger('id_orden_compra')->index();
                $table->decimal('horas_asignadas', 12, 2);
                $table->unique(['id_cotizacion_maqu', 'id_orden_compra'], 'mc_oc_unica');
            });
        }

        if (Schema::hasColumn('maquinaria_cotizacion', 'id_orden_compra')) {
            Schema::table('maquinaria_cotizacion', function (Blueprint $table) {
                $table->dropColumn('id_orden_compra');
            });
        }
    }
};
