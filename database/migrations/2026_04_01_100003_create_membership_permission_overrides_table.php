<?php

// FILE: database/migrations/2026_04_01_100003_create_membership_permission_overrides_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_permission_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('membership_id')
                ->constrained('memberships')
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();

            $table->boolean('is_allowed')->nullable();
            $table->string('scope')->nullable();
            $table->string('execution_mode')->nullable();
            $table->json('constraints')->nullable();

            $table->timestamps();

            $table->unique(
                ['membership_id', 'permission_id'],
                'membership_permission_overrides_unique'
            );

            $table->index(['permission_id'], 'membership_permission_overrides_permission_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_permission_overrides');
    }
};
