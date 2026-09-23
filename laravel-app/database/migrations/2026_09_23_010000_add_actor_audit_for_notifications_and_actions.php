<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('notificacion_factura') && !Schema::hasColumn('notificacion_factura', 'id_usuario')) {
            Schema::table('notificacion_factura', function (Blueprint $table) {
                $table->unsignedInteger('id_usuario')->nullable()->after('id_factura');
                $table->index(['id_usuario', 'fecha_creacion'], 'idx_notif_usuario_fecha');
            });
        }

        if (!Schema::hasTable('auditoria_accion')) {
            Schema::create('auditoria_accion', function (Blueprint $table) {
                $table->bigIncrements('id_auditoria');
                $table->unsignedInteger('id_usuario')->nullable();
                $table->string('entidad', 60);
                $table->unsignedBigInteger('id_entidad')->nullable();
                $table->string('accion', 80);
                $table->json('detalle')->nullable();
                $table->string('ip_origen', 45)->nullable();
                $table->timestamp('fecha_creacion');
                $table->index(['entidad', 'id_entidad', 'fecha_creacion'], 'idx_auditoria_entidad_fecha');
                $table->index(['id_usuario', 'fecha_creacion'], 'idx_auditoria_usuario_fecha');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('auditoria_accion')) {
            Schema::drop('auditoria_accion');
        }

        if (Schema::hasTable('notificacion_factura') && Schema::hasColumn('notificacion_factura', 'id_usuario')) {
            Schema::table('notificacion_factura', function (Blueprint $table) {
                $table->dropIndex('idx_notif_usuario_fecha');
                $table->dropColumn('id_usuario');
            });
        }
    }
};
