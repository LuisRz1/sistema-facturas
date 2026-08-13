<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Índice compuesto para el filtro principal: activo + fecha_emision
        Schema::table('factura', function (Blueprint $table) {
            $table->index(['activo', 'fecha_emision'], 'idx_factura_activo_fecha');
            $table->index('fecha_vencimiento', 'idx_factura_vencimiento');
        });

        // Índice compuesto para las notificaciones (id_factura + canal)
        Schema::table('notificacion_factura', function (Blueprint $table) {
            $table->index(['id_factura', 'canal'], 'idx_notif_factura_canal');
        });

        // Índice para el historial de importaciones (ORDER BY fecha_inicio)
        Schema::table('sincronizacion_nubefact', function (Blueprint $table) {
            $table->index('fecha_inicio', 'idx_sync_fecha_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->dropIndex('idx_factura_activo_fecha');
            $table->dropIndex('idx_factura_vencimiento');
        });

        Schema::table('notificacion_factura', function (Blueprint $table) {
            $table->dropIndex('idx_notif_factura_canal');
        });

        Schema::table('sincronizacion_nubefact', function (Blueprint $table) {
            $table->dropIndex('idx_sync_fecha_inicio');
        });
    }
};
