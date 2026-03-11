<?php

//file:database/migrations/2026_03_11_171437_create_document_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->id();

            // Multi-tenant: NO usar foreignId('tenant_id')
            $table->char('tenant_id', 36)->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->unsignedInteger('position')->default(1);
            $table->string('kind', 20)->default('product');
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'document_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['document_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};