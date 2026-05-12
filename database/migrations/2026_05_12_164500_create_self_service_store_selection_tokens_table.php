<?php

// FILE: database/migrations/2026_05_12_164500_create_self_service_store_selection_tokens_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_store_selection_tokens', function (Blueprint $table) {
            $table->id();

            $table->char('token_hash', 64);

            $table->unsignedBigInteger('self_service_customer_account_id');

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique('token_hash', 'ss_store_selection_token_hash_unique');

            $table->foreign('self_service_customer_account_id', 'ss_store_selection_account_fk')
                ->references('id')
                ->on('self_service_customer_accounts')
                ->cascadeOnDelete();

            $table->index(
                ['self_service_customer_account_id', 'expires_at'],
                'ss_store_selection_account_expires_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_service_store_selection_tokens');
    }
};