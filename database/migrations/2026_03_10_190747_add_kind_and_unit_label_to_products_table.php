<?php

// FILE: database/migrations/2026_03_10_190747_add_kind_and_unit_label_to_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('kind')->default('product')->after('tenant_id');
            $table->string('unit_label')->default('unidad')->after('price');

            $table->index(['tenant_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'kind']);
            $table->dropColumn(['kind', 'unit_label']);
        });
    }
};