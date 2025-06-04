<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::get('/login', function () {
    return view('login'); // login.blade.php
})->name('login');

Route::post('/login', [AuthController::class, 'loginWeb']);

Route::get('/register', function () {
    return view('register');
})->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register');


// Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
// Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);
    
//     Route::apiResource('/projects', ProjectController::class);
//     Route::post('/projects/{id}/repository', [RepositoryController::class, 'store']);
//     Route::get('/evaluations/{project_id}', [EvaluationController::class, 'show']);
//     Route::post('/evaluations/generate', [EvaluationController::class, 'generate']);
// });

