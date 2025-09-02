<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;        // ✅ مهم لـ Auth
use Illuminate\Http\Request;               // ✅ لتمرير $request للّوج آوت

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PlagiarismCheckController;
use App\Http\Controllers\SupervisorRequestController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

Route::get('/student/dashboard', function () {
    return view('student.studentDashboard');
})->name('dashboard.student');

Route::get('/supervisor/dashboard', function () {
    return view('supervisor.supervisorDashboard');
})->name('dashboard.supervisor');

// ✅ Logout (جلسات الويب) + حماية auth + CSRF (POST)
Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// إنشاء مشروع
Route::middleware(['auth',\App\Http\Middleware\BlockUnverifiedStudents::class])->group(function () {
    Route::get('/student/dashboard', fn() => view('student.studentDashboard'))->name('dashboard.student');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
});

Route::middleware(['auth'])->group(function () {
    // الطالب → عرض المشرفين وإرسال طلب إشراف
    Route::get('/supervisors', [SupervisorRequestController::class, 'indexForStudent'])->name('supervisors.list');
    Route::post('/supervisors/request/{id}', [SupervisorRequestController::class, 'sendRequest'])->name('supervisors.request');

    // المشرف → استعراض/الرد على طلبات الإشراف
    Route::get('/supervisor/requests', [SupervisorRequestController::class, 'indexForSupervisor'])->name('supervisor.requests');
    Route::post('/supervisor/requests/{id}/{action}', [SupervisorRequestController::class, 'respond'])->name('supervisor.request.respond');

    // مشاريع مقبولة لدى المشرف
    Route::get('/supervisor/accepted-projects', [ProjectController::class, 'acceptedProjects'])->name('supervisor.accepted-projects');

    // تحليل السونار
    Route::post('/projects/{id}/analyze', [ProjectController::class, 'analyze'])->name('projects.analyze');

    // ✅ تصحيح: ربط مسار فحص السرقة بالكونترولر الصحيح
    Route::get('/projects/{id}/plagiarism', [PlagiarismCheckController::class, 'plagiarism'])->name('projects.plagiarism');

    Route::get('/supervisor/plagiarism-check/{project1}', [PlagiarismCheckController::class, 'plagiarism'])->name('projects.plagiarism.form');
    Route::post('/supervisor/plagiarism-check', [PlagiarismCheckController::class, 'checkPlagiarism'])->name('projects.plagiarism.check');
    Route::get('/supervisor/plagiarism-report/{id}', [PlagiarismCheckController::class, 'viewPlagiarismReport'])->name('projects.plagiarism.report');

    Route::get('/projects/{id}/evaluate', [ProjectController::class, 'evaluate'])->name('projects.evaluate');
});
