<?php

//FILE: database/migrations/2026_03_06_205103_create_parties_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36)->index();

            $table->string('kind', 50);
            $table->string('name');
            $table->string('display_name')->nullable();

            $table->string('document_type', 50)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->string('tax_id', 50)->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('address')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'kind']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'tax_id']);
            $table->index(['tenant_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};