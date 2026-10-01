<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una OC y un HES no pueden pertenecer a dos valorizaciones distintas:
 * la unicidad pasa de (id_cotizacion, numero|codigo) a global (numero|codigo).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cotizacion_orden_compra')) {
            $this->quitarIndice('cotizacion_orden_compra', 'cotizacion_orden_compra_id_cotizacion_numero_unique');
            $this->agregarUnico('cotizacion_orden_compra', 'numero', 'cotizacion_orden_compra_numero_unique');
        }

        if (Schema::hasTable('cotizacion_hes')) {
            $this->quitarIndice('cotizacion_hes', 'cotizacion_hes_id_cotizacion_codigo_unique');
            $this->agregarUnico('cotizacion_hes', 'codigo', 'cotizacion_hes_codigo_unique');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cotizacion_orden_compra')) {
            $this->quitarIndice('cotizacion_orden_compra', 'cotizacion_orden_compra_numero_unique');
            try {
                Schema::table('cotizacion_orden_compra', fn (Blueprint $table) => $table->unique(['id_cotizacion', 'numero']));
            } catch (\Throwable $e) {
                // Índice ya existente.
            }
        }

        if (Schema::hasTable('cotizacion_hes')) {
            $this->quitarIndice('cotizacion_hes', 'cotizacion_hes_codigo_unique');
            try {
                Schema::table('cotizacion_hes', fn (Blueprint $table) => $table->unique(['id_cotizacion', 'codigo']));
            } catch (\Throwable $e) {
                // Índice ya existente.
            }
        }
    }

    private function quitarIndice(string $tabla, string $indice): void
    {
        try {
            Schema::table($tabla, fn (Blueprint $table) => $table->dropUnique($indice));
        } catch (\Throwable $e) {
            // El índice no existe (migración ya aplicada parcialmente).
        }
    }

    private function agregarUnico(string $tabla, string $columna, string $nombre): void
    {
        try {
            Schema::table($tabla, fn (Blueprint $table) => $table->unique($columna, $nombre));
        } catch (\Throwable $e) {
            // El índice ya existe.
        }
    }
};
