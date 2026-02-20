<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up() {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')->nullable()->after('approved_at');
            $table->boolean('deletion_approved')->default(false)->after('deletion_requested_at');
            $table->timestamp('deleted_at')->nullable()->after('deletion_approved'); // Soft delete
        });
    }

    public function down() {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['deletion_requested_at', 'deletion_approved', 'deleted_at']);
        });
    }
};
