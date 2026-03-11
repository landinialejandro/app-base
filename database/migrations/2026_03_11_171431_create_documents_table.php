<?php

//FILE:database/migrations/2026_03_11_171431_create_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
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

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->string('kind', 30)->default('quote');
            $table->string('number')->nullable();
            $table->string('status', 20)->default('draft');

            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();

            $table->string('currency_code', 10)->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

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
            $table->index(['tenant_id', 'issued_at']);
            $table->index(['tenant_id', 'number']);
            $table->index(['tenant_id', 'party_id']);
            $table->index(['tenant_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};