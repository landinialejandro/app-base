<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('assigned_user_id')->constrained('users')->restrictOnDelete();

            $table->string('kind', 50);
            $table->string('status', 50);
            $table->string('work_mode', 50)->nullable();

            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->string('workstation_name')->nullable();

            $table->date('scheduled_date');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_all_day')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'scheduled_date']);
            $table->index(['tenant_id', 'assigned_user_id', 'scheduled_date'], 'appointments_tenant_user_date_idx');
            $table->index(['tenant_id', 'kind', 'status'], 'appointments_tenant_kind_status_idx');
            $table->index(['tenant_id', 'order_id'], 'appointments_tenant_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
