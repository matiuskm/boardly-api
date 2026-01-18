<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Owner User',
                'password' => Hash::make('password'),
            ]
        );

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'demo-workspace'],
            [
                'name' => 'Demo Workspace',
                'owner_id' => $user->id,
            ]
        );

        $workspace->users()->syncWithoutDetaching([
            $user->id => ['role' => 'owner'],
        ]);

        $project = $workspace->projects()->create([
            'name' => 'Demo Project',
            'description' => 'Demo project description',
        ]);

        $board = $project->board()->create([
            'name' => 'Demo Board',
        ]);

        $columns = collect([
            ['name' => 'Todo', 'key' => 'todo', 'position' => 1],
            ['name' => 'Doing', 'key' => 'doing', 'position' => 2],
            ['name' => 'Done', 'key' => 'done', 'position' => 3],
        ])->map(function (array $data) use ($board) {
            return $board->columns()->create($data);
        });

        $todoColumn = $columns->firstWhere('key', 'todo');

        collect(range(1, 5))->each(function ($i) use ($board, $todoColumn) {
            $board->issues()->create([
                'title' => "Demo Issue {$i}",
                'description' => "Description for issue {$i}",
                'column_id' => $todoColumn?->id,
                'position' => $i,
            ]);
        });
    }
}
