<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addIndexes([
            // Búsqueda de notas de crédito por documento modificado (ReporteController, FacturaController)
            ['credito', ['serie_doc_modificado', 'numero_doc_modificado'], 'idx_credito_doc_modificado'],
            // Sumas de pagos activos por factura (procesarPago, editarPago, eliminarPago, pago masivo)
            ['pago_factura', ['id_factura', 'activo'], 'idx_pago_factura_activo'],
            // Facturas pendientes por cliente dentro de un rango (facturasPendientesCliente, reportes)
            ['factura', ['id_cliente', 'activo', 'fecha_emision'], 'idx_factura_cliente_activo_fecha'],
            // Orden/filtro de clientes por tipo en dashboard y reportes
            ['cliente', ['tipo_cliente', 'razon_social'], 'idx_cliente_tipo_razon'],
            // Última notificación por factura y canal (listado de facturas)
            ['notificacion_factura', ['id_factura', 'canal', 'id_notificacion'], 'idx_notif_factura_canal_id'],
        ]);
    }

    public function down(): void
    {
        $this->dropIndexes([
            ['credito', 'idx_credito_doc_modificado'],
            ['pago_factura', 'idx_pago_factura_activo'],
            ['factura', 'idx_factura_cliente_activo_fecha'],
            ['cliente', 'idx_cliente_tipo_razon'],
            ['notificacion_factura', 'idx_notif_factura_canal_id'],
        ]);
    }

    /**
     * @param array<int, array{0:string,1:array<int,string>,2:string}> $indexes
     */
    private function addIndexes(array $indexes): void
    {
        foreach ($indexes as [$table, $columns, $name]) {
            if (!Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                $blueprint->index($columns, $name);
            });
        }
    }

    /**
     * @param array<int, array{0:string,1:string}> $indexes
     */
    private function dropIndexes(array $indexes): void
    {
        foreach ($indexes as [$table, $name]) {
            if (!Schema::hasTable($table) || !Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropIndex($name);
            });
        }
    }
};
