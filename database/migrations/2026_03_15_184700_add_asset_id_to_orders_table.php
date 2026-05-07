<?php

// FILE: database/migrations/2026_03_15_184700_add_asset_id_to_orders_table.php | V4

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'asset_id')) {
                $table->foreignId('asset_id')
                    ->nullable()
                    ->after('counterparty_reference')
                    ->constrained('assets')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'asset_reference')) {
                $table->string('asset_reference')
                    ->nullable()
                    ->after('asset_id');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'asset_id')) {
                $table->index(['tenant_id', 'asset_id'], 'orders_tenant_asset_id_index');
            }

            if (Schema::hasColumn('orders', 'asset_reference')) {
                $table->index(['tenant_id', 'asset_reference'], 'orders_tenant_asset_reference_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'asset_reference')) {
                $table->dropIndex('orders_tenant_asset_reference_index');
                $table->dropColumn('asset_reference');
            }

            if (Schema::hasColumn('orders', 'asset_id')) {
                $table->dropIndex('orders_tenant_asset_id_index');
                $table->dropConstrainedForeignId('asset_id');
            }
        });
    }
};