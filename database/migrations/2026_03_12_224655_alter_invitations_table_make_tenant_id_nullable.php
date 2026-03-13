<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'invitations')
            ->where('COLUMN_NAME', 'tenant_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if ($foreignKeyExists) {
            Schema::table('invitations', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
        }

        DB::statement('ALTER TABLE invitations MODIFY tenant_id CHAR(36) NULL');

        Schema::table('invitations', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'invitations')
            ->where('COLUMN_NAME', 'tenant_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if ($foreignKeyExists) {
            Schema::table('invitations', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
        }

        DB::statement('ALTER TABLE invitations MODIFY tenant_id CHAR(36) NOT NULL');

        Schema::table('invitations', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }
};