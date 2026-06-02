<?php

// FILE: database/migrations/2026_06_02_180000_add_is_stockable_to_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_stockable')->default(true)->after('kind');

            $table->index(['tenant_id', 'is_stockable']);
        });

        DB::table('products')
            ->where('kind', 'product')
            ->update(['is_stockable' => true]);

        DB::table('products')
            ->where('kind', 'service')
            ->update(['is_stockable' => false]);

        DB::table('products')
            ->where('kind', 'intangible')
            ->update(['is_stockable' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'is_stockable']);
            $table->dropColumn('is_stockable');
        });
    }
};
