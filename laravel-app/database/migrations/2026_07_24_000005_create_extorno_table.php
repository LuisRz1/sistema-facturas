<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('extorno', function (Blueprint $table) {
            $table->id('id_extorno');
            $table->unsignedBigInteger('id_movimiento');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('aprobado_por_id')->nullable();
            $table->string('motivo', 500);
            $table->decimal('monto', 18, 2);
            $table->string('estado', 30)->default('PENDIENTE_APROBACION');
            $table->timestamp('fecha_extorno');
        });
    }
    public function down(): void { Schema::dropIfExists('extorno'); }
};
