<?php

// FILE: database/migrations/2026_05_28_120001_create_self_service_token_pocket_movements_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_token_pocket_movements', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'sstpm_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('self_service_token_pocket_id')
                ->constrained('self_service_token_pockets', 'id', 'sstpm_pocket_fk')
                ->cascadeOnDelete();

            $table->foreignId('self_service_cart_id')
                ->nullable()
                ->constrained('self_service_carts', 'id', 'sstpm_cart_fk')
                ->nullOnDelete();

            $table->foreignId('self_service_cart_item_id')
                ->nullable()
                ->constrained('self_service_cart_items', 'id', 'sstpm_cart_item_fk')
                ->nullOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders', 'id', 'sstpm_order_fk')
                ->nullOnDelete();

            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained('order_items', 'id', 'sstpm_order_item_fk')
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('movement_type', 40);
            $table->decimal('quantity', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('unit_label_snapshot', 40)->nullable();
            $table->unsignedInteger('unit_seconds_snapshot')->nullable();
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique('idempotency_key', 'sstpm_idempotency_unique');
            $table->index(['tenant_id', 'self_service_token_pocket_id'], 'sstpm_tenant_pocket_index');
            $table->index(['tenant_id', 'movement_type'], 'sstpm_tenant_type_index');
            $table->index(['tenant_id', 'product_id'], 'sstpm_tenant_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_token_pocket_movements');
    }
};
