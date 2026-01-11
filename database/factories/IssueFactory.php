<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Issue>
 */
class IssueFactory extends Factory
{
    protected $model = Issue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['todo', 'doing', 'done'];

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement($statuses),
            'position' => fake()->numberBetween(1, 100),
            'board_id' => Board::factory(),
            'assignee_id' => null,
        ];
    }

    public function withAssignee(): static
    {
        return $this->state(fn () => [
            'assignee_id' => User::factory(),
        ]);
    }
}
