<?php

// FILE: database/migrations/2026_03_15_153257_create_assets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36)->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('party_id')
                ->nullable()
                ->constrained('parties')
                ->nullOnDelete();

            $table->string('kind', 50);
            $table->string('relationship_type', 50);
            $table->string('name');
            $table->string('internal_code', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'kind']);
            $table->index(['tenant_id', 'relationship_type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'party_id']);
            $table->index(['tenant_id', 'internal_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};