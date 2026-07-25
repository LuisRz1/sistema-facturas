<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('factura', function (Blueprint $table) {
            $table->unsignedBigInteger('id_movimiento')->nullable()->after('activo');
            $table->string('estado_conciliacion', 30)->nullable()->after('id_movimiento');
        });
    }
    public function down(): void {
        Schema::table('factura', function (Blueprint $table) {
            $table->dropColumn(['id_movimiento', 'estado_conciliacion']);
        });
    }
};
