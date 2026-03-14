<?php

// FILE: database/migrations/2026_03_14_220100_alter_document_sequences_add_point_of_sale_and_adjust_unique_key.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->string('point_of_sale', 50)
                ->default('0001')
                ->after('prefix');

            $table->dropUnique('document_sequences_tenant_id_branch_id_doc_type_unique');

            $table->unique(
                ['tenant_id', 'doc_type', 'point_of_sale'],
                'document_sequences_tenant_id_doc_type_pos_unique'
            );

            $table->index(
                ['tenant_id', 'doc_type', 'point_of_sale'],
                'document_sequences_tenant_id_doc_type_pos_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropIndex('document_sequences_tenant_id_doc_type_pos_index');
            $table->dropUnique('document_sequences_tenant_id_doc_type_pos_unique');

            $table->dropColumn('point_of_sale');

            $table->unique(
                ['tenant_id', 'branch_id', 'doc_type'],
                'document_sequences_tenant_id_branch_id_doc_type_unique'
            );
        });
    }
};