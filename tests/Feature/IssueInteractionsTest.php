<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWorkspaceWithOwner(User $user): Workspace
{
    $workspace = Workspace::factory()->create([
        'owner_id' => $user->id,
    ]);

    $workspace->members()->syncWithoutDetaching([
        $user->id => ['role' => 'owner'],
    ]);

    return $workspace;
}

function createProjectWithBoardFor(User $user): array
{
    $workspace = createWorkspaceWithOwner($user);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);
    $board = $project->board()->create(['name' => 'Main Board']);

    return [$workspace, $project, $board];
}

it('moves issues within the same column and normalizes positions', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoardFor($user);

    $issues = $board->issues()->createMany([
        ['title' => 'One', 'status' => 'todo', 'position' => 1],
        ['title' => 'Two', 'status' => 'todo', 'position' => 2],
        ['title' => 'Three', 'status' => 'todo', 'position' => 3],
        ['title' => 'Four', 'status' => 'todo', 'position' => 4],
        ['title' => 'Five', 'status' => 'todo', 'position' => 5],
    ]);

    $moveTarget = $issues[3];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/issues/{$moveTarget->id}/move", [
            'to_status' => 'todo',
            'to_position' => 2,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.issue.position', 2);

    $positions = $board->issues()
        ->where('status', 'todo')
        ->orderBy('position')
        ->pluck('position')
        ->all();

    expect($positions)->toBe([1, 2, 3, 4, 5]);
});

it('moves issues across columns and closes gaps', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoardFor($user);

    $todo = $board->issues()->createMany([
        ['title' => 'Todo 1', 'status' => 'todo', 'position' => 1],
        ['title' => 'Todo 2', 'status' => 'todo', 'position' => 2],
        ['title' => 'Todo 3', 'status' => 'todo', 'position' => 3],
    ]);

    $board->issues()->createMany([
        ['title' => 'Doing 1', 'status' => 'doing', 'position' => 1],
        ['title' => 'Doing 2', 'status' => 'doing', 'position' => 2],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/issues/{$todo[1]->id}/move", [
            'to_status' => 'doing',
            'to_position' => 1,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.issue.status', 'doing')
        ->assertJsonPath('data.issue.position', 1);

    $todoPositions = $board->issues()
        ->where('status', 'todo')
        ->orderBy('position')
        ->pluck('position')
        ->all();

    $doingPositions = $board->issues()
        ->where('status', 'doing')
        ->orderBy('position')
        ->pluck('position')
        ->all();

    expect($todoPositions)->toBe([1, 2]);
    expect($doingPositions)->toBe([1, 2, 3]);
});

it('clamps moves to the end of the target column', function () {
    $user = User::factory()->create();
    [, , $board] = createProjectWithBoardFor($user);

    $issue = $board->issues()->create([
        'title' => 'Todo 1',
        'status' => 'todo',
        'position' => 1,
    ]);

    $board->issues()->createMany([
        ['title' => 'Doing 1', 'status' => 'doing', 'position' => 1],
        ['title' => 'Doing 2', 'status' => 'doing', 'position' => 2],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/issues/{$issue->id}/move", [
            'to_status' => 'doing',
            'to_position' => 99,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.issue.status', 'doing')
        ->assertJsonPath('data.issue.position', 3);
});

it('logs activity for comments and blocks non-members', function () {
    $user = User::factory()->create();
    $outsider = User::factory()->create();
    [$workspace, , $board] = createProjectWithBoardFor($user);

    $issue = $board->issues()->create([
        'title' => 'Commented issue',
        'status' => 'todo',
        'position' => 1,
    ]);

    $this->actingAs($outsider, 'sanctum')
        ->postJson("/api/issues/{$issue->id}/comments", ['body' => 'Nope'])
        ->assertStatus(403);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/issues/{$issue->id}/comments", ['body' => 'Looks good']);

    $response->assertStatus(201)
        ->assertJsonPath('data.comment.body', 'Looks good');

    $activity = $workspace->activities()->latest('created_at')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->action)->toBe('issue.commented');
});

it('requires admins to assign and records activities for key actions', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $assignee = User::factory()->create();

    [$workspace, , $board] = createProjectWithBoardFor($owner);

    $workspace->members()->syncWithoutDetaching([
        $member->id => ['role' => 'member'],
        $assignee->id => ['role' => 'member'],
    ]);

    $issueResponse = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Assign me',
            'status' => 'todo',
        ]);

    $issueId = $issueResponse->json('data.issue.id');

    $this->actingAs($member, 'sanctum')
        ->postJson("/api/issues/{$issueId}/assign", [
            'assignee_id' => $assignee->id,
        ])
        ->assertStatus(403);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/issues/{$issueId}/assign", [
            'assignee_id' => $assignee->id,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.issue.assignee_id', $assignee->id);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/issues/{$issueId}/move", [
            'to_status' => 'doing',
            'to_position' => 1,
        ])
        ->assertStatus(200);

    $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/issues/{$issueId}")
        ->assertStatus(200);

    $actions = $workspace->activities()
        ->whereIn('action', ['issue.created', 'issue.assigned', 'issue.moved', 'issue.deleted'])
        ->pluck('action')
        ->all();

    expect($actions)->toContain('issue.created')
        ->toContain('issue.assigned')
        ->toContain('issue.moved')
        ->toContain('issue.deleted');
});

it('lists activities only for workspace members', function () {
    $user = User::factory()->create();
    $outsider = User::factory()->create();
    [$workspace, , $board] = createProjectWithBoardFor($user);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Activity seed',
            'status' => 'todo',
        ])
        ->assertStatus(201);

    $this->actingAs($outsider, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}/activities")
        ->assertStatus(403);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}/activities")
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['activities' => ['data', 'current_page']],
            'meta' => ['request_id'],
        ]);
});
