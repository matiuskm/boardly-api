<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabelRequest;
use App\Models\Label;
use App\Models\Workspace;
use App\Support\ApiResponse;

class LabelController extends Controller
{
    public function index(Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        $labels = $workspace->labels()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(['labels' => $labels]);
    }

    public function store(StoreLabelRequest $request, Workspace $workspace)
    {
        $this->authorize('create', [Label::class, $workspace]);

        $label = $workspace->labels()->create($request->validated());

        return ApiResponse::success(['label' => $label], 201);
    }
}
