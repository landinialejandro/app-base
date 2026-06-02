<?php

// FILE: database/migrations/2026_06_02_190000_add_party_id_to_self_service_token_pockets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_service_token_pockets', function (Blueprint $table) {
            $table->foreignId('party_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('parties', 'id', 'sstp_party_fk')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'party_id'], 'sstp_tenant_party_index');
        });

        DB::table('self_service_token_pockets as pockets')
            ->join('self_service_store_customers as customers', 'customers.id', '=', 'pockets.self_service_store_customer_id')
            ->whereNull('pockets.party_id')
            ->update([
                'pockets.party_id' => DB::raw('customers.party_id'),
            ]);

        $duplicates = DB::table('self_service_token_pockets')
            ->select('tenant_id', 'party_id', 'product_id')
            ->selectRaw('count(*) as total')
            ->whereNotNull('party_id')
            ->groupBy('tenant_id', 'party_id', 'product_id')
            ->havingRaw('count(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException('No se puede crear unique de pockets por tenant, party y producto porque existen duplicados.');
        }

        Schema::table('self_service_token_pockets', function (Blueprint $table) {
            $table->unique(
                ['tenant_id', 'party_id', 'product_id'],
                'sstp_unique_party_product'
            );
        });
    }

    public function down(): void
    {
        Schema::table('self_service_token_pockets', function (Blueprint $table) {
            $table->dropUnique('sstp_unique_party_product');
            $table->dropIndex('sstp_tenant_party_index');
            $table->dropForeign('sstp_party_fk');
            $table->dropColumn('party_id');
        });
    }
};
