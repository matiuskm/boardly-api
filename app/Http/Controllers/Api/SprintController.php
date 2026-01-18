<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSprintRequest;
use App\Http\Requests\UpdateSprintRequest;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\IssueScheduler;
use App\Services\NotificationService;
use App\Services\SprintLifecycleService;
use App\Support\ApiResponse;

class SprintController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $sprints = $project->sprints()
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(['sprints' => $sprints]);
    }

    public function store(StoreSprintRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $sprint = $project->sprints()->create($request->validated());

        return ApiResponse::success(['sprint' => $sprint], 201);
    }

    public function update(UpdateSprintRequest $request, Sprint $sprint)
    {
        $this->authorize('update', $sprint);

        $sprint->update($request->validated());

        return ApiResponse::success(['sprint' => $sprint->fresh()]);
    }

    public function destroy(Sprint $sprint)
    {
        $this->authorize('delete', $sprint);

        $sprint->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    public function start(Sprint $sprint, SprintLifecycleService $lifecycle, NotificationService $notifications)
    {
        $this->authorize('update', $sprint);

        $sprint = $lifecycle->start($sprint);
        $notifications->sprintStarted($sprint, request()->user());

        return ApiResponse::success(['sprint' => $sprint]);
    }

    public function complete(Sprint $sprint, SprintLifecycleService $lifecycle, IssueScheduler $scheduler, NotificationService $notifications)
    {
        $this->authorize('update', $sprint);

        $sprint = $lifecycle->complete($sprint);

        $issues = $sprint->issues()->get();
        foreach ($issues as $issue) {
            $scheduler->assignToBacklog($sprint->project, $issue);
        }

        $notifications->sprintCompleted($sprint, request()->user());

        return ApiResponse::success(['sprint' => $sprint]);
    }
}
