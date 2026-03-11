<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('invited'); // invited|active|blocked|left
            $table->boolean('is_owner')->default(false);

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->text('blocked_reason')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
