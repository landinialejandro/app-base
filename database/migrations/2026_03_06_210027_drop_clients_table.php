<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('clients');
    }

    public function down(): void
    {
        // vacío por ahora, salvo que quieras reconstruir la tabla transitoria
    }
};