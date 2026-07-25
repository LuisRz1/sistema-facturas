<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('movimiento_historial_estado', function (Blueprint $table) {
            $table->id('id_historial');
            $table->unsignedBigInteger('id_movimiento');
            $table->string('estado_anterior', 30);
            $table->string('estado_nuevo', 30);
            $table->unsignedInteger('usuario_id')->nullable();
            $table->string('motivo', 500)->nullable();
            $table->timestamp('fecha_transicion');
        });
    }
    public function down(): void { Schema::dropIfExists('movimiento_historial_estado'); }
};
