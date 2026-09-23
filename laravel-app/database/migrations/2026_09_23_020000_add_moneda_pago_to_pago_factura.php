<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pago_factura')) {
            return;
        }

        if (!Schema::hasColumn('pago_factura', 'moneda_pago')) {
            Schema::table('pago_factura', function (Blueprint $table) {
                $table->string('moneda_pago', 3)->nullable()->after('monto_pagado');
            });
        }

        if (!Schema::hasColumn('pago_factura', 'monto_original')) {
            Schema::table('pago_factura', function (Blueprint $table) {
                $table->decimal('monto_original', 12, 2)->nullable()->after('moneda_pago');
            });
        }

        if (!Schema::hasColumn('pago_factura', 'monto_cambio_pago')) {
            Schema::table('pago_factura', function (Blueprint $table) {
                $table->decimal('monto_cambio_pago', 10, 4)->nullable()->after('monto_original');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pago_factura')) {
            return;
        }

        foreach (['monto_cambio_pago', 'monto_original', 'moneda_pago'] as $columna) {
            if (Schema::hasColumn('pago_factura', $columna)) {
                Schema::table('pago_factura', function (Blueprint $table) use ($columna) {
                    $table->dropColumn($columna);
                });
            }
        }
    }
};
