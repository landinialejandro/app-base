<?php

// FILE: database/seeders/Modules/BaseModuleSeeder.php | V2

namespace Database\Seeders\Modules;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

abstract class BaseModuleSeeder extends Seeder
{
    protected array $context = [];

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    abstract public function run(): void;

    protected function hasDependency(string $key): bool
    {
        return array_key_exists($key, $this->context);
    }

    protected function getDependency(string $key): mixed
    {
        return $this->context[$key] ?? null;
    }

    protected function createIfNotExists(string $model, array $conditions, array $data): mixed
    {
        return $model::firstOrCreate($conditions, $data);
    }

    protected function createWithUniqueKey(string $table, array $conditions, array $data): void
    {
        DB::table($table)->updateOrInsert($conditions, $data);
    }

    protected function ensureTenantScope(string $tenantId, array $data): array
    {
        return array_merge(['tenant_id' => $tenantId], $data);
    }
}
