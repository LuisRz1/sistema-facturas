<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->boolean('control_oc_activo')->default(false);
            $table->boolean('usa_hes')->default(false);
        });

        Schema::create('cotizacion_orden_compra', function (Blueprint $table) {
            $table->bigIncrements('id_orden_compra');
            $table->unsignedBigInteger('id_cotizacion')->index();
            $table->string('numero', 100);
            $table->decimal('horas_autorizadas', 12, 2);
            $table->string('ruta_documento', 500)->nullable();
            $table->timestamps();
            $table->unique(['id_cotizacion', 'numero']);
        });

        Schema::create('cotizacion_hes', function (Blueprint $table) {
            $table->bigIncrements('id_hes');
            $table->unsignedBigInteger('id_cotizacion')->index();
            $table->string('codigo', 100);
            $table->string('ruta_documento', 500);
            $table->timestamps();
            $table->unique(['id_cotizacion', 'codigo']);
        });

        Schema::table('maquinaria_cotizacion', function (Blueprint $table) {
            $table->boolean('es_facturable')->nullable();
            $table->boolean('es_ajuste_horometro')->default(false);
            $table->unsignedBigInteger('id_hes')->nullable()->index();
        });

        Schema::table('agregado_cotizacion', function (Blueprint $table) {
            $table->boolean('es_facturable')->nullable();
            $table->unsignedBigInteger('id_hes')->nullable()->index();
        });

        Schema::create('maquinaria_cotizacion_oc', function (Blueprint $table) {
            $table->bigIncrements('id_asignacion');
            $table->unsignedBigInteger('id_cotizacion_maqu')->index();
            $table->unsignedBigInteger('id_orden_compra')->index();
            $table->decimal('horas_asignadas', 12, 2);
            $table->unique(['id_cotizacion_maqu', 'id_orden_compra'], 'mc_oc_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquinaria_cotizacion_oc');
        Schema::table('agregado_cotizacion', function (Blueprint $table) {
            $table->dropColumn(['es_facturable', 'id_hes']);
        });
        Schema::table('maquinaria_cotizacion', function (Blueprint $table) {
            $table->dropColumn(['es_facturable', 'es_ajuste_horometro', 'id_hes']);
        });
        Schema::dropIfExists('cotizacion_hes');
        Schema::dropIfExists('cotizacion_orden_compra');
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropColumn(['control_oc_activo', 'usa_hes']);
        });
    }
};
