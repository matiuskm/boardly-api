<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardColumn;
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
        $board = Board::factory();

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'position' => fake()->numberBetween(1, 100),
            'board_id' => $board,
            'column_id' => BoardColumn::factory()->for($board),
            'assignee_id' => null,
            'priority' => fake()->numberBetween(1, 5),
            'due_at' => null,
        ];
    }

    public function withAssignee(): static
    {
        return $this->state(fn () => [
            'assignee_id' => User::factory(),
        ]);
    }
}
