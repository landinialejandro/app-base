<?php

// FILE: database/migrations/2026_05_12_211000_create_product_components_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('component_product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit_label')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'component_product_id']);
            $table->unique(
                ['tenant_id', 'product_id', 'component_product_id'],
                'product_components_unique_component'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};