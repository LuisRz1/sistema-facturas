<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alterar el ENUM de estado para incluir ANULADO
        DB::statement("ALTER TABLE factura MODIFY COLUMN estado ENUM('PENDIENTE', 'VENCIDO', 'PAGADA', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE', 'ANULADO') NOT NULL DEFAULT 'PENDIENTE'");
    }

    public function down(): void
    {
        // Revertir al ENUM anterior
        DB::statement("ALTER TABLE factura MODIFY COLUMN estado ENUM('PENDIENTE', 'VENCIDO', 'PAGADA', 'PAGO PARCIAL', 'DIFERENCIA PENDIENTE') NOT NULL DEFAULT 'PENDIENTE'");
    }
};
