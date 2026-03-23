<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->whereNotNull('deleted_at')
            ->whereNotNull('task_id')
            ->update([
                'task_id' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No reversible de forma segura:
        // no puede reconstruirse qué task_id tenía cada orden soft-deleted.
    }
};
