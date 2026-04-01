<?php

// FILE: database/migrations/2026_04_01_100001_add_profile_slug_to_memberships_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('memberships', 'profile_slug')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->string('profile_slug')->nullable()->after('is_owner');
                $table->index(['tenant_id', 'profile_slug'], 'memberships_tenant_profile_slug_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('memberships', 'profile_slug')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropIndex('memberships_tenant_profile_slug_index');
                $table->dropColumn('profile_slug');
            });
        }
    }
};
