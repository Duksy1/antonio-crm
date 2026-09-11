<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'type' => ActivityType::Task,
            'subject' => fake()->sentence(3),
            'notes' => null,
            'due_at' => null,
            'completed_at' => null,
        ];
    }

    public function type(ActivityType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function dueAt(mixed $moment): static
    {
        return $this->state(fn () => ['due_at' => $moment]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }

    public function system(): static
    {
        return $this->state(fn () => ['type' => ActivityType::System, 'completed_at' => now()]);
    }
}
