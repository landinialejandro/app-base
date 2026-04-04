<?php

// FILE: database/migrations/2026_04_03_210000_add_metadata_to_tasks_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'metadata')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('due_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'metadata')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
