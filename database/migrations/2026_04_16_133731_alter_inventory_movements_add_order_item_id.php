<?php

// FILE: database/migrations/2026_04_16_133731_alter_inventory_movements_add_order_item_id.php | V2

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Migración neutralizada.
        // inventory_movements ya no usa order_item_id.
        // La trazabilidad operativa se resuelve mediante origin_type/origin_id
        // y origin_line_type/origin_line_id.
    }

    public function down(): void
    {
        //
    }
};