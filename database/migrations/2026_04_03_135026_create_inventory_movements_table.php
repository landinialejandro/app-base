<?php

// FILE: database/migrations/2026_04_03_135026_create_inventory_movements_table.php | V2

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('origin_type', 40)->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();

            $table->string('origin_line_type', 40)->nullable();
            $table->unsignedBigInteger('origin_line_id')->nullable();

            $table->string('kind', 20);
            $table->decimal('quantity', 12, 2);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'origin_type', 'origin_id'], 'inventory_movements_tenant_origin_index');
            $table->index(['tenant_id', 'origin_line_type', 'origin_line_id'], 'inventory_movements_tenant_origin_line_index');
            $table->index(['tenant_id', 'kind']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};