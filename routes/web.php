<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\SupervisorRequestController;
use App\Http\Controllers\SupervisorVerificationController;
use App\Http\Controllers\SupervisorProfileController;
use App\Http\Controllers\PlagiarismCheckController;
use App\Http\Controllers\SonarWebhookController;

use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminSupervisorController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\StudentResubmissionController;
use App\Http\Controllers\StudentProfileController;

use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Project;
use App\Models\TeamInvitation;

/*
|--------------------------------------------------------------------------
| الصفحة الرئيسية (welcome)
|--------------------------------------------------------------------------
*/
Route::view("/", "welcome")->name("welcome");

// Test route for Blade
Route::get("/test", function() {
    return view("test");
})->name("test");

/*
|--------------------------------------------------------------------------
| الضيوف (Guest): تسجيل الدخول/التسجيل
|--------------------------------------------------------------------------
*/
Route::middleware("guest")->group(function () {
    Route::get("/login", [AuthController::class, "showLoginForm"])->name("login");
    Route::post("/login", [AuthController::class, "login"])->name("login.post");

    Route::get("/register", [AuthController::class, "showRegisterForm"])->name("register");
    Route::post("/register", [AuthController::class, "register"])->name("register.post");
});

/*
|--------------------------------------------------------------------------
| المصدقون (Auth): لوحات/طلبات/مشاريع
|--------------------------------------------------------------------------
*/
Route::middleware("auth")->group(function () {

    Route::post("/logout", [AuthController::class, "logout"])->name("logout");

    // -------------------- الطالب --------------------
    Route::get("/student/dashboard", function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->loadMissing("student");
        abort_unless(optional($user)->student, 403, "Student profile not found.");
        $studentId = $user->student->id;

        $supervisors = Supervisor::with("user")
            ->whereHas('user', function($query) {
                $query->where('is_active', true);
            })
            ->get()->map(function ($s) {
            return (object) [
                "id"    => $s->id,
                "name"  => $s->user->name ?? trim(($s->first_name ?? "")." ".($s->last_name ?? "")),
                "email" => $s->user->email ?? "",
            ];
        });

        $students = Student::with("user")
            ->whereHas("user")
            ->where("user_id", "!=", $user->id)
            ->get()
            ->map(function ($st) {
                return (object) [
                    "id"             => $st->id,
                    "name"           => $st->user->name ?? trim(($st->first_name ?? "")." ".($st->last_name ?? "")) ,
                    "university_num" => $st->university_num,
                ];
            });

        $projects = Project::with(["owner.user", "supervisor.user", "students.user"])
            ->where("owner_student_id", $studentId)
            ->orWhereHas("students", fn($q) => $q->where("students.id", $studentId))
            ->orderByDesc("id")
            ->get();

        $joiningRequests = TeamInvitation::with(["project.owner.user","project.supervisor.user","invitedBy"])
            ->pending()
            ->where("to_student_id", $studentId)
            ->whereHas("project")
            ->latest()
            ->get();

        return view("student.studentDashboard", compact("supervisors", "students", "projects", "joiningRequests"));
    })->name("student.dashboard");

    // Student resubmission routes
    Route::get('/student/resubmit-certificate', [StudentResubmissionController::class, 'show'])
        ->name('student.resubmit-certificate');
    Route::post('/student/resubmit-certificate', [StudentResubmissionController::class, 'store'])
        ->name('student.resubmit-certificate.store');
    
    // Student Profile Management
    Route::get('/student/profile/edit', [StudentProfileController::class, 'edit'])
        ->name('student.profile.edit');
    Route::put('/student/profile/update', [StudentProfileController::class, 'update'])
        ->name('student.profile.update');

    // تفاصيل مشروع (JSON للـ popups)
    Route::get("/projects/{project}/details", function (Project $project) {
        $project->load(["supervisor.user", "students.user", "repository"]);

        $supUser = optional($project->supervisor)->user;
        $supName = $supUser->name
            ?? trim(($supUser->first_name ?? "")." ".($supUser->last_name ?? ""))
            ?? trim(($project->supervisor->first_name ?? "")." ".($project->supervisor->last_name ?? ""));

        $team = $project->students->map(function ($st) {
            $u = $st->user;
            $name = ($u->name
                ?? trim(($u->first_name ?? "")." ".($u->last_name ?? ""))
                ?? trim(($st->first_name ?? "")." ".($st->last_name ?? ""))) ?: "";
            return ["name" => $name];
        })->values();

        return response()->json([
            "id"          => $project->id,
            "title"       => $project->title,
            "description" => $project->description,
            "supervisor"  => $supName ?: "",
            "repository"  => optional($project->repository)->github_url ?? null,
            "team"        => $team,
        ]);
    })->name("projects.details");

    // مشاريع الطالب
    Route::post("/projects", [ProjectController::class, "store"])->name("projects.store");
    Route::get("/projects/{project}/report", [ProjectController::class, "report"])->name("projects.report");
    Route::delete("/projects/{project}", [ProjectController::class, "destroy"])->name("projects.destroy");

    // -------------------- المشرف --------------------
    Route::view("/supervisor/dashboard", "supervisor.supervisorDashboard")->name("supervisor.dashboard");

    Route::get("/supervisors", [SupervisorRequestController::class, "indexForStudent"])->name("supervisors.list");
    Route::post("/supervisors/{key}/request", [SupervisorRequestController::class, "sendRequest"])
        ->whereNumber("key")->name("supervisors.request.send");

    Route::get("/supervisor/requests/pending", [SupervisorRequestController::class, "indexForSupervisor"])
        ->name("supervisor.requests.pending");
    Route::patch("/supervisor/requests/{id}/{action}", [SupervisorRequestController::class, "respond"])
        ->whereNumber("id")->whereIn("action", ["accept","reject"])->name("supervisor.requests.respond");

    // مشاريع المشرف
    Route::get("/supervisor/accepted-projects", [ProjectController::class, "acceptedProjects"])
        ->name("supervisor.projects.accepted");

    // زر Code Analysis (Sonar)
    Route::post("/supervisor/projects/{project}/analyze", [ProjectController::class, "analyze"])
        ->whereNumber("project")
        ->name("supervisor.projects.analyze");

    // Plagiarism زر
    Route::get("/supervisor/projects/{project}/plagiarism", [PlagiarismCheckController::class, "plagiarism"])
        ->whereNumber("project")
        ->name("supervisor.projects.plagiarism");

    Route::post("/supervisor/plagiarism/check", [PlagiarismCheckController::class, "checkPlagiarism"])
        ->name("supervisor.plagiarism.check");

    Route::get("/supervisor/plagiarism/report/{id}", [PlagiarismCheckController::class, "viewPlagiarismReport"])
        ->whereNumber("id")
        ->name("projects.plagiarism.report");
    // Evaluation زر
    Route::post("/supervisor/projects/{project}/evaluate", [ProjectController::class, "runEvaluation"])
    ->whereNumber("project")
    ->name("supervisor.projects.evaluate");    

    // عرض صفحة نتائج التقييم
    Route::get("/supervisor/projects/{project}/evaluation", [ProjectController::class, "showEvaluation"])
        ->whereNumber("project")
        ->name("supervisor.projects.evaluation.show");

    // حفظ ملاحظة المشرف
    Route::post("/supervisor/projects/{project}/note", [ProjectController::class, "saveSupervisorNote"])
        ->whereNumber("project")
        ->name("supervisor.projects.note");

    // الانتحال
    Route::view("/supervisor/plagiarism/select", "supervisor.plagiarism_select")->name("supervisor.plagiarism.form");
    Route::view("/supervisor/plagiarism/result", "supervisor.plagiarism-result")->name("supervisor.plagiarism.result");

    // التحقق من الطلاب
    Route::get("/supervisor/students/verification", [SupervisorVerificationController::class, "index"])
        ->name("supervisor.students.verify.index");
    Route::patch("/supervisor/students/{student}/approve", [SupervisorVerificationController::class, "approve"])
        ->name("supervisor.students.verify.approve");
    Route::patch("/supervisor/students/{student}/reject", [SupervisorVerificationController::class, "reject"])
        ->name("supervisor.students.verify.reject");

    // تعديل بيانات المشرف
    Route::get("/supervisor/profile/edit", [SupervisorProfileController::class, "edit"])
        ->name("supervisor.profile.edit");
    Route::patch("/supervisor/profile/update", [SupervisorProfileController::class, "update"])
        ->name("supervisor.profile.update");

    // دعوات الانضمام
    Route::patch("/invitations/{invitation}/accept", [TeamInvitationController::class, "accept"])->name("invitations.accept");
    Route::patch("/invitations/{invitation}/decline", [TeamInvitationController::class, "decline"])->name("invitations.decline");
});

