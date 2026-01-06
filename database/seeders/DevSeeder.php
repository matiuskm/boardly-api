<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevSeeder extends Seeder
{
    use WithoutModelEvents;

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
    }
}
