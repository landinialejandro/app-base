<?php

// FILE: database/migrations/2026_05_14_140000_make_self_service_shop_id_required_on_self_service_shop_items_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $itemsWithoutShop = DB::table('self_service_shop_items')
            ->whereNull('self_service_shop_id')
            ->count();

        if ($itemsWithoutShop > 0) {
            throw new \RuntimeException(
                'No se puede hacer obligatorio self_service_shop_id porque existen artículos de tienda sin tienda asociada.'
            );
        }

        Schema::table('self_service_shop_items', function (Blueprint $table) {
            $table->dropForeign('ss_shop_items_shop_fk');
        });

        DB::statement('ALTER TABLE self_service_shop_items MODIFY self_service_shop_id BIGINT UNSIGNED NOT NULL');

        Schema::table('self_service_shop_items', function (Blueprint $table) {
            $table->foreign('self_service_shop_id', 'ss_shop_items_shop_fk')
                ->references('id')
                ->on('self_service_shops')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('self_service_shop_items', function (Blueprint $table) {
            $table->dropForeign('ss_shop_items_shop_fk');
        });

        DB::statement('ALTER TABLE self_service_shop_items MODIFY self_service_shop_id BIGINT UNSIGNED NULL');

        Schema::table('self_service_shop_items', function (Blueprint $table) {
            $table->foreign('self_service_shop_id', 'ss_shop_items_shop_fk')
                ->references('id')
                ->on('self_service_shops')
                ->cascadeOnDelete();
        });
    }
};
