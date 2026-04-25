<?php

// FILE: database/migrations/2026_04_24_190000_create_inventory_operations_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_operations', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->string('operation_type', 40);

            $table->string('origin_type', 40)->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();

            $table->string('origin_line_type', 40)->nullable();
            $table->unsignedBigInteger('origin_line_id')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id']);
            $table->index(['tenant_id', 'operation_type']);
            $table->index(['tenant_id', 'origin_type', 'origin_id'], 'inventory_operations_tenant_origin_index');
            $table->index(['tenant_id', 'origin_line_type', 'origin_line_id'], 'inventory_operations_tenant_origin_line_index');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_operations');
    }
};