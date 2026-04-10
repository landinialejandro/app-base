<?php

// FILE: database/migrations/2026_04_01_100004_backfill_role_permissions_v2_base.php | V4

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Migración histórica neutralizada.
        // El contrato vigente de permisos se siembra desde seeders limpios.
    }

    public function down(): void
    {
        //
    }
};
