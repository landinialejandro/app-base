<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'project_id' => null,
            'party_id' => null,
            'assigned_user_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'done', 'cancelled']),
            'due_date' => fake()->optional(0.8)->dateTimeBetween('-10 days', '+20 days')->format('Y-m-d'),
        ];
    }

    public function withProject(Project $project): static
    {
        return $this->state(fn () => [
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
        ]);
    }

    public function withParty(Party $party): static
    {
        return $this->state(fn () => [
            'tenant_id' => $party->tenant_id,
            'party_id' => $party->id,
        ]);
    }

    public function assignedTo(User $user, string $tenantId): static
    {
        return $this->state(fn () => [
            'tenant_id' => $tenantId,
            'assigned_user_id' => $user->id,
        ]);
    }
}