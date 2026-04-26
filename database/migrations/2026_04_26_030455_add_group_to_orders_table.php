<?php

// FILE: database/migrations/2026_04_26_030455_add_group_to_orders_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('group', 20)
                ->nullable()
                ->after('task_id');

            $table->index(['tenant_id', 'group'], 'orders_tenant_group_index');
        });

        DB::table('orders')
            ->whereNull('group')
            ->update([
                'group' => DB::raw('kind'),
            ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('group', 20)
                ->nullable(false)
                ->default('sale')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_tenant_group_index');
            $table->dropColumn('group');
        });
    }
};