<?php

// FILE: database/migrations/2026_03_10_211217_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
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

            $table->string('kind', 20)->default('sale');
            $table->string('number')->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('ordered_at')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'kind']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'ordered_at']);
            $table->index(['tenant_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};