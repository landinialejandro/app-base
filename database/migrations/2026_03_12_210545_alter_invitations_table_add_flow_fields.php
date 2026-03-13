<?php

// FILE: database/migrations/2026_03_12_210545_alter_invitations_table_add_flow_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('type', 30)
                ->default('member_invite')
                ->after('tenant_id');

            $table->string('status', 30)
                ->default('pending')
                ->after('type');

            $table->foreignId('signup_request_id')
                ->nullable()
                ->after('status')
                ->constrained('signup_requests')
                ->nullOnDelete();

            $table->foreignId('invited_by_user_id')
                ->nullable()
                ->after('signup_request_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by_user_id');
            $table->dropConstrainedForeignId('signup_request_id');
            $table->dropColumn('status');
            $table->dropColumn('type');
        });
    }
};