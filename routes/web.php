<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| الصفحة الرئيسية (welcome)
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

/*
|--------------------------------------------------------------------------
| الضيوف (Guest): تسجيل الدخول/التسجيل
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

/*
|--------------------------------------------------------------------------
| المصدقون (Auth): لوحات/طلبات/مشاريع
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // تسجيل الخروج (POST فقط)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |-------------------- الطالب --------------------
    */
    // اسم الراوت المطلوب من AuthController: student.dashboard
    Route::view('/student/dashboard', 'student.studentDashboard')->name('student.dashboard');

    // إنشاء مشروع (نفس أسماء المجلدات الموجودة)
    Route::view('/projects/create', 'projects.create')->name('projects.create');

    /*
    |-------------------- المشرف --------------------
    */
    // لوحة المشرف العامة
    Route::view('/supervisor/dashboard', 'supervisor.supervisorDashboard')->name('supervisor.dashboard');

    // طلبات الإشراف المعلّقة — ربطناه باسم route الذي تستخدمه في التوجيه: supervisor.requests
    Route::view('/supervisor/requests', 'requests.pending')->name('supervisor.requests');

    // قائمة المشرفين (واجهة موجودة باسم requests/supervisors.blade.php)
    Route::view('/supervisors', 'requests.supervisors')->name('supervisors.list');

    // مشاريع المشرف
    Route::view('/supervisor/projects', 'supervisor.projects.index')->name('supervisor.projects.index');

    // المشاريع المقبولة لدى المشرف
    Route::view('/supervisor/accepted-projects', 'supervisor.accepted-projects')->name('supervisor.accepted-projects');

    // واجهات فحص الانتحال (Plagiarism) الموجودة في المجلد
    Route::view('/supervisor/plagiarism/select', 'supervisor.plagiarism_select')->name('supervisor.plagiarism.form');
    Route::view('/supervisor/plagiarism/result', 'supervisor.plagiarism-result')->name('supervisor.plagiarism.result');

    /*
    |-------------------- الأدمن --------------------
    */
    // لا يوجد admin/panel.blade.php في الأرشيف الحالي
    // إن كان AuthController يوجّه إلى admin.panel، إمّا:
    //   1) تضيف هذا العرض لاحقًا resources/views/admin/panel.blade.php
    //   2) أو تغيّر التوجيه في AuthController لدور الأدمن مؤقتًا إلى welcome أو صفحة أخرى موجودة
    // مؤقتًا نربطه بصفحة welcome حتى لا يحدث خطأ:
    Route::get('/admin/panel', fn() => view('welcome'))->name('admin.panel');
});