/*
|--------------------------------------------------------------------------
| الأدمن (داخل auth + admin)
|--------------------------------------------------------------------------
*/
Route::middleware(["auth", "admin"])->group(function () {
    Route::view("/admin/panel", "admin.adminPanel")->name("admin.panel");

    // Students CRUD (JSON)
    Route::get("/admin/students",        [AdminStudentController::class, "index"]);
    Route::post("/admin/students",       [AdminStudentController::class, "store"]);
    Route::put("/admin/students/{id}",   [AdminStudentController::class, "update"]);
    Route::delete("/admin/students/{id}",[AdminStudentController::class, "destroy"]);

    // Supervisors CRUD (JSON)
    Route::get("/admin/supervisors",         [AdminSupervisorController::class, "index"]);
    Route::post("/admin/supervisors",        [AdminSupervisorController::class, "store"]);
    Route::put("/admin/supervisors/{id}",    [AdminSupervisorController::class, "update"]);
    Route::delete("/admin/supervisors/{id}", [AdminSupervisorController::class, "destroy"]);
});

/*
|--------------------------------------------------------------------------
| File Routes (للمشرفين والمدراء)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/files/enrollment-certificate/{student}', [FileController::class, 'viewEnrollmentCertificate'])
        ->name('files.enrollment-certificate.view');
    Route::get('/files/enrollment-certificate/{student}/download', [FileController::class, 'downloadEnrollmentCertificate'])
        ->name('files.enrollment-certificate.download');
});

/*
|--------------------------------------------------------------------------
| SonarQube Webhook (لا يحتاج middleware)
|--------------------------------------------------------------------------
*/
Route::post('/sonar/webhook', [SonarWebhookController::class, 'handle'])
    ->name('sonar.webhook')
    ->withoutMiddleware(['web']); // تجنب CSRF middleware
