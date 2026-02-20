v<?php
// database/migrations/xxxx_make_email_unique_globally.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar índice único anterior si existe
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
        
        // Crear nuevo índice único global
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }
};