<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('task_id')
                ->nullable()
                ->after('asset_id')
                ->constrained('tasks')
                ->nullOnDelete();

            $table->unique(['task_id'], 'orders_task_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_task_id_unique');
            $table->dropConstrainedForeignId('task_id');
        });
    }
};
