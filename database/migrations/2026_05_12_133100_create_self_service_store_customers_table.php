<?php

// FILE: database/migrations/2026_05_12_133100_create_self_service_store_customers_table.php | V3

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_store_customers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('self_service_customer_account_id');
            $table->char('tenant_id', 36);
            $table->unsignedBigInteger('party_id');

            $table->string('status', 50)->default('active');

            $table->string('identity_stage', 80)->default('email_confirmed');
            $table->boolean('operation_enabled')->default(false);

            $table->timestamp('identity_completed_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('self_service_customer_account_id', 'ss_store_customer_account_fk')
                ->references('id')
                ->on('self_service_customer_accounts')
                ->cascadeOnDelete();

            $table->foreign('tenant_id', 'ss_store_customer_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('party_id', 'ss_store_customer_party_fk')
                ->references('id')
                ->on('parties')
                ->cascadeOnDelete();

            $table->unique(
                ['self_service_customer_account_id', 'tenant_id'],
                'ss_store_customers_account_tenant_unique'
            );

            $table->unique(
                ['tenant_id', 'party_id'],
                'ss_store_customers_tenant_party_unique'
            );

            $table->index(
                ['tenant_id', 'status'],
                'ss_store_customers_tenant_status_index'
            );

            $table->index(
                ['tenant_id', 'operation_enabled'],
                'ss_store_customers_tenant_operation_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_store_customers');
    }
};