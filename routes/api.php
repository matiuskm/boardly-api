<?php

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceController;

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
});
