<?php

// FILE: database/migrations/2026_05_28_180000_create_self_service_shop_consumption_points_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_shop_consumption_points', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'ssscp_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('self_service_shop_id')
                ->constrained('self_service_shops', 'id', 'ssscp_shop_fk')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 50)->default('active');
            $table->string('public_token')->unique('ssscp_public_token_unique');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'self_service_shop_id', 'status'], 'ssscp_tenant_shop_status_idx');
            $table->index(['tenant_id', 'self_service_shop_id', 'code'], 'ssscp_tenant_shop_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_shop_consumption_points');
    }
};
