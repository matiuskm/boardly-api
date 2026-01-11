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

        collect(range(1, 5))->each(function ($i) use ($board) {
            $board->issues()->create([
                'title' => "Demo Issue {$i}",
                'description' => "Description for issue {$i}",
                'status' => 'todo',
                'position' => $i,
            ]);
        });
    }
}
