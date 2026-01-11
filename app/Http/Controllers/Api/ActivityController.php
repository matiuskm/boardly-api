<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Workspace;
use App\Support\ApiResponse;

class ActivityController extends Controller
{
    public function index(Workspace $workspace)
    {
        $this->authorize('view', [Activity::class, $workspace]);

        $activities = Activity::where('workspace_id', $workspace->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(['activities' => $activities]);
    }
}
