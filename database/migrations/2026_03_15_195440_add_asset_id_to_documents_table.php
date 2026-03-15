<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('asset_id')
                ->nullable()
                ->after('order_id')
                ->constrained('assets')
                ->nullOnDelete();

            $table->index(['tenant_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'asset_id']);
            $table->dropConstrainedForeignId('asset_id');
        });
    }
};
