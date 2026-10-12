<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado manual de una valorización: COMPLETO o PENDIENTE.
 * Es aditivo y con valor por defecto, así que las valorizaciones existentes
 * quedan como PENDIENTE sin backfill. El usuario puede cambiarlo en cualquier
 * momento y volver a PENDIENTE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cotizacion') && !Schema::hasColumn('cotizacion', 'estado_valorizacion')) {
            Schema::table('cotizacion', function (Blueprint $table) {
                $table->string('estado_valorizacion', 20)->default('PENDIENTE')->after('usa_hes');
            });

            Schema::table('cotizacion', function (Blueprint $table) {
                $table->index('estado_valorizacion', 'cotizacion_estado_valorizacion_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cotizacion') && Schema::hasColumn('cotizacion', 'estado_valorizacion')) {
            Schema::table('cotizacion', function (Blueprint $table) {
                $table->dropIndex('cotizacion_estado_valorizacion_index');
                $table->dropColumn('estado_valorizacion');
            });
        }
    }
};
