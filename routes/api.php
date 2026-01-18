<?php

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\BoardColumnController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\IssueCommentController;
use App\Http\Controllers\Api\IssueLabelController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\SprintController;
use App\Http\Controllers\Api\SprintIssueController;
use App\Http\Controllers\Api\BacklogController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\NotificationController;

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

    Route::get('/projects/{project}/sprints', [SprintController::class, 'index']);
    Route::post('/projects/{project}/sprints', [SprintController::class, 'store']);
    Route::patch('/sprints/{sprint}', [SprintController::class, 'update']);
    Route::delete('/sprints/{sprint}', [SprintController::class, 'destroy']);
    Route::post('/sprints/{sprint}/start', [SprintController::class, 'start']);
    Route::post('/sprints/{sprint}/complete', [SprintController::class, 'complete']);
    Route::post('/projects/{project}/backlog/reorder', [BacklogController::class, 'reorder']);
    Route::post('/sprints/{sprint}/issues/reorder', [SprintIssueController::class, 'reorder']);

    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
    Route::patch('/projects/{project}/members/{user}', [ProjectMemberController::class, 'update']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);

    Route::get('/boards/{board}/columns', [BoardColumnController::class, 'index']);
    Route::post('/boards/{board}/columns', [BoardColumnController::class, 'store']);
    Route::patch('/columns/{column}', [BoardColumnController::class, 'update']);
    Route::delete('/columns/{column}', [BoardColumnController::class, 'destroy']);

    Route::get('/boards/{board}/issues', [IssueController::class, 'index']);
    Route::post('/boards/{board}/issues', [IssueController::class, 'store']);
    Route::patch('/issues/{issue}', [IssueController::class, 'update']);
    Route::post('/issues/{issue}/move', [IssueController::class, 'move']);
    Route::post('/issues/{issue}/assign', [IssueController::class, 'assign']);
    Route::delete('/issues/{issue}', [IssueController::class, 'destroy']);

    Route::get('/workspaces/{workspace}/labels', [LabelController::class, 'index']);
    Route::post('/workspaces/{workspace}/labels', [LabelController::class, 'store']);
    Route::post('/issues/{issue}/labels', [IssueLabelController::class, 'store']);
    Route::delete('/issues/{issue}/labels/{label}', [IssueLabelController::class, 'destroy']);

    Route::get('/issues/{issue}/comments', [IssueCommentController::class, 'index']);
    Route::post('/issues/{issue}/comments', [IssueCommentController::class, 'store']);

    Route::get('/workspaces/{workspace}/activities', [ActivityController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
});
