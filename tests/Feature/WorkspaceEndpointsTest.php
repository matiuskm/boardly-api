<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only workspaces the user belongs to', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $memberWorkspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $memberWorkspace->users()->attach($user->id, ['role' => 'owner']);

    $otherWorkspace = Workspace::factory()->create(['owner_id' => $otherUser->id]);
    $otherWorkspace->users()->attach($otherUser->id, ['role' => 'owner']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/workspaces');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.workspaces')
        ->assertJsonPath('data.workspaces.0.id', $memberWorkspace->id)
        ->assertJsonStructure([
            'data' => ['workspaces'],
            'meta' => ['request_id'],
        ]);
});

it('creates a workspace and attaches owner role', function () {
    $user = User::factory()->create();

    $payload = ['name' => 'My Team'];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/workspaces', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['workspace' => ['id', 'name', 'slug', 'owner_id']],
            'meta' => ['request_id'],
        ]);

    $workspaceId = $response->json('data.workspace.id');
    $this->assertDatabaseHas('workspaces', [
        'id' => $workspaceId,
        'owner_id' => $user->id,
    ]);
    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $workspaceId,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

it('prevents viewing workspaces the user is not a member of', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $workspace = Workspace::factory()->create(['owner_id' => $otherUser->id]);
    $workspace->users()->attach($otherUser->id, ['role' => 'owner']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(403);
});

it('allows viewing workspace details for members', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.workspace.id', $workspace->id)
        ->assertJsonStructure([
            'data' => ['workspace'],
            'meta' => ['request_id'],
        ]);

    expect($response->headers->get('X-Request-Id'))->not->toBeNull();
});
