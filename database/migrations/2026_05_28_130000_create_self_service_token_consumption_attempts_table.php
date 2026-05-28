<?php

// FILE: database/migrations/2026_05_28_130000_create_self_service_token_consumption_attempts_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_token_consumption_attempts', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'sstca_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('self_service_customer_account_id')
                ->constrained('self_service_customer_accounts', 'id', 'sstca_account_fk')
                ->cascadeOnDelete();

            $table->foreignId('self_service_store_customer_id')
                ->constrained('self_service_store_customers', 'id', 'sstca_store_customer_fk')
                ->cascadeOnDelete();

            $table->foreignId('self_service_token_pocket_id')
                ->constrained('self_service_token_pockets', 'id', 'sstca_pocket_fk')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products', 'id', 'sstca_product_fk')
                ->cascadeOnDelete();

            $table->decimal('quantity', 12, 2);
            $table->string('unit_label_snapshot', 40)->nullable();
            $table->unsignedInteger('unit_seconds_snapshot')->nullable();
            $table->unsignedInteger('total_seconds')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key')->unique('sstca_idempotency_unique');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(
                ['tenant_id', 'self_service_customer_account_id', 'self_service_store_customer_id'],
                'sstca_tenant_customer_index'
            );
            $table->index(['tenant_id', 'self_service_token_pocket_id', 'status'], 'sstca_tenant_pocket_status_idx');
            $table->index(['tenant_id', 'status'], 'sstca_tenant_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_token_consumption_attempts');
    }
};
