<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        $kind = fake()->randomElement(['company', 'person']);

        if ($kind === 'company') {
            $name = fake()->company();

            return [
                'tenant_id' => Tenant::factory(),
                'kind' => 'company',
                'name' => $name,
                'display_name' => $name,
                'document_type' => 'CUIT',
                'document_number' => (string) fake()->unique()->numerify('30-########-#'),
                'tax_id' => (string) fake()->unique()->numerify('30-########-#'),
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
                'notes' => fake()->optional()->sentence(),
                'is_active' => true,
            ];
        }

        $name = fake()->name();

        return [
            'tenant_id' => Tenant::factory(),
            'kind' => 'person',
            'name' => $name,
            'display_name' => $name,
            'document_type' => 'DNI',
            'document_number' => (string) fake()->unique()->numberBetween(20000000, 45000000),
            'tax_id' => null,
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}