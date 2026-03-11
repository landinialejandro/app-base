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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('doc_type', 30); // ej: invoice, work_order, receipt
            $table->string('prefix', 3);    // ej: FAC, OTR
            $table->unsignedInteger('padding')->default(8); // 00000001
            $table->unsignedBigInteger('next_number')->default(1);

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            // Un tipo de doc por tenant (y opcionalmente por sucursal)
            $table->unique(['tenant_id', 'branch_id', 'doc_type']);

            $table->index(['tenant_id']);
            $table->index(['tenant_id', 'doc_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
