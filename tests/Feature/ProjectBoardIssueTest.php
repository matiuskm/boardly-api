<?php

use App\Models\Board;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWorkspaceFor(User $user): Workspace
{
    $workspace = Workspace::factory()->create([
        'owner_id' => $user->id,
    ]);

    $workspace->members()->syncWithoutDetaching([
        $user->id => ['role' => 'owner'],
    ]);

    return $workspace;
}

function createProjectWithBoard(User $user): array
{
    $workspace = createWorkspaceFor($user);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);
    $board = $project->board()->create(['name' => 'Main Board']);

    return [$workspace, $project, $board];
}

it('lists projects for workspace members and scopes by workspace', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceFor($user);
    $otherWorkspace = createWorkspaceFor(User::factory()->create());

    Project::factory()->count(2)->create(['workspace_id' => $workspace->id]);
    Project::factory()->create(['workspace_id' => $otherWorkspace->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}/projects");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data.projects');
});

it('blocks project listing for non-members', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceFor(User::factory()->create());

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}/projects");

    $response->assertStatus(403);
});

it('allows owners/admins to create projects in a workspace', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceFor($user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/workspaces/{$workspace->id}/projects", [
            'name' => 'New Project',
            'description' => 'Desc',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['project' => ['id', 'name', 'workspace_id']],
            'meta' => ['request_id'],
        ]);

    $this->assertDatabaseHas('projects', [
        'name' => 'New Project',
        'workspace_id' => $workspace->id,
    ]);
});

it('creates only one board per project and returns existing on repeat', function () {
    $user = User::factory()->create();
    [, $project] = createProjectWithBoard($user);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/projects/{$project->id}/board", ['name' => 'Another Name']);

    $response->assertStatus(200)
        ->assertJsonPath('data.board.id', $project->board->id);
});

it('blocks board access for non-members', function () {
    $user = User::factory()->create();
    [, $project] = createProjectWithBoard(User::factory()->create());

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}/board");

    $response->assertStatus(403);
});

it('lists issues ordered by status then position', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoard($user);

    $board->issues()->createMany([
        ['title' => 'Todo 2', 'status' => 'todo', 'position' => 2],
        ['title' => 'Todo 1', 'status' => 'todo', 'position' => 1],
        ['title' => 'Doing 1', 'status' => 'doing', 'position' => 1],
        ['title' => 'Done 1', 'status' => 'done', 'position' => 1],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/boards/{$board->id}/issues");

    $response->assertStatus(200)
        ->assertJsonPath('data.issues.0.title', 'Doing 1')
        ->assertJsonPath('data.issues.1.title', 'Done 1')
        ->assertJsonPath('data.issues.2.title', 'Todo 1')
        ->assertJsonPath('data.issues.3.title', 'Todo 2');
});

it('assigns next position within status when creating issues', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoard($user);

    $board->issues()->createMany([
        ['title' => 'Todo 1', 'status' => 'todo', 'position' => 1],
        ['title' => 'Todo 2', 'status' => 'todo', 'position' => 2],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Todo 3',
            'status' => 'todo',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.issue.position', 3);
});

it('defaults status to todo and increments within that status only', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoard($user);

    $board->issues()->createMany([
        ['title' => 'Todo 1', 'status' => 'todo', 'position' => 1],
        ['title' => 'Doing 1', 'status' => 'doing', 'position' => 1],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Todo 2',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.issue.status', 'todo')
        ->assertJsonPath('data.issue.position', 2);
});

it('assigns next position for a specified status independently of other statuses', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoard($user);

    $board->issues()->createMany([
        ['title' => 'Todo 1', 'status' => 'todo', 'position' => 1],
        ['title' => 'Doing 1', 'status' => 'doing', 'position' => 1],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Doing 2',
            'status' => 'doing',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.issue.status', 'doing')
        ->assertJsonPath('data.issue.position', 2);
});

it('repositions issues when status changes', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoard($user);

    $issue = $board->issues()->create([
        'title' => 'Move me',
        'status' => 'todo',
        'position' => 1,
    ]);

    $board->issues()->createMany([
        ['title' => 'Doing 1', 'status' => 'doing', 'position' => 1],
        ['title' => 'Doing 2', 'status' => 'doing', 'position' => 2],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->patchJson("/api/issues/{$issue->id}", [
            'status' => 'doing',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.issue.status', 'doing')
        ->assertJsonPath('data.issue.position', 3);
});

it('deletes issues', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoard($user);

    $issue = $board->issues()->create([
        'title' => 'Delete me',
        'status' => 'todo',
        'position' => 1,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/issues/{$issue->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
});
