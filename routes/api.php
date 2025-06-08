<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\AuthController as APIAuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\GitHubRepositoryController;


Route::post('/login', [APIAuthController::class, 'login']);
Route::post('/register', [APIAuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/repository-info/{project}', [GitHubRepositoryController::class, 'fetch']);

});












































// Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
// Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);
    
//     Route::apiResource('/projects', ProjectController::class);
//     Route::post('/projects/{id}/repository', [RepositoryController::class, 'store']);
//     Route::get('/evaluations/{project_id}', [EvaluationController::class, 'show']);
//     Route::post('/evaluations/generate', [EvaluationController::class, 'generate']);
// });