<?php

namespace Database\Factories;

use App\Models\Task;
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
        $urgent = fake()->boolean();
        $important = fake()->boolean();

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->boolean(60) ? fake()->paragraph() : null,
            'is_urgent' => $urgent,
            'is_important' => $important,
            'quadrant' => Task::quadrantFor($urgent, $important),
            'status' => Task::STATUS_PENDING,
            'due_at' => fake()->boolean(70) ? fake()->dateTimeBetween('now', '+10 days') : null,
            'estimated_minutes' => fake()->boolean(70) ? fake()->numberBetween(10, 120) : null,
        ];
    }
}
