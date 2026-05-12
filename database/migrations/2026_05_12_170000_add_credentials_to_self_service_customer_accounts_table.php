<?php

// FILE: database/migrations/2026_05_12_170000_add_credentials_to_self_service_customer_accounts_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_service_customer_accounts', function (Blueprint $table) {
            $table->string('password_hash')->nullable()->after('phone');
            $table->timestamp('password_set_at')->nullable()->after('password_hash');
            $table->boolean('password_needs_reset')->default(false)->after('password_set_at');
            $table->boolean('access_enabled')->default(false)->after('password_needs_reset');

            $table->index(
                ['access_enabled', 'status'],
                'ss_customer_accounts_access_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('self_service_customer_accounts', function (Blueprint $table) {
            $table->dropIndex('ss_customer_accounts_access_status_idx');

            $table->dropColumn([
                'password_hash',
                'password_set_at',
                'password_needs_reset',
                'access_enabled',
            ]);
        });
    }
};