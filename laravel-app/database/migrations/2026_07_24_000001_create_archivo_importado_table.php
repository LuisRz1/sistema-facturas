<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('archivo_importado', function (Blueprint $table) {
            $table->id('id_archivo');
            $table->string('banco', 10);
            $table->string('moneda', 5);
            $table->string('nombre_archivo', 255);
            $table->string('hash_archivo', 64)->unique();
            $table->unsignedInteger('usuario_id');
            $table->timestamp('fecha_importacion');
            $table->string('estado', 30)->default('EN_PROCESO');
            $table->integer('total_registros')->default(0);
            $table->integer('total_conciliados')->default(0);
            $table->integer('total_pendientes')->default(0);
            $table->integer('total_errores')->default(0);
            $table->integer('tiempo_procesamiento_ms')->nullable();
            $table->boolean('activo')->default(true);
        });
    }
    public function down(): void { Schema::dropIfExists('archivo_importado'); }
};
