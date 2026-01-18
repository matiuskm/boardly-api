<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\IssueScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createProjectWithBoardAndColumns(User $user): array
{
    $workspace = Workspace::factory()->create([
        'owner_id' => $user->id,
    ]);

    $workspace->members()->syncWithoutDetaching([
        $user->id => ['role' => 'owner'],
    ]);

    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
    ]);

    $board = $project->board()->create(['name' => 'Main Board']);
    $columns = collect([
        ['name' => 'Todo', 'key' => 'todo', 'position' => 1],
        ['name' => 'Done', 'key' => 'done', 'position' => 2],
    ])->map(fn (array $data) => $board->columns()->create($data));

    return [$workspace, $project, $board, $columns];
}

it('starts a sprint and completes any active sprint in the project', function () {
    $user = User::factory()->create();
    [, $project] = createProjectWithBoardAndColumns($user);

    $active = $project->sprints()->create([
        'name' => 'Active Sprint',
        'status' => 'active',
    ]);

    $newSprint = $project->sprints()->create([
        'name' => 'New Sprint',
        'status' => 'planned',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/sprints/{$newSprint->id}/start")
        ->assertStatus(200)
        ->assertJsonPath('data.sprint.status', 'active');

    $active->refresh();
    expect($active->status)->toBe('completed');
});

it('enforces column wip limits on issue creation', function () {
    $user = User::factory()->create();
    [, , $board, $columns] = createProjectWithBoardAndColumns($user);
    $todoColumn = $columns->firstWhere('key', 'todo');
    $todoColumn->update(['wip_limit' => 1]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'First',
            'column_id' => $todoColumn->id,
        ])
        ->assertStatus(201);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/boards/{$board->id}/issues", [
            'title' => 'Second',
            'column_id' => $todoColumn->id,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'wip_limit_reached');
});

it('creates notifications for issue assignment', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    [, , $board, $columns] = createProjectWithBoardAndColumns($owner);
    $todoColumn = $columns->firstWhere('key', 'todo');

    $board->project->members()->syncWithoutDetaching([
        $assignee->id => ['role' => 'contributor'],
    ]);

    $issue = $board->issues()->create([
        'title' => 'Assign me',
        'column_id' => $todoColumn->id,
        'position' => 1,
    ]);

    app(IssueScheduler::class)->assignToBacklog($board->project, $issue);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/issues/{$issue->id}/assign", [
            'assignee_id' => $assignee->id,
        ])
        ->assertStatus(200);

    $notification = $assignee->notifications()->first();
    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('issue.assigned');
});
