<?php

// FILE: database/migrations/2026_05_28_120000_create_self_service_token_pockets_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_token_pockets', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'sstp_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('self_service_customer_account_id')
                ->constrained('self_service_customer_accounts', 'id', 'sstp_account_fk')
                ->cascadeOnDelete();

            $table->foreignId('self_service_store_customer_id')
                ->constrained('self_service_store_customers', 'id', 'sstp_store_customer_fk')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->decimal('quantity_available', 12, 2)->default(0);
            $table->string('unit_label_snapshot', 40)->nullable();
            $table->unsignedInteger('unit_seconds_snapshot')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'self_service_customer_account_id', 'self_service_store_customer_id', 'product_id'],
                'sstp_unique_customer_product'
            );
            $table->index(['tenant_id', 'status'], 'sstp_tenant_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_token_pockets');
    }
};
