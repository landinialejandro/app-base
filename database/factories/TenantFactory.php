<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $company = fake()->unique()->company();

        return [
            'id' => (string) Str::uuid(),
            'name' => $company,
            'slug' => Str::slug($company) . '-' . fake()->unique()->numberBetween(100, 999),
            'settings' => [
                'timezone' => 'America/Argentina/Salta',
                'currency' => 'ARS',
            ],
        ];
    }
}