<?php

// FILE: database/migrations/2026_05_14_120000_create_self_service_shop_items_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_shop_items', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'ss_shop_items_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->boolean('is_visible')->default(false);
            $table->boolean('use_product_price')->default(true);
            $table->decimal('price', 12, 2)->nullable();
            $table->string('display_name')->nullable();
            $table->text('display_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft');
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'is_visible'], 'ss_shop_items_visibility_index');
            $table->index(['tenant_id', 'product_id'], 'ss_shop_items_tenant_product_index');
            $table->index(['tenant_id', 'sort_order'], 'ss_shop_items_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_shop_items');
    }
};
