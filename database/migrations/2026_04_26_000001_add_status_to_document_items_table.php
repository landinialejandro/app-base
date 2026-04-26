<?php

// FILE: database/migrations/2026_04_26_000001_add_status_to_document_items_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_items', function (Blueprint $table): void {
            $table->string('status', 20)
                ->default('pending')
                ->after('quantity');

            $table->index(['tenant_id', 'status'], 'document_items_tenant_status_index');
            $table->index(['tenant_id', 'document_id', 'status'], 'document_items_tenant_document_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table): void {
            $table->dropIndex('document_items_tenant_document_status_index');
            $table->dropIndex('document_items_tenant_status_index');

            $table->dropColumn('status');
        });
    }
};