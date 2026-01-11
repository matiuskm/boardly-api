<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Board>
 */
class BoardFactory extends Factory
{
    protected $model = Board::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Board - '.fake()->word(),
            'project_id' => Project::factory(),
        ];
    }
}
