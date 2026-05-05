<?php

// FILE: database/migrations/2026_05_05_140800_move_order_task_relation_to_tasks_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'order_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('order_id')
                    ->nullable()
                    ->after('party_id')
                    ->constrained('orders')
                    ->nullOnDelete();

                $table->index(['tenant_id', 'order_id'], 'tasks_tenant_order_idx');
            });
        }

        if (Schema::hasColumn('orders', 'task_id')) {
            DB::table('orders')
                ->whereNotNull('task_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'task_id'])
                ->each(function (object $order): void {
                    DB::table('tasks')
                        ->where('id', $order->task_id)
                        ->whereNull('deleted_at')
                        ->update([
                            'order_id' => $order->id,
                            'updated_at' => now(),
                        ]);
                });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_task_id_unique');
                $table->dropConstrainedForeignId('task_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'order_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_tenant_order_idx');
                $table->dropConstrainedForeignId('order_id');
            });
        }

        if (! Schema::hasColumn('orders', 'task_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('task_id')
                    ->nullable()
                    ->after('asset_id')
                    ->constrained('tasks')
                    ->nullOnDelete();

                $table->unique(['task_id'], 'orders_task_id_unique');
            });
        }
    }
};