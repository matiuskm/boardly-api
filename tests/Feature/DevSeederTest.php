<?php

use Database\Seeders\DevSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds a demo owner and workspace with owner role', function () {
    $this->seed(DevSeeder::class);

    $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
    $this->assertDatabaseHas('workspaces', ['slug' => 'demo-workspace']);

    $workspaceId = DB::table('workspaces')->where('slug', 'demo-workspace')->value('id');
    $userId = DB::table('users')->where('email', 'owner@example.com')->value('id');

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspaceId,
        'user_id' => $userId,
        'role' => 'owner',
    ]);
});
