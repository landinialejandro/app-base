<?php

// FILE: database/migrations/2026_05_18_120000_create_self_service_carts_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_carts', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'ss_carts_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('self_service_customer_account_id')
                ->constrained('self_service_customer_accounts', 'id', 'ss_carts_account_fk')
                ->cascadeOnDelete();

            $table->foreignId('self_service_store_customer_id')
                ->constrained('self_service_store_customers', 'id', 'ss_carts_store_customer_fk')
                ->cascadeOnDelete();

            $table->string('status', 30)->default('active');
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id', 'ss_carts_tenant_index');
            $table->index('self_service_customer_account_id', 'ss_carts_account_index');
            $table->index('self_service_store_customer_id', 'ss_carts_store_customer_index');
            $table->index(['tenant_id', 'status'], 'ss_carts_tenant_status_index');
            $table->index(['tenant_id', 'self_service_store_customer_id', 'status'], 'ss_carts_tenant_store_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_carts');
    }
};
