<?php

// FILE: database/migrations/2026_04_16_120001_alter_order_items_add_status.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('pending')
                ->after('quantity');

            $table->index(['tenant_id', 'status'], 'order_items_tenant_status_index');
            $table->index(['tenant_id', 'order_id', 'status'], 'order_items_tenant_order_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_tenant_order_status_index');
            $table->dropIndex('order_items_tenant_status_index');
            $table->dropColumn('status');
        });
    }
};
