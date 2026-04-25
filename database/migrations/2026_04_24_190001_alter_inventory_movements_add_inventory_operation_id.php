<?php

// FILE: database/migrations/2026_04_24_190001_alter_inventory_movements_add_inventory_operation_id.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('inventory_operation_id')
                ->nullable()
                ->after('product_id')
                ->constrained('inventory_operations')
                ->nullOnDelete();

            $table->index(
                ['tenant_id', 'inventory_operation_id'],
                'inventory_movements_tenant_operation_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inventory_movements_tenant_operation_index');
            $table->dropConstrainedForeignId('inventory_operation_id');
        });
    }
};