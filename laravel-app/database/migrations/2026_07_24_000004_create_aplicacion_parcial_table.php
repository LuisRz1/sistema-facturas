<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('aplicacion_parcial', function (Blueprint $table) {
            $table->id('id_aplicacion');
            $table->unsignedInteger('id_factura');
            $table->unsignedBigInteger('id_movimiento');
            $table->decimal('monto_aplicado', 18, 2);
            $table->decimal('saldo_remanente', 18, 2)->default(0);
            $table->timestamp('fecha_aplicacion');
        });
    }
    public function down(): void { Schema::dropIfExists('aplicacion_parcial'); }
};
