<?php

// FILE: database/migrations/2026_06_02_191000_make_external_customer_nullable_on_self_service_token_pockets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE self_service_token_pockets MODIFY self_service_customer_account_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE self_service_token_pockets MODIFY self_service_store_customer_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE self_service_token_pockets MODIFY self_service_customer_account_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE self_service_token_pockets MODIFY self_service_store_customer_id BIGINT UNSIGNED NOT NULL');
    }
};
