<?php

// FILE: database/migrations/2026_03_12_210543_create_signup_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signup_requests', function (Blueprint $table) {
            $table->id();

            $table->string('requested_name');
            $table->string('requested_email');
            $table->string('company_name');
            $table->string('phone_whatsapp', 50);

            $table->string('status', 30)->default('pending');

            $table->text('review_notes')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_requests');
    }
};