<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->decimal('monto_cambio', 10, 4)->nullable()->after('monto_pendiente')
                ->comment('Tipo de cambio S/ por 1 USD. Importado desde columna T/C del Excel de ventas.');
        });
    }

    public function down(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->dropColumn('monto_cambio');
        });
    }
};
