<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('movimiento_bancario', function (Blueprint $table) {
            $table->id('id_movimiento');
            $table->unsignedBigInteger('id_archivo');
            $table->string('banco', 10);
            $table->string('moneda', 5);
            $table->string('cuenta_bancaria', 30);
            $table->date('fecha_operacion');
            $table->date('fecha_proceso')->nullable();
            $table->time('hora')->nullable();
            $table->string('numero_operacion', 50);
            $table->text('descripcion');
            $table->string('referencia', 500)->nullable();
            $table->decimal('importe', 18, 2);
            $table->string('tipo_movimiento', 10);
            $table->decimal('abono', 18, 2)->nullable();
            $table->decimal('cargo', 18, 2)->nullable();
            $table->decimal('saldo', 18, 2)->nullable();
            $table->string('codigo_interno_banco', 20)->nullable();
            $table->string('hash_movimiento', 64)->unique();
            $table->string('estado_conciliacion', 30)->default('IMPORTADO');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_factura')->nullable();
            $table->decimal('score_match', 5, 2)->nullable();
            $table->unsignedInteger('usuario_conciliador_id')->nullable();
            $table->timestamp('fecha_conciliacion')->nullable();
            $table->string('version_config_parser', 20)->nullable();
            $table->boolean('activo')->default(true);
        });
    }
    public function down(): void { Schema::dropIfExists('movimiento_bancario'); }
};
