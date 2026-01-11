<?php

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\IssueController;

Route::get('/user', function (Request $request) {
    return ApiResponse::success(['user' => $request->user()]);
})->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function () {
    Route::get('/me', [AuthController::class, 'me']);
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);

    Route::get('/workspaces/{workspace}/projects', [ProjectController::class, 'index']);
    Route::post('/workspaces/{workspace}/projects', [ProjectController::class, 'store']);

    Route::get('/projects/{project}/board', [BoardController::class, 'show']);
    Route::post('/projects/{project}/board', [BoardController::class, 'store']);

    Route::get('/boards/{board}/issues', [IssueController::class, 'index']);
    Route::post('/boards/{board}/issues', [IssueController::class, 'store']);
    Route::patch('/issues/{issue}', [IssueController::class, 'update']);
    Route::delete('/issues/{issue}', [IssueController::class, 'destroy']);
});
