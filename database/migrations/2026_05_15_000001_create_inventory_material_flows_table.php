<?php

// FILE: database/migrations/2026_05_15_000001_create_inventory_material_flows_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_material_flows', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();

            $table->foreignId('inventory_movement_id')
                ->nullable()
                ->constrained('inventory_movements')
                ->nullOnDelete();

            $table->string('flow_type', 40);
            $table->decimal('quantity', 12, 2);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id']);
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'order_item_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'flow_type']);
            $table->index(['tenant_id', 'inventory_movement_id'], 'inventory_material_flows_tenant_movement_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_material_flows');
    }
};
