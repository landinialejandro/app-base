<?php

// FILE: database/migrations/2026_04_29_000001_add_party_id_to_memberships_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('memberships', 'party_id')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->foreignId('party_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('parties')
                    ->nullOnDelete();

                $table->index(['tenant_id', 'party_id'], 'memberships_tenant_party_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('memberships', 'party_id')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropForeign(['party_id']);
                $table->dropIndex('memberships_tenant_party_id_index');
                $table->dropColumn('party_id');
            });
        }
    }
};