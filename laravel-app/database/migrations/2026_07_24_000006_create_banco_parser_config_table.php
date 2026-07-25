<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('banco_parser_config', function (Blueprint $table) {
            $table->id('id_config');
            $table->string('banco', 10);
            $table->string('version', 20);
            $table->json('mapeo_columnas');
            $table->json('tipos_ignorables');
            $table->decimal('tolerancia_monto', 10, 2)->default(1.00);
            $table->integer('tolerancia_dias')->default(3);
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('banco_parser_config'); }
};
