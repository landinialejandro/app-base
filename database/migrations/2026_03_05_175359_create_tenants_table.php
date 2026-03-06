<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(table: 'tenants', callback: function (Blueprint $table): void {
            $table->uuid(column: 'id')->primary();
            $table->string(column: 'name');
            $table->string(column: 'slug')->unique();
            $table->json(column: 'settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(table: 'tenants');
    }
};
