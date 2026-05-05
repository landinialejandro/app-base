<?php

// FILE: database/migrations/2026_03_10_211217_create_orders_table.php | V4

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

            $table->string('counterparty_name')->nullable();

            $table->string('group', 20)->default('sale');
            $table->string('kind', 20)->default('standard');

            $table->string('number')->nullable();
            $table->string('sequence_prefix', 3)->nullable();
            $table->string('point_of_sale', 50)->nullable();
            $table->unsignedBigInteger('sequence_number')->nullable();

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

            $table->index(['tenant_id', 'party_id']);
            $table->index(['tenant_id', 'counterparty_name'], 'orders_tenant_counterparty_name_index');
            $table->index(['tenant_id', 'group'], 'orders_tenant_group_index');
            $table->index(['tenant_id', 'kind']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'ordered_at']);
            $table->index(['tenant_id', 'number']);

            $table->unique(
                ['tenant_id', 'group', 'point_of_sale', 'sequence_number'],
                'orders_tenant_group_pos_seq_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};