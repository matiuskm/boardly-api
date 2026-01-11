<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Workspace;
use App\Support\ApiResponse;

class ProjectController extends Controller
{
    public function index(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $projects = $workspace->projects()->with('board')->get();

        return ApiResponse::success(['projects' => $projects]);
    }

    public function store(StoreProjectRequest $request, Workspace $workspace)
    {
        $this->authorize('update', $workspace);

        $project = $workspace->projects()->create($request->validated());

        return ApiResponse::success(['project' => $project], 201);
    }
}
