<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            // Agregar organization_id DESPUÉS de que existe la tabla organizations
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('role')->default('user')->after('organization_id');
            $table->timestamp('approved_at')->nullable()->after('role');
            $table->boolean('is_platform_admin')->default(false)->after('approved_at');
        });
    }

    public function down() {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'role', 'approved_at', 'is_platform_admin']);
        });
    }
};
