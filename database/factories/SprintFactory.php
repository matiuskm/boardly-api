<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Sprint>
 */
class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Sprint '.fake()->word(),
            'goal' => fake()->sentence(),
            'starts_at' => null,
            'ends_at' => null,
            'status' => 'planned',
        ];
    }
}
