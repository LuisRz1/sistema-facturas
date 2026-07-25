<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('auditoria_importacion', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->unsignedInteger('usuario_id');
            $table->timestamp('fecha');
            $table->string('ip_origen', 45)->nullable();
            $table->unsignedBigInteger('id_archivo')->nullable();
            $table->string('accion', 50);
            $table->integer('cantidad_registros')->default(0);
            $table->integer('cantidad_conciliada')->default(0);
            $table->integer('cantidad_manual')->default(0);
            $table->integer('cantidad_rechazada')->default(0);
            $table->integer('tiempo_procesamiento_ms')->nullable();
            $table->json('errores_encontrados')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('auditoria_importacion'); }
};
