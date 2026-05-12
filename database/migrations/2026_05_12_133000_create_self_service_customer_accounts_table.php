<?php

// FILE: database/migrations/2026_05_12_133000_create_self_service_customer_accounts_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_customer_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('email');
            $table->string('display_name')->nullable();
            $table->string('phone', 100)->nullable();

            $table->string('status', 50)->default('active');

            $table->timestamp('email_confirmed_at')->nullable();
            $table->timestamp('last_access_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique('email', 'ss_customer_accounts_email_unique');
            $table->index('status', 'ss_customer_accounts_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_customer_accounts');
    }
};