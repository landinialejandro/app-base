<?php

// FILE: database/migrations/2026_05_11_120000_create_self_service_customer_registrations_table.php | V2

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_customer_registrations', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36)->index('sscr_tenant_idx');
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();

            $table->string('status', 50)->default('pending')->index('sscr_status_idx');
            $table->string('token', 100)->unique('sscr_token_unique');

            $table->string('name');
            $table->string('display_name')->nullable();

            $table->string('document_type', 50)->default('DNI');
            $table->string('document_number', 100);

            $table->string('email');
            $table->string('phone', 100);

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->string('accepted_ip', 100)->nullable();
            $table->text('user_agent')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'sscr_tenant_status_idx');
            $table->index(['tenant_id', 'email'], 'sscr_tenant_email_idx');
            $table->index(['tenant_id', 'phone'], 'sscr_tenant_phone_idx');
            $table->index(['tenant_id', 'document_number'], 'sscr_tenant_doc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_customer_registrations');
    }
};