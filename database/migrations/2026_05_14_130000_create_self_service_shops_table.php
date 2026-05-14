<?php

// FILE: database/migrations/2026_05_14_130000_create_self_service_shops_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_service_shops', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->foreign('tenant_id', 'ss_shops_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status'], 'ss_shops_tenant_status_index');
        });

        Schema::table('self_service_shop_items', function (Blueprint $table) {
            $table->foreignId('self_service_shop_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('self_service_shops', 'id', 'ss_shop_items_shop_fk')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'self_service_shop_id'], 'ss_shop_items_tenant_shop_index');
        });

        $tenantIds = DB::table('self_service_shop_items')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            $tenant = DB::table('tenants')
                ->where('id', $tenantId)
                ->first(['id', 'name']);

            if (! $tenant) {
                continue;
            }

            $shopId = DB::table('self_service_shops')->insertGetId([
                'tenant_id' => $tenant->id,
                'name' => 'Tienda '.$tenant->name,
                'description' => 'Catálogo publicado inicial.',
                'status' => 'active',
                'published_at' => now(),
                'meta' => json_encode([
                    'source' => 'self_service_shop_items_migration',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('self_service_shop_items')
                ->where('tenant_id', $tenant->id)
                ->whereNull('self_service_shop_id')
                ->update([
                    'self_service_shop_id' => $shopId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('self_service_shop_items', function (Blueprint $table) {
            $table->dropForeign('ss_shop_items_shop_fk');
            $table->dropIndex('ss_shop_items_tenant_shop_index');
            $table->dropColumn('self_service_shop_id');
        });

        Schema::dropIfExists('self_service_shops');
    }
};
