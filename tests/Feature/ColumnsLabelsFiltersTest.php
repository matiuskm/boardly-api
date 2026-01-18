<?php

use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createWorkspaceWithBoard(User $user): array
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
        ['name' => 'Doing', 'key' => 'doing', 'position' => 2],
        ['name' => 'Done', 'key' => 'done', 'position' => 3],
    ])->map(fn (array $data) => $board->columns()->create($data));

    return [$workspace, $board, $columns];
}

it('allows owners to manage columns and blocks members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    [$workspace, $board] = createWorkspaceWithBoard($owner);

    $workspace->members()->syncWithoutDetaching([
        $member->id => ['role' => 'member'],
    ]);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/boards/{$board->id}/columns", [
            'name' => 'QA',
            'key' => 'qa',
            'position' => 2,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.column.key', 'qa');

    $this->actingAs($member, 'sanctum')
        ->postJson("/api/boards/{$board->id}/columns", [
            'name' => 'Blocked',
        ])
        ->assertStatus(403);
});

it('allows owners to create labels and assign them to issues', function () {
    $owner = User::factory()->create();
    [$workspace, $board, $columns] = createWorkspaceWithBoard($owner);
    $todoColumn = $columns->firstWhere('key', 'todo');

    $labelResponse = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/workspaces/{$workspace->id}/labels", [
            'name' => 'Bug',
            'color' => '#ff0000',
        ])
        ->assertStatus(201);

    $labelId = $labelResponse->json('data.label.id');

    $issue = $board->issues()->create([
        'title' => 'Needs label',
        'column_id' => $todoColumn->id,
        'position' => 1,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->postJson("/api/issues/{$issue->id}/labels", [
            'label_ids' => [$labelId],
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.labels.0.id', $labelId);
});

it('filters issues by label and assignee', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    [$workspace, $board, $columns] = createWorkspaceWithBoard($owner);
    $todoColumn = $columns->firstWhere('key', 'todo');

    $workspace->members()->syncWithoutDetaching([
        $assignee->id => ['role' => 'member'],
    ]);

    $label = Label::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Urgent',
    ]);

    $matching = $board->issues()->create([
        'title' => 'Match',
        'column_id' => $todoColumn->id,
        'position' => 1,
        'assignee_id' => $assignee->id,
    ]);
    $matching->labels()->attach($label->id);

    $board->issues()->create([
        'title' => 'Other',
        'column_id' => $todoColumn->id,
        'position' => 2,
    ]);

    $response = $this->actingAs($owner, 'sanctum')
        ->getJson("/api/boards/{$board->id}/issues?label_id={$label->id}&assignee_id={$assignee->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.columns.0.issues.0.id', $matching->id)
        ->assertJsonCount(1, 'data.columns.0.issues');
});

it('dedupes issue moves when request_id is repeated', function () {
    $owner = User::factory()->create();
    [$workspace, $board, $columns] = createWorkspaceWithBoard($owner);
    $todoColumn = $columns->firstWhere('key', 'todo');
    $doingColumn = $columns->firstWhere('key', 'doing');

    $issue = $board->issues()->create([
        'title' => 'Move me',
        'column_id' => $todoColumn->id,
        'position' => 1,
    ]);

    $requestId = (string) Str::uuid();

    $this->actingAs($owner, 'sanctum')
        ->withHeaders(['X-Request-Id' => $requestId])
        ->postJson("/api/issues/{$issue->id}/move", [
            'to_column_id' => $doingColumn->id,
            'to_position' => 1,
        ])
        ->assertStatus(200);

    $this->actingAs($owner, 'sanctum')
        ->withHeaders(['X-Request-Id' => $requestId])
        ->postJson("/api/issues/{$issue->id}/move", [
            'to_column_id' => $doingColumn->id,
            'to_position' => 1,
        ])
        ->assertStatus(200);

    $activityCount = $workspace->activities()
        ->where('action', 'issue.moved')
        ->count();

    expect($activityCount)->toBe(1);
});
