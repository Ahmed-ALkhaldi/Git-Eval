<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SupervisorRequestController;


Route::get('/login', [AuthController::class, 'showLoginForm'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::get('/student/dashboard', function () {
    return view('student.studentDashboard');
})->name('dashboard.student');

Route::get('/supervisor/dashboard', function () {
    return view('supervisor.supervisorDashboard');
})->name('dashboard.supervisor');
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');


Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
// Route::get('/supervisor/projects', [ProjectController::class, 'supervisorIndex'])->middleware('auth')->name('supervisor.projects');
// Route::post('/supervisor/projects/approve/{id}', [ProjectController::class, 'approve'])->middleware('auth')->name('supervisor.approve');


Route::middleware(['auth'])->group(function () {
    Route::get('/supervisors', [SupervisorRequestController::class, 'indexForStudent'])->name('supervisors.list');
    Route::post('/supervisors/request/{id}', [SupervisorRequestController::class, 'sendRequest'])->name('supervisors.request');

    Route::get('/supervisor/requests', [SupervisorRequestController::class, 'indexForSupervisor'])->name('supervisor.requests');
    Route::post('/supervisor/requests/{id}/{action}', [SupervisorRequestController::class, 'respond'])->name('supervisor.request.respond');
});












































// Route::get('/login', function () {
//     return view('login'); // login.blade.php
// })->name('login');

// Route::post('/login', [AuthController::class, 'loginWeb']);

// Route::get('/register', function () {
//     return view('register');
// })->name('register');

// Route::post('/register', [AuthController::class, 'register'])->name('register');


// Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
// Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);
    
//     Route::apiResource('/projects', ProjectController::class);
//     Route::post('/projects/{id}/repository', [RepositoryController::class, 'store']);
//     Route::get('/evaluations/{project_id}', [EvaluationController::class, 'show']);
//     Route::post('/evaluations/generate', [EvaluationController::class, 'generate']);
// });

