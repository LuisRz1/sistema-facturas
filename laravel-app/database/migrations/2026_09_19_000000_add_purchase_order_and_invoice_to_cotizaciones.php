<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->string('orden_compra', 100)->nullable()->after('numero_valorizacion');
        });

        Schema::table('maquinaria_cotizacion', function (Blueprint $table) {
            $table->string('numero_factura', 50)->nullable()->after('n_parte_diario');
        });

        Schema::table('agregado_cotizacion', function (Blueprint $table) {
            $table->string('numero_factura', 50)->nullable()->after('n_parte_diario');
        });
    }

    public function down(): void
    {
        Schema::table('agregado_cotizacion', function (Blueprint $table) {
            $table->dropColumn('numero_factura');
        });

        Schema::table('maquinaria_cotizacion', function (Blueprint $table) {
            $table->dropColumn('numero_factura');
        });

        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropColumn('orden_compra');
        });
    }
};
