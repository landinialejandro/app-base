<?php

//FILE: 2026_03_14_220100_alter_documents_add_sequence_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('sequence_prefix', 3)
                ->nullable()
                ->after('number');

            $table->string('point_of_sale', 50)
                ->nullable()
                ->after('sequence_prefix');

            $table->unsignedBigInteger('sequence_number')
                ->nullable()
                ->after('point_of_sale');

            $table->unique(
                ['tenant_id', 'kind', 'point_of_sale', 'sequence_number'],
                'documents_tenant_kind_pos_seq_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_tenant_kind_pos_seq_unique');
            $table->dropColumn([
                'sequence_prefix',
                'point_of_sale',
                'sequence_number',
            ]);
        });
    }
};