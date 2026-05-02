<?php

// FILE: database/migrations/2026_05_02_180000_create_operational_activities_table.php | V2

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('subject_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('module', 80);
            $table->string('record_type');
            $table->unsignedBigInteger('record_id');

            $table->string('activity_type', 80);
            $table->timestamp('occurred_at')->useCurrent();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'occurred_at'], 'operational_activities_tenant_occurred_idx');
            $table->index(['tenant_id', 'module', 'occurred_at'], 'operational_activities_tenant_module_idx');
            $table->index(['tenant_id', 'actor_user_id', 'occurred_at'], 'operational_activities_tenant_actor_idx');
            $table->index(['tenant_id', 'subject_user_id', 'occurred_at'], 'operational_activities_tenant_subject_idx');
            $table->index(['record_type', 'record_id'], 'operational_activities_record_idx');
            $table->index(['activity_type', 'occurred_at'], 'operational_activities_type_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_activities');
    }
};