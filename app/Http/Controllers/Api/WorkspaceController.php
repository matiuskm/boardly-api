<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $workspaces = $request->user()->workspaces()->with('owner')->get();

        return ApiResponse::success(['workspaces' => $workspaces]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:workspaces,slug'],
        ]);

        $user = $request->user();
        $slug = $validated['slug'] ?? $this->makeSlug($validated['name']);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'owner_id' => $user->id,
        ]);

        $workspace->users()->attach($user->id, ['role' => 'owner']);

        return ApiResponse::success(['workspace' => $workspace->load('owner')], 201);
    }

    public function show(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        return ApiResponse::success(['workspace' => $workspace->load('owner')]);
    }

    protected function makeSlug(string $name): string
    {
        $base = Str::slug($name) ?: (string) Str::uuid();
        $slug = $base;
        $counter = 1;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
