<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminSupervisorController;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Project;
use App\Models\TeamInvitation;

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
    |
    | نمرّر $supervisors و $students إلى studentDashboard
    | ليشتغل الـ popup (Add Project) بدون صفحة إنشاء منفصلة
    */
    Route::get('/student/dashboard', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->loadMissing('student');
        abort_unless(optional($user)->student, 403, 'Student profile not found.');
        $studentId = $user->student->id;

        // supervisors
        $supervisors = Supervisor::with('user')->get()->map(function ($s) {
            return (object) [
                'id'    => $s->id,
                'name'  => $s->user->name ?? trim(($s->first_name ?? '').' '.($s->last_name ?? '')),
                'email' => $s->user->email ?? '',
            ];
        });

        // students (exclude current)
        $students = Student::with('user')
            ->whereHas('user')
            ->where('user_id', '!=', $user->id)
            ->get()
            ->map(function ($st) {
                return (object) [
                    'id'             => $st->id,
                    'name'           => $st->user->name ?? trim(($st->first_name ?? '').' '.($st->last_name ?? '')),
                    'university_num' => $st->university_num,
                ];
            });

        // projects (owner or team member)
        $projects = Project::with(['owner.user', 'supervisor.user', 'students.user'])
            ->where('owner_student_id', $studentId)
            ->orWhereHas('students', function ($q) use ($studentId) {
                $q->where('students.id', $studentId);
            })
            ->orderByDesc('id')
            ->get();

        // joining requests for this student (pending only)
        $joiningRequests = TeamInvitation::with([
            'project.owner.user',
            'project.supervisor.user',
            'invitedBy', // المرسل (User)
        ])
        ->pending()
        ->where('to_student_id', $studentId) // بدل student_id
        ->latest()
        ->get();

        return view('student.studentDashboard', compact('supervisors', 'students', 'projects', 'joiningRequests'));
    })->name('student.dashboard');


    // حفظ مشروع جديد (يُستخدم من داخل الـpopup في studentDashboard)
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    // عرض التقرير
    Route::get('/projects/{project}/report', [ProjectController::class, 'report'])->name('projects.report');

    // حذف مشروع
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');









    /*
    |-------------------- المشرف --------------------
    */
    // لوحة المشرف العامة
    Route::view('/supervisor/dashboard', 'supervisor.supervisorDashboard')->name('supervisor.dashboard');

    // طلبات الإشراف المعلّقة
    Route::view('/supervisor/requests', 'requests.pending')->name('supervisor.requests');

    // قائمة المشرفين
    Route::view('/supervisors', 'requests.supervisors')->name('supervisors.list');

    // مشاريع المشرف
    Route::view('/supervisor/projects', 'supervisor.projects.index')->name('supervisor.projects.index');

    // المشاريع المقبولة لدى المشرف
    Route::view('/supervisor/accepted-projects', 'supervisor.accepted-projects')->name('supervisor.accepted-projects');

    // واجهات فحص الانتحال (Plagiarism)
    Route::view('/supervisor/plagiarism/select', 'supervisor.plagiarism_select')->name('supervisor.plagiarism.form');
    Route::view('/supervisor/plagiarism/result', 'supervisor.plagiarism-result')->name('supervisor.plagiarism.result');





    /*
    |-------------------- الأدمن --------------------
    */
    Route::middleware(['auth','admin'])->group(function () {
        Route::view('/admin/panel', 'admin.adminPanel')->name('admin.panel');

        // Students CRUD (JSON)
        Route::get('/admin/students',        [AdminStudentController::class, 'index']);
        Route::post('/admin/students',       [AdminStudentController::class, 'store']);
        Route::put('/admin/students/{id}',   [AdminStudentController::class, 'update']);
        Route::delete('/admin/students/{id}',[AdminStudentController::class, 'destroy']);

        // Supervisors CRUD (JSON)
        Route::get('/admin/supervisors',         [AdminSupervisorController::class, 'index']);
        Route::post('/admin/supervisors',        [AdminSupervisorController::class, 'store']);
        Route::put('/admin/supervisors/{id}',    [AdminSupervisorController::class, 'update']);
        Route::delete('/admin/supervisors/{id}', [AdminSupervisorController::class, 'destroy']);
    });

});









// قبول / رفض الدعوة
Route::patch('/invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])
->name('invitations.accept');
Route::patch('/invitations/{invitation}/decline', [TeamInvitationController::class, 'decline'])
->name('invitations.decline');

// تفاصيل مشروع (JSON) لاستخدامها في Details Popup
Route::get('/projects/{project}/details', [ProjectController::class, 'details'])
->name('projects.details');