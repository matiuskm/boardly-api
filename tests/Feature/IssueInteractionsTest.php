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
    $columns = collect([
        ['name' => 'Todo', 'key' => 'todo', 'position' => 1],
        ['name' => 'Doing', 'key' => 'doing', 'position' => 2],
        ['name' => 'Done', 'key' => 'done', 'position' => 3],
    ])->map(fn (array $data) => $board->columns()->create($data));

    return [$workspace, $project, $board, $columns];
}

it('moves issues within the same column and normalizes positions', function () {
    $user = User::factory()->create();
    [, , $board, $columns] = createProjectWithBoardFor($user);
    $todoColumn = $columns->firstWhere('key', 'todo');

    $issues = $board->issues()->createMany([
        ['title' => 'One', 'column_id' => $todoColumn->id, 'position' => 1],
        ['title' => 'Two', 'column_id' => $todoColumn->id, 'position' => 2],
        ['title' => 'Three', 'column_id' => $todoColumn->id, 'position' => 3],
        ['title' => 'Four', 'column_id' => $todoColumn->id, 'position' => 4],
        ['title' => 'Five', 'column_id' => $todoColumn->id, 'position' => 5],
    ]);

    $moveTarget = $issues[3];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/issues/{$moveTarget->id}/move", [
            'to_column_id' => $todoColumn->id,
            'to_position' => 2,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.issue.position', 2);

    $positions = $board->issues()
        ->where('column_id', $todoColumn->id)
        ->orderBy('position')
        ->pluck('position')
        ->all();

    expect($positions)->toBe([1, 2, 3, 4, 5]);
});

it('moves issues across columns and closes gaps', function () {
    $user = User::factory()->create();
    [, , $board, $columns] = createProjectWithBoardFor($user);
    $todoColumn = $columns->firstWhere('key', 'todo');
    $doingColumn = $columns->firstWhere('key', 'doing');

    $todo = $board->issues()->createMany([
        ['title' => 'Todo 1', 'column_id' => $todoColumn->id, 'position' => 1],
        ['title' => 'Todo 2', 'column_id' => $todoColumn->id, 'position' => 2],
        ['title' => 'Todo 3', 'column_id' => $todoColumn->id, 'position' => 3],
    ]);

    $board->issues()->createMany([
        ['title' => 'Doing 1', 'column_id' => $doingColumn->id, 'position' => 1],
        ['title' => 'Doing 2', 'column_id' => $doingColumn->id, 'position' => 2],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/issues/{$todo[1]->id}/move", [
            'to_column_id' => $doingColumn->id,
            'to_position' => 1,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.issue.column_id', $doingColumn->id)
        ->assertJsonPath('data.issue.position', 1);

    $todoPositions = $board->issues()
        ->where('column_id', $todoColumn->id)
        ->orderBy('position')
        ->pluck('position')
        ->all();

    $doingPositions = $board->issues()
        ->where('column_id', $doingColumn->id)
        ->orderBy('position')
        ->pluck('position')
        ->all();

    expect($todoPositions)->toBe([1, 2]);
    expect($doingPositions)->toBe([1, 2, 3]);
});

it('clamps moves to the end of the target column', function () {
    $user = User::factory()->create();
    [, , $board, $columns] = createProjectWithBoardFor($user);
    $todoColumn = $columns->firstWhere('key', 'todo');
    $doingColumn = $columns->firstWhere('key', 'doing');

    $issue = $board->issues()->create([
        'title' => 'Todo 1',
        'column_id' => $todoColumn->id,
        'position' => 1,
    ]);

    $board->issues()->createMany([
        ['title' => 'Doing 1', 'column_id' => $doingColumn->id, 'position' => 1],
        ['title' => 'Doing 2', 'column_id' => $doingColumn->id, 'position' => 2],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/issues/{$issue->id}/move", [
            'to_column_id' => $doingColumn->id,
            'to_position' => 99,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.issue.column_id', $doingColumn->id)
        ->assertJsonPath('data.issue.position', 3);
});

it('logs activity for comments and blocks non-members', function () {
    $user = User::factory()->create();
    $outsider = User::factory()->create();
    [$workspace, , $board, $columns] = createProjectWithBoardFor($user);
    $todoColumn = $columns->firstWhere('key', 'todo');

    $issue = $board->issues()->create([
        'title' => 'Commented issue',
        'column_id' => $todoColumn->id,
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

    [$workspace, , $board, $columns] = createProjectWithBoardFor($owner);
    $todoColumn = $columns->firstWhere('key', 'todo');
    $doingColumn = $columns->firstWhere('key', 'doing');

    $workspace->members()->syncWithoutDetaching([
        $member->id => ['role' => 'member'],
        $assignee->id => ['role' => 'member'],
    ]);

    $issueResponse = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Assign me',
            'column_id' => $todoColumn->id,
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
            'to_column_id' => $doingColumn->id,
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
    [$workspace, , $board, $columns] = createProjectWithBoardFor($user);
    $todoColumn = $columns->firstWhere('key', 'todo');

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Activity seed',
            'column_id' => $todoColumn->id,
        ])
        ->assertStatus(201);

    $this->actingAs($outsider, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}/activities")
        ->assertStatus(403);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/workspaces/{$workspace->id}/activities")
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['activities' => ['data', 'next_cursor']],
            'meta' => ['request_id'],
        ]);
});
