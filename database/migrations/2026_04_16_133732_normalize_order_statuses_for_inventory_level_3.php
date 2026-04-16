<?php

// FILE: database/migrations/2026_04_16_120002_normalize_order_statuses_for_inventory_level_3.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'confirmed')
            ->update([
                'status' => 'approved',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('status', 'approved')
            ->update([
                'status' => 'confirmed',
                'updated_at' => now(),
            ]);
    }
};
