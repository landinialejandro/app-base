<?php

// FILE: database/migrations/2026_05_18_120100_create_self_service_cart_items_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_cart_items', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'ss_cart_items_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('self_service_cart_id')
                ->constrained('self_service_carts', 'id', 'ss_cart_items_cart_fk')
                ->cascadeOnDelete();

            $table->foreignId('self_service_shop_item_id')
                ->constrained('self_service_shop_items', 'id', 'ss_cart_items_shop_item_fk')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price_snapshot', 12, 2)->default(0);
            $table->string('display_name_snapshot');
            $table->string('unit_label_snapshot')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'ss_cart_items_tenant_index');
            $table->index('self_service_cart_id', 'ss_cart_items_cart_index');
            $table->index('self_service_shop_item_id', 'ss_cart_items_shop_item_index');
            $table->index('product_id', 'ss_cart_items_product_index');
            $table->unique(['self_service_cart_id', 'self_service_shop_item_id'], 'ss_cart_items_cart_shop_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_cart_items');
    }
};
