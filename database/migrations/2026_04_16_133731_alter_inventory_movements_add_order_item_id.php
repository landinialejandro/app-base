<?php

// FILE: database/migrations/database/migrations/2026_04_16_133731_alter_inventory_movements_add_order_item_id.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('order_item_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_items')
                ->nullOnDelete();

            $table->index(['tenant_id', 'order_item_id'], 'inventory_movements_tenant_order_item_index');
            $table->index(['tenant_id', 'order_id', 'order_item_id'], 'inventory_movements_tenant_order_order_item_index');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_movements_tenant_order_order_item_index');
            $table->dropIndex('inventory_movements_tenant_order_item_index');
            $table->dropConstrainedForeignId('order_item_id');
        });
    }
};
