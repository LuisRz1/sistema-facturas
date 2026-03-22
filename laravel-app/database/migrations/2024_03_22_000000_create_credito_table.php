<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credito', function (Blueprint $table) {
            $table->unsignedBigInteger('id_factura')->primary();
            $table->string('serie_doc_modificado', 10);
            $table->integer('numero_doc_modificado');
            $table->dateTime('fecha_creacion');

            $table->foreign('id_factura')
                ->references('id_factura')
                ->on('factura')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credito');
    }
};
