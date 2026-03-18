<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('priority', 20)
                ->default('medium')
                ->after('status');

            $table->index(['tenant_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'priority']);
            $table->dropColumn('priority');
        });
    }
};
