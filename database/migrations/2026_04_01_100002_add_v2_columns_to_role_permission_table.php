<?php
// FILE: database/migrations/2026_04_01_100002_add_v2_columns_to_role_permission_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permission', function (Blueprint $table) {
            if (! Schema::hasColumn('role_permission', 'scope')) {
                $table->string('scope')->nullable()->after('permission_id');
            }

            if (! Schema::hasColumn('role_permission', 'execution_mode')) {
                $table->string('execution_mode')->nullable()->after('scope');
            }

            if (! Schema::hasColumn('role_permission', 'constraints')) {
                $table->json('constraints')->nullable()->after('execution_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('role_permission', function (Blueprint $table) {
            if (Schema::hasColumn('role_permission', 'constraints')) {
                $table->dropColumn('constraints');
            }

            if (Schema::hasColumn('role_permission', 'execution_mode')) {
                $table->dropColumn('execution_mode');
            }

            if (Schema::hasColumn('role_permission', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};