<?php

// FILE: database/migrations/2026_03_15_184700_add_asset_id_to_orders_table.php | V2

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'asset_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('asset_id')
                    ->nullable()
                    ->after('party_id')
                    ->constrained('assets')
                    ->nullOnDelete();

                $table->index(['tenant_id', 'asset_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'asset_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'asset_id']);
                $table->dropConstrainedForeignId('asset_id');
            });
        }
    }
};