<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\ProjectAccess;

class ProjectMemberController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $members = $project->members()
            ->withPivot('role')
            ->get();

        return ApiResponse::success(['members' => $members]);
    }

    public function store(StoreProjectMemberRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validated();
        $user = User::findOrFail($data['user_id']);

        if (! ProjectAccess::canViewProject($user, $project) && ! $project->workspace->members()->where('user_id', $user->id)->exists()) {
            return ApiResponse::error('User must be a workspace member.', 'not_member', 422);
        }

        $project->members()->syncWithoutDetaching([
            $user->id => ['role' => $data['role']],
        ]);

        return ApiResponse::success(['members' => $project->members()->get()]);
    }

    public function update(UpdateProjectMemberRequest $request, Project $project, User $user)
    {
        $this->authorize('update', $project);

        $project->members()->updateExistingPivot($user->id, [
            'role' => $request->validated()['role'],
        ]);

        return ApiResponse::success(['members' => $project->members()->get()]);
    }

    public function destroy(Project $project, User $user)
    {
        $this->authorize('update', $project);

        $project->members()->detach($user->id);

        return ApiResponse::success(['deleted' => true]);
    }
}
