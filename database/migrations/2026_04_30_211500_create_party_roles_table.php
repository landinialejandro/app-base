<?php

// FILE: database/migrations/2026_04_30_211500_create_party_roles_table.php | V2

use App\Support\Catalogs\PartyCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_roles', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36)->index();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('role', 50);

            $table->timestamps();

            $table->unique(['tenant_id', 'party_id', 'role']);
            $table->index(['tenant_id', 'role']);
        });

        DB::table('parties')
            ->select(['id', 'tenant_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($parties) {
                foreach ($parties as $party) {
                    DB::table('party_roles')->insertOrIgnore([
                        'tenant_id' => $party->tenant_id,
                        'party_id' => $party->id,
                        'role' => PartyCatalog::ROLE_OTHER,
                        'created_at' => $party->created_at,
                        'updated_at' => $party->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_roles');
    }
};