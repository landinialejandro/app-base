<?php

// FILE: database/migrations/2026_05_12_133200_add_customer_account_to_self_service_customer_registrations_table.php | V2

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_service_customer_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('self_service_customer_registrations', 'self_service_customer_account_id')) {
                $table->unsignedBigInteger('self_service_customer_account_id')
                    ->nullable()
                    ->after('party_id');
            }

            $table->foreign('self_service_customer_account_id', 'ss_customer_reg_account_fk')
                ->references('id')
                ->on('self_service_customer_accounts')
                ->nullOnDelete();

            $table->index(
                'self_service_customer_account_id',
                'ss_customer_reg_account_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('self_service_customer_registrations', function (Blueprint $table) {
            $table->dropForeign('ss_customer_reg_account_fk');
            $table->dropIndex('ss_customer_reg_account_index');
            $table->dropColumn('self_service_customer_account_id');
        });
    }
};