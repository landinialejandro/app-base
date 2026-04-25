<?php

// FILE: database/migrations/2026_04_25_000001_add_group_to_documents_table.php | V1

use App\Support\Catalogs\DocumentCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('group', 30)
                ->default(DocumentCatalog::GROUP_SALE)
                ->after('asset_id');

            $table->index(['tenant_id', 'group']);
            $table->index(['tenant_id', 'group', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'group', 'kind']);
            $table->dropIndex(['tenant_id', 'group']);
            $table->dropColumn('group');
        });
    }
};