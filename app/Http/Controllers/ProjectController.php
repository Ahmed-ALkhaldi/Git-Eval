<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\GithubInsightsService;
use App\Services\MossService;
use App\Services\SonarQubeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\{
    User,
    Student,
    Project,
    ProjectMember,
    TeamInvitation,
    Repository,
    CodeAnalysisReport,
    Supervisor
};
use App\Models\Evaluation;
use App\Models\StudentEvaluation;
use ZipArchive;
use Symfony\Component\Process\Process;

class ProjectController extends Controller
{
    /** عرض قائمة المشاريع */
    public function index(Request $request)
    {
        $projects = Project::with(['owner.user','students.user','supervisor'])->latest()->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $projects], 200);
        }
        return view('projects.index', compact('projects'));
    }

    /** صفحة إنشاء مشروع (طلاب فقط) */
    public function create()
    {
        $me = Auth::user();
        if (!$me || $me->role !== 'student') {
            abort(403, 'Only students can access this page.');
        }

        $meStudent = $me->student;
        
        // Check verification status
        if (!$meStudent->isVerified()) {
            $message = match($meStudent->verification_status) {
                'pending' => 'You cannot create a project until you are approved by the supervisor.',
                'rejected' => 'Your request was rejected. Please resubmit your enrollment certificate from the dashboard.',
                default => 'Invalid verification status. Please contact administration.'
            };
            abort(403, $message);
        }

        $eligible = Student::query()
            ->whereDoesntHave('ownedProject')
            ->whereDoesntHave('memberships')
            ->where('verification_status', 'approved') // فقط الطلاب المقبولين
            ->whereKeyNot($meStudent->id)
            ->with('user')
            ->get();

        return view('projects.create', ['students' => $eligible]);
    }

    /** إنشاء المشروع + تنزيل ZIP + استخراج + إنشاء دعوات الأعضاء */
    public function store(Request $request)
    {
        set_time_limit(360);

        $user = Auth::user();
        if (!$user || $user->role !== 'student') {
            return $this->fail($request, 403, 'Only students can create projects.');
        }

        $owner = $user->student;
        if (!$owner) {
            return $this->fail($request, 422, 'Student profile not found.');
        }

        // Check student verification status
        if (!$owner->isVerified()) {
            $message = match($owner->verification_status) {
                'pending' => 'You cannot create a project until you are approved by the supervisor.',
                'rejected' => 'Your request was rejected. Please resubmit your enrollment certificate from the dashboard.',
                default => 'Invalid verification status. Please contact administration.'
            };
            return $this->fail($request, 422, $message);
        }

        // لا يملك مشروعاً ولا عضوية
        $alreadyMember = ProjectMember::where('student_id', $owner->id)->exists();
        if ($owner->ownedProject || $alreadyMember) {
            return $this->fail($request, 422, 'لا يمكنك إنشاء مشروع لأنك تملك/منضم لمشروع آخر.');
        }

        // التحقق
        $request->validate([
            'title'                 => 'required|string|max:190',
            'description'           => 'nullable|string',
            'github_url'            => 'required|url',
            'invite_student_ids'    => 'required|array|min:1|max:4',
            'invite_student_ids.*'  => 'integer|exists:students,id',
            'supervisor_id'         => 'nullable|integer|exists:supervisors,id', // ✅ جديد
        ]);


        // أهلية المدعوين
        $inviteeIds = array_values(array_unique($request->invite_student_ids));
        $ineligibleMembers = ProjectMember::whereIn('student_id', $inviteeIds)->pluck('student_id')->all();
        $owners = Project::whereIn('owner_student_id', $inviteeIds)->pluck('owner_student_id')->all();
        $bad = array_values(array_unique(array_merge($ineligibleMembers, $owners)));
        if (!empty($bad)) {
            return $this->fail($request, 422, 'بعض الطلاب المدعوين غير مؤهلين (لديهم مشروع أو عضوية).');
        }

        // تحليل رابط GitHub + refs
        [$ghUser, $ghRepo, $ref] = $this->parseGitHubUrl($request->github_url);
        if (!$ghUser || !$ghRepo) {
            return $this->fail($request, 422, '❌ Bad GitHub URL.');
        }
        $refsToTry = $this->resolveRefsToTry($ghUser, $ghRepo, $ref);

        // ===== المرحلة (أ): كتابة قاعدة البيانات فقط =====
        try {
            DB::beginTransaction();

            $project = Project::create([
                'title'            => $request->title,
                'description'      => $request->description,
                'owner_student_id' => $owner->id,
            ]);

            ProjectMember::create([
                'project_id' => $project->id,
                'student_id' => $owner->id,
                'role'       => 'owner',
            ]);

            Repository::create([
                'project_id'  => $project->id,
                'github_url'  => $request->github_url,
                'repo_name'   => $ghRepo,
                'description' => null,
                'stars'       => 0,
                'forks'       => 0,
                'open_issues' => 0,
            ]);

            foreach ($inviteeIds as $sid) {
                TeamInvitation::firstOrCreate(
                    ['project_id' => $project->id, 'to_student_id' => $sid, 'status' => 'pending'],
                    ['invited_by_user_id' => $owner->user_id]
                );
            }

            // ✅ لو تم اختيار مشرف في الفورم، أنشئ/حدّث طلب إشراف Pending
            if ($request->filled('supervisor_id')) {
                $supId = (int) $request->input('supervisor_id');

                // عطّل أي طلب نشط سابق لنفس الطالب (احترازيًا)
                \App\Models\SupervisorRequest::where('student_id', $owner->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active'    => false,
                        'status'       => 'rejected', // أو 'cancelled' حسب سياستك
                        'responded_at' => now(),
                    ]);

                // أنشئ/حدّث طلبًا نشطًا واحدًا Pending
                \App\Models\SupervisorRequest::updateOrCreate(
                    [
                        'student_id' => $owner->id,
                        'is_active'  => true,
                    ],
                    [
                        'supervisor_id' => $supId,
                        'status'        => 'pending',
                        'message'       => $request->input('description'), // أو نص مخصص إن رغبت
                        'responded_at'  => null,
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('DB create failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->fail($request, 422, 'فشل إنشاء السجلات: '.$e->getMessage());
        }

        // ===== المرحلة (ب): تنزيل وفكّ الـZIP خارج الترانزاكشن =====
        try {
            Storage::makeDirectory('private/zips');
            Storage::makeDirectory('projects');

            $zipPath = storage_path("app/private/zips/project_{$project->id}.zip");

            Log::info("DL start project={$project->id} repo={$ghUser}/{$ghRepo} refs=".json_encode($refsToTry));
            $workedRef = $this->downloadPublicRepoZipCodeload($ghUser, $ghRepo, $refsToTry, $zipPath);
            Log::info("DL ok project={$project->id} ref={$workedRef} size=".(@filesize($zipPath) ?: 0));

            $this->extractZipToProject($project->id, $zipPath);
            Log::info("Extract ok project={$project->id}");

            return $this->ok(
                $request,
                '✅ Project created, ZIP downloaded and extracted! Invitations sent.',
                route('student.dashboard')
            );
        } catch (\Throwable $e) {
            Log::warning('ZIP download/extract failed for project '.$project->id.' : '.$e->getMessage());

            // المشروع والدعوات محفوظين — فقط ننبّه المستخدم
            return $this->ok(
                $request,
                '⚠️ Project created and invitations sent, but ZIP download/extract failed. You can retry later.',
                route('student.dashboard')
            );
        }
    }

    /** مشاريع المشرف المقبولة لديه */
    public function acceptedProjects(Request $request)
    {
        $me = Auth::user();
        if ($me?->role !== 'supervisor') {
            return $this->fail($request, 403, 'Access denied.');
        }

        $supervisorModel = $me->supervisor;
        if (!$supervisorModel) {
            return $this->fail($request, 422, 'Supervisor profile not found.');
        }

        // تحميل العلاقات اللازمة لإظهار حالة الجاهزية للتقرير النهائي
        $projects = Project::with(['owner.user','students.user','repository','codeAnalysisReport','evaluation','plagiarismChecks','plagiarismChecksAsProject2'])
            ->where('supervisor_id', $supervisorModel->id)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $projects], 200);
        }

        return view('supervisor.accepted-projects', compact('projects'));
    }


    public function showEvaluation(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        /** @var \App\Models\Project $project */
        $project = Project::with(['students.user','supervisor.user','repository','evaluation','studentEvaluations.student.user'])->findOrFail($id);

        // Allow supervisor assigned to this project or owner student to view
        $canSupervisor = ($user->role === 'supervisor' && optional($user->supervisor)->id === $project->supervisor_id);
        $canOwner      = ($user->role === 'student' && optional($user->student)->id === $project->owner_student_id);
        abort_unless($canSupervisor || $canOwner, 403);

        return view('supervisor.evaluation', [
            'project' => $project,
        ]);
    }


    /** تحليل السونار وحفظ النتائج */
    public function analyze(Request $request, $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            return $this->fail($request, 403, '❌ Access denied. Supervisors only.');
        }

        $project = Project::findOrFail($id);

        // تعيين مفتاح SonarQube إذا لم يكن موجوداً
        $sonarProjectKey = "project_{$project->id}";
        if (!$project->sonar_project_key) {
            $project->update(['sonar_project_key' => $sonarProjectKey]);
        } else {
            $sonarProjectKey = $project->sonar_project_key;
        }

        // تأكد أن الملفات مفكوكة
        $this->ensureExtractedIfNeeded($project->id);
        $finalExtractPath = storage_path("app/projects/project_{$project->id}");

        // إعداد متغيرات التحليل - استخدام 127.0.0.1 بدل localhost لتجنب مشاكل IPv6/Proxy
        $sonarHost  = env('SONARQUBE_HOST', 'http://127.0.0.1:9000');
        $sonarToken = env('SONARQUBE_TOKEN', '');
        if (!$sonarToken) {
            return $this->fail($request, 422, '❌ SONARQUBE_TOKEN غير مُعدّ في .env');
        }

        // إعداد متغيرات ملف الـ batch
        $projectId   = $project->id;
        $projectKey  = $sonarProjectKey;
        $projectDir  = $finalExtractPath;

        $bat   = env('SONAR_ANALYZE_BAT', base_path('sonar_analyze.bat'));
        $host  = $sonarHost;
        $token = $sonarToken;
        $scan  = env('SONAR_SCANNER_BIN', 'sonar-scanner');
        $jhome = env('JAVA_HOME', 'C:/Program Files/Java/jdk-17');
        $to    = (string) env('SONAR_SCANNER_TIMEOUT', 600);

        // تأكد من وجود مجلد السورس قبل الاستدعاء
        if (!is_dir($projectDir)) {
            return $this->fail($request, 422, "❌ Project source directory not found: {$projectDir}");
        }

        // تأكد من وجود ملف الـ batch
        if (!file_exists($bat)) {
            return $this->fail($request, 422, "❌ Batch file not found: {$bat}");
        }

        // إعداد البيئة للـ Process - الحل للمشكلة WinSock 10106
        $baseEnv = getenv(); // الحصول على بيئة النظام الكاملة
        $env = $baseEnv; // استخدامها كأساس
        
        // إضافة/تعديل المتغيرات المطلوبة فقط
        $env['JAVA_HOME'] = $jhome;
        $env['SONAR_SCANNER_OPTS'] = implode(' ', [
            '-Xmx2048m',
            '-Xms512m',
            '-Djava.net.useSystemProxies=false',
            '-Dfile.encoding=UTF-8',
            // مسار مؤهل كامل لمجلد التمب
            '-Djava.io.tmpdir=' . getenv('TEMP') ?: 'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Temp',
        ]);
        
        // التأكد من وجود متغيرات Windows الأساسية
        $env['SystemRoot'] = $baseEnv['SystemRoot'] ?? 'C:\\Windows';
        $env['WINDIR'] = $baseEnv['WINDIR'] ?? 'C:\\Windows';
        $env['TEMP'] = $baseEnv['TEMP'] ?? 'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Temp';
        $env['TMP'] = $baseEnv['TMP'] ?? $env['TEMP'];
        
        // إضافة مجلد scanner إلى PATH (بدون استبدال PATH الأصلي)
        $scannerDir = dirname($scan);
        if (isset($baseEnv['PATH'])) {
            $env['PATH'] = $scannerDir . ';' . $baseEnv['PATH'];
        } else {
            $env['PATH'] = $scannerDir;
        }
        
        // استدعاء ملف الـ batch (CMD) مع البيئة الصحيحة
        $process = new Process([$bat, $projectDir, $projectKey, $host, $token, $scan, $jhome, $to], null, $env);
        // وقت التنفيذ الإجمالي
        $process->setTimeout((int)$to);
        $process->run();

        // لوج وتصرّف النتيجة
        if (!$process->isSuccessful()) {
            Log::error('❌ Sonar batch failed', [
                'exit_code' => $process->getExitCode(),
                'out'       => $process->getOutput(),
                'err'       => $process->getErrorOutput(),
                'args'      => [$projectDir, $projectKey, $host, '[TOKEN HIDDEN]', $scan, $jhome, $to],
            ]);
            return $this->fail($request, 500, '❌ SonarQube analysis failed. Check logs for details.');
        }

        Log::info('[OK] Sonar batch completed', [
            'out'  => $process->getOutput(),
            'args' => [$projectDir, $projectKey, $host, '[TOKEN HIDDEN]', $scan, $jhome, $to],
        ]);

        // انتظار اكتمال التحليل في Compute Engine
        sleep(5); // مؤقتاً - سيتم استبداله بـ polling لاحقاً

        // استخدام النظام الجديد للمزامنة
        \App\Jobs\SyncSonarAnalysisJob::dispatch($sonarProjectKey, null);

        // بإمكانك هنا إرجاع استجابة فورية للمستخدم، والاعتماد على Webhook لإحضار النتائج وتخزينها
        return $this->ok(
            $request,
            '✅ Analysis started/uploaded successfully. Results will appear after server processing.',
            route('supervisor.projects.accepted')
        );
    }

    public function runEvaluation(Request $request, $id)
    {
        // 1) السماح لمالك المشروع أو المشرف المنسّب فقط
        $user = \Illuminate\Support\Facades\Auth::user();
        abort_unless($user, 403);

        /** @var \App\Models\Project $project */
        $project = \App\Models\Project::with(['students.user', 'repository'])->findOrFail($id);

        $canSupervisor = ($user->role === 'supervisor' && optional($user->supervisor)->id === $project->supervisor_id);
        $canOwner      = ($user->role === 'student' && optional($user->student)->id === $project->owner_student_id);
        abort_unless($canSupervisor || $canOwner, 403, 'Only owner or assigned supervisor can evaluate.');

        // 2) التحقق من مستودع GitHub العام
        $repoUrl = optional($project->repository)->github_url;
        if (!$repoUrl) {
            return $this->fail($request, 422, 'Missing GitHub repository URL for this project.');
        }

        // استخراج owner/repo
        [$owner, $repo] = (function (string $url) {
            $owner = $repo = null;
            if (preg_match('~github\.com/([^/]+)/([^/#?]+)~i', $url, $m)) {
                $owner = $m[1];
                $repo  = preg_replace('~\.git$~i', '', $m[2]);
            }
            return [$owner, $repo];
        })($repoUrl);

        if (!$owner || !$repo) {
            return $this->fail($request, 422, 'Invalid GitHub repository URL.');
        }

        // 3) إعدادات طلبات GitHub العامة (بدون توكن)
        $headers = [
            'User-Agent'      => 'GitEvalAI',
            'Accept'          => 'application/vnd.github+json',
            'Accept-Encoding' => 'identity',
        ];
        if ($token = env('GITHUB_TOKEN')) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        // ============= جمع الإشارات العامة من GitHub =============

        // A) contributors: تقريب لعدد الـ commits لكل login داخل المستودع
        $contributors = [];
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(15)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/contributors", [
                    'per_page' => 100,
                    'anon'     => 'false',
                ]);
            if ($resp->ok()) {
                $contributors = collect($resp->json() ?: [])
                    ->mapWithKeys(function ($c) {
                        $login = strtolower($c['login'] ?? '');
                        $cnt   = (int)($c['contributions'] ?? 0);
                        return $login ? [$login => $cnt] : [];
                    })->all();
            }
        } catch (\Throwable $e) {
            Log::warning("runEvaluation: contributors fetch failed: ".$e->getMessage());
        }

        // B) دوال مساعدة عبر Search API (نعتمد total_count فقط لتقليل الاستهلاك)
        $fetchCount = function (string $q) use ($headers) {
            try {
                $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(15)
                    ->get('https://api.github.com/search/issues', ['q' => $q, 'per_page' => 1]);
                if ($resp->ok()) {
                    return (int) data_get($resp->json(), 'total_count', 0);
                }
            } catch (\Throwable $e) {
                Log::info("runEvaluation: search failed for [{$q}]: ".$e->getMessage());
            }
            return 0;
        };

        // - PRs التي فتحها الطالب داخل نفس المستودع
        $fetchPRsOpened = fn(string $login) => $fetchCount("repo:{$owner}/{$repo} type:pr author:{$login}");
        // - PRs المدمجة للطالب داخل نفس المستودع
        $fetchPRsMerged = fn(string $login) => $fetchCount("repo:{$owner}/{$repo} type:pr is:merged author:{$login}");
        // - Issues التي فتحها الطالب داخل نفس المستودع
        $fetchIssues    = fn(string $login) => $fetchCount("repo:{$owner}/{$repo} type:issue author:{$login}");
        // - PRs قام الطالب بمراجعتها (GitHub يدعم reviewed-by في البحث)
        $fetchReviews   = fn(string $login) => $fetchCount("repo:{$owner}/{$repo} type:pr reviewed-by:{$login}");

        // 4) الطلاب المستهدفون
        $students = $project->students;
        if ($students->isEmpty()) {
            return $this->fail($request, 422, 'Project has no students to evaluate.');
        }

        // 5) حساب المقاييس لكل طالب
        $rows = []; // student_id => metrics
        foreach ($students as $st) {
            $login = strtolower(trim((string)($st->github_username ?? '')));
            if ($login === '') {
                $rows[$st->id] = [
                    'github_username' => null,
                    'commits'         => 0,
                    'prs_opened'      => 0,
                    'issues_opened'   => 0,
                    'reviews'         => 0,
                    'prs_merged'      => 0,
                ];
                continue;
            }

            $commits = (int)($contributors[$login] ?? 0);
            $prs     = $fetchPRsOpened($login);
            $issues  = $fetchIssues($login);
            $reviews = $fetchReviews($login);
            $merged  = $fetchPRsMerged($login);

            $rows[$st->id] = [
                'github_username' => $login,
                'commits'         => $commits,
                'prs_opened'      => $prs,
                'issues_opened'   => $issues,
                'reviews'         => $reviews,
                'prs_merged'      => $merged,
            ];
        }

        // 6) تطبيع واحتساب الدرجات (0..100)
        // أوزان قابلة للتعديل — بدون توكن نركز على إشارات متاحة علنًا:
        $W_COMMITS = 0.60;
        $W_PRS     = 0.25;
        $W_ISSUES  = 0.10;
        $W_REVIEWS = 0.05;

        $maxC  = max([1, ...array_map(fn($r) => $r['commits'],       $rows)]);
        $maxPR = max([1, ...array_map(fn($r) => $r['prs_opened'],    $rows)]);
        $maxIS = max([1, ...array_map(fn($r) => $r['issues_opened'], $rows)]);
        $maxRV = max([1, ...array_map(fn($r) => $r['reviews'],       $rows)]);

        // 7) إنشاء/تحديث Evaluation ملخّص للمشروع
        /** @var \App\Models\Evaluation $evaluation */
        $evaluation = \App\Models\Evaluation::updateOrCreate(
            ['project_id' => $project->id],
            [
                'computed_at' => now(),
                'summary'     => [
                    'repo'    => "{$owner}/{$repo}",
                    'weights' => [
                        'commits' => $W_COMMITS,
                        'prs'     => $W_PRS,
                        'issues'  => $W_ISSUES,
                        'reviews' => $W_REVIEWS,
                    ],
                    'note'     => 'Public GitHub signals only (no token). Values based on /contributors and search/issues.',
                    'max'      => [
                        'commits' => $maxC,
                        'prs'     => $maxPR,
                        'issues'  => $maxIS,
                        'reviews' => $maxRV,
                    ],
                ],
            ]
        );

        // 8) حفظ/تحديث StudentEvaluation لكل طالب
        foreach ($students as $st) {
            $m   = $rows[$st->id];

            $normC  = $m['commits']       / $maxC;
            $normPR = $m['prs_opened']    / $maxPR;
            $normIS = $m['issues_opened'] / $maxIS;
            $normRV = $m['reviews']       / $maxRV;

            $score = round(
                ($normC  * $W_COMMITS) +
                ($normPR * $W_PRS)     +
                ($normIS * $W_ISSUES)  +
                ($normRV * $W_REVIEWS)
            * 100, 2);

            \App\Models\StudentEvaluation::updateOrCreate(
                ['project_id' => $project->id, 'student_id' => $st->id],
                [
                    'evaluation_id' => $evaluation->id,
                    'commits'       => $m['commits'],
                    'additions'     => 0, // لا نحسبها بدون توكن
                    'deletions'     => 0, // لا نحسبها بدون توكن
                    'issues_opened' => $m['issues_opened'],
                    'prs_opened'    => $m['prs_opened'],
                    'prs_merged'    => $m['prs_merged'] ?? 0,
                    'reviews'       => $m['reviews'],
                    'score'         => $score,
                    'comments'      => $this->makeEvalComment($m, $score),
                ]
            );
        }

        return $this->ok(
            $request,
            '✅ Evaluation completed and saved!',
            route('supervisor.projects.evaluation.show', $project->id)
        );
    }

    /**
     * توليد تعليق مختصر بناءً على المقاييس.
     */
    protected function makeEvalComment(array $m, float $score): string
    {
        $parts = [];
        if (($m['commits'] ?? 0) > 0)       $parts[] = "commits={$m['commits']}";
        if (($m['prs_opened'] ?? 0) > 0)    $parts[] = "PRs={$m['prs_opened']}";
        if (($m['issues_opened'] ?? 0) > 0) $parts[] = "issues={$m['issues_opened']}";
        if (($m['reviews'] ?? 0) > 0)       $parts[] = "reviews={$m['reviews']}";

        $detail = $parts ? (' ['.implode(', ', $parts).']') : '';
        return "Auto-evaluated from public GitHub signals. Score={$score}{$detail}";
    }

   

    // =========================
    // Helpers (Responses)
    // =========================

    private function ok(Request $request, string $message, ?string $redirectTo = null, int $status = 200)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], $status);
        }
        return $redirectTo
            ? redirect($redirectTo)->with('success', $message)
            : back()->with('success', $message);
    }

    private function fail(Request $request, int $status, string $message)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], $status);
        }
        return back()->with('error', $message);
    }




    // =========================
    // Helpers (GitHub Download)
    // =========================

    /**
     * parse GitHub URL to [owner, repo, ref?]
     * يدعم:
     *  - https://github.com/owner/repo
     *  - https://github.com/owner/repo?ref=branchOrTag
     *  - https://github.com/owner/repo#branchOrTag
     *  - https://github.com/owner/repo/tree/branchOrTag
     *  - https://github.com/owner/repo.git
     */
    protected function parseGitHubUrl(string $githubUrl): array
    {
        $owner = $repo = $ref = null;

        if (preg_match('~github\.com/([^/]+)/([^/#?]+)~i', $githubUrl, $m)) {
            $owner = $m[1];
            $repo  = preg_replace('~\.git$~i', '', $m[2]);
        }

        if (preg_match('~[?&]ref=([^&#]+)~i', $githubUrl, $m)) {
            $ref = $m[1];
        } elseif (preg_match('~#([^/#?]+)$~', $githubUrl, $m)) {
            $ref = $m[1];
        } elseif (preg_match('~github\.com/[^/]+/[^/]+/tree/([^/#?]+)~i', $githubUrl, $m)) {
            $ref = $m[1];
        }

        return [$owner, $repo, $ref];
    }

    /** اختيار refs (default_branch إن توفر التوكين) */
    protected function resolveRefsToTry(string $owner, string $repo, ?string $refFromUrl): array
    {
        if ($refFromUrl) {
            // إن أعطى المستخدم ref محدد، استخدمه فقط
            return [$refFromUrl];
        }

        // مرشّحات افتراضية
        $refs = ['main','master','develop','dev'];

        // 1) حاول جلب default_branch (بدون توكين أولًا)
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'GitEvalAI',
                'Accept'     => 'application/vnd.github+json',
            ])->timeout(12)->get("https://api.github.com/repos/{$owner}/{$repo}");

            if ($resp->ok() && ($def = $resp->json('default_branch'))) {
                array_unshift($refs, $def);
            }
        } catch (\Throwable $e) {
            Log::info("resolveRefsToTry: unauth default_branch skip: ".$e->getMessage());
        }

        // 2) لو فيه توكين، أعد المحاولة (أكثر موثوقية ضد الـ rate limit)
        try {
            if ($token = env('GITHUB_TOKEN')) {
                $resp = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent'    => 'GitEvalAI',
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/vnd.github+json',
                ])->timeout(12)->get("https://api.github.com/repos/{$owner}/{$repo}");

                if ($resp->ok() && ($def = $resp->json('default_branch'))) {
                    array_unshift($refs, $def);
                }
            }
        } catch (\Throwable $e) {
            Log::info("resolveRefsToTry: auth default_branch skip: ".$e->getMessage());
        }

        // إزالة التكرار مع الحفاظ على الترتيب
        $refs = array_values(array_unique($refs));

        return $refs;
    }


    /**
     * تنزيل ZIP من codeload مع fallback شامل:
     * - لعدة فروع: جرّب heads لكل ref بالترتيب، ثم zipball لكل ref بالترتيب.
     * - لفرع واحد: جرّب heads ثم tags ثم zipball.
     * @return string ref الذي نجح عليه التنزيل
     */
    protected function downloadPublicRepoZipCodeload(string $owner, string $repo, array $refsToTry, string $zipPath): string
    {
        if (empty($refsToTry)) $refsToTry = ['main','master'];

        // حالة ref واحد
        if (count($refsToTry) === 1) {
            $ref = $refsToTry[0];

            $candidates = [
                "https://codeload.github.com/{$owner}/{$repo}/zip/refs/heads/{$ref}",
                "https://codeload.github.com/{$owner}/{$repo}/zip/refs/tags/{$ref}",
            ];
            foreach ($candidates as $url) {
                Log::info("Trying codeload (single): {$url}");
                if ($this->attemptCodeload($url, $zipPath)) {
                    return $ref;
                }
            }

            // zipball fallback
            Log::info("Trying zipball fallback (single) for ref {$ref}");
            $this->downloadRepoZip($owner, $repo, $ref, $zipPath);
            return $ref;
        }

        // حالة عدّة refs — 1) جرّب heads لكل ref
        foreach ($refsToTry as $ref) {
            $url = "https://codeload.github.com/{$owner}/{$repo}/zip/refs/heads/{$ref}";
            Log::info("Trying codeload (multi): {$url}");
            if ($this->attemptCodeload($url, $zipPath)) {
                return $ref;
            }
        }

        // 2) إن فشلت heads كلها — جرّب zipball لكل ref بالترتيب
        foreach ($refsToTry as $ref) {
            try {
                Log::info("Trying zipball fallback (multi) for ref {$ref}");
                $this->downloadRepoZip($owner, $repo, $ref, $zipPath);
                return $ref;
            } catch (\Throwable $e) {
                Log::warning("zipball failed for {$owner}/{$repo}@{$ref}: ".$e->getMessage());
            }
        }

        throw new \RuntimeException(
            "Could not download ZIP for refs (heads + zipball attempted): ".implode(', ', $refsToTry)
        );
    }


    /**
     * محاولة تنزيل ZIP من codeload مع تحقق قوي للمحتوى.
     */
    protected function attemptCodeload(string $url, string $zipPath): bool
    {
        if (file_exists($zipPath)) @unlink($zipPath);

        $headers = [
            'User-Agent'      => 'GitEvalAI',
            'Accept'          => 'application/zip',
            'Accept-Encoding' => 'identity', // منع gzip مع sink
        ];
        if ($token = env('GITHUB_TOKEN')) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        $resp = Http::retry(3, 800)
            ->timeout(120)
            ->withHeaders($headers)
            ->withOptions([
                'sink' => $zipPath,
                'allow_redirects' => true,
            ])
            ->get($url);

        if (!$resp->successful() || !file_exists($zipPath)) {
            @unlink($zipPath);
            Log::warning("ZIP download failed: {$url}, HTTP=".$resp->status());
            return false;
        }

        clearstatcache(true, $zipPath);

        // Content-Type يجب أن يحوي "zip"
        $ct = $resp->header('Content-Type');
        if ($ct && stripos($ct, 'zip') === false) {
            $head = @file_get_contents($zipPath, false, null, 0, 200);
            Log::warning("Non-zip response from {$url}. CT={$ct}. Head=".substr((string)$head,0,200));
            @unlink($zipPath);
            return false;
        }

        // توقيع PK + فتح الأرشيف + وجود ملفات Laravel المعتادة
        $fh = fopen($zipPath, 'rb'); $sig = fread($fh, 2); fclose($fh);
        if ($sig !== "PK") {
            $head = @file_get_contents($zipPath, false, null, 0, 200);
            Log::warning("Bad ZIP signature from {$url}. Head=".substr((string)$head,0,200));
            @unlink($zipPath);
            return false;
        }

        $za = new ZipArchive();
        $opened = $za->open($zipPath);
        if ($opened === true && $za->numFiles > 0) {
            // فحص سريع لوجود بعض الملفات الأساسية (اختياري)
            $must = ['composer.json','artisan','app/','config/'];
            $have = 0;
            $limit = min($za->numFiles, 400);
            for ($i = 0; $i < $limit; $i++) {
                $name = $za->getNameIndex($i);
                foreach ($must as $m) {
                    if (str_ends_with($name, $m) || str_contains($name, "/{$m}")) { $have++; break; }
                }
            }
            $za->close();

            if ($have < 2) {
                Log::warning("ZIP looks suspicious: missing expected Laravel files");
                @unlink($zipPath);
                return false;
            }

            return true;
        }

        Log::warning("ZipArchive open failed ({$opened}) or empty archive from {$url}");
        @unlink($zipPath);
        return false;
    }

    /** تنزيل zipball عبر API/GitHub */
    protected function downloadRepoZip(string $owner, string $repo, string $ref, string $destPath): void
    {
        $headers = [
            'User-Agent'      => 'GitEvalAI',
            'Accept'          => 'application/zip',
            'Accept-Encoding' => 'identity',
        ];
        if ($token = env('GITHUB_TOKEN')) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        $candidates = [
            "https://api.github.com/repos/{$owner}/{$repo}/zipball/{$ref}",
            "https://github.com/{$owner}/{$repo}/archive/refs/heads/{$ref}.zip",
        ];

        $ok = false;
        foreach ($candidates as $url) {
            try {
                $resp = Http::withHeaders($headers)
                    ->timeout(120)
                    ->withOptions(['allow_redirects' => true, 'stream' => true])
                    ->get($url);

                if (!$resp->ok()) {
                    Log::warning("ZIP GET failed {$url}: HTTP ".$resp->status());
                    continue;
                }

                $stream = fopen($destPath, 'w+b');
                foreach ($resp->toPsrResponse()->getBody() as $chunk) {
                    fwrite($stream, $chunk);
                }
                fclose($stream);
                clearstatcache(true, $destPath);

                $fh = fopen($destPath, 'rb'); $sig = fread($fh, 2); fclose($fh);
                if ($sig === "PK") {
                    $za = new ZipArchive;
                    if ($za->open($destPath) === true && $za->numFiles > 0) {
                        $za->close();
                        $ok = true;
                        break;
                    }
                }

                $head = @file_get_contents($destPath, false, null, 0, 200);
                Log::warning("Bad ZIP content from {$url}. Head=" . substr((string)$head, 0, 200));
            } catch (\Throwable $e) {
                Log::warning("ZIP download exception {$url}: ".$e->getMessage());
            }
        }

        if (!$ok) {
            if (file_exists($destPath)) @unlink($destPath);
            throw new \Exception('❌ Failed to download a valid ZIP (rate limit/redirect?). Add/verify GITHUB_TOKEN in .env');
        }
    }

    /** هل يشبه ZIP (PK)؟ */
    protected function looksLikeZip(string $path): bool
    {
        if (!file_exists($path) || filesize($path) < 4) return false;
        $fh = fopen($path, 'rb');
        $sig = fread($fh, 2);
        fclose($fh);
        return $sig === "PK";
    }

    /** اختيار جذر المجلد داخل الأرشيف */
    protected function pickRootDir(string $path): ?string
    {
        $items = array_values(array_filter(scandir($path), function ($e) use ($path) {
            if ($e === '.' || $e === '..') return false;
            if (strpos($e, '__MACOSX') === 0) return false;
            if (strpos($e, '.') === 0) return false; // مخفية
            return is_dir($path . DIRECTORY_SEPARATOR . $e);
        }));

        if (count($items) === 1) return $items[0];

        $best = null; $bestScore = -1;
        foreach ($items as $dir) {
            $full = $path . DIRECTORY_SEPARATOR . $dir;
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($full, \FilesystemIterator::SKIP_DOTS));
            $count = 0;
            foreach ($rii as $f) { $count++; }
            if ($count > $bestScore) { $bestScore = $count; $best = $dir; }
        }
        return $best;
    }

    /** استخراج الأرشيف إلى مسار المشروع */
    protected function extractZipToProject(int $projectId, string $zipPath): void
    {
        $tmpExtractPath   = storage_path("app/projects/tmp_project_{$projectId}");
        $finalExtractPath = storage_path("app/projects/project_{$projectId}");

        if (!is_dir($tmpExtractPath)) mkdir($tmpExtractPath, 0777, true);

        $zip = new ZipArchive;
        $code = $zip->open($zipPath);
        if ($code !== true) {
            $errMap = [
                ZipArchive::ER_NOZIP  => 'Not a zip archive',
                ZipArchive::ER_INCONS => 'Inconsistent archive',
                ZipArchive::ER_CRC    => 'CRC error',
            ];
            $msg = $errMap[$code] ?? "Zip open error code {$code}";
            throw new \Exception("❌ Failed to open ZIP for project {$projectId}: {$msg}");
        }

        try {
            if (!$zip->extractTo($tmpExtractPath)) {
                throw new \Exception("❌ Failed to extract ZIP for project {$projectId}");
            }
        } finally {
            $zip->close();
        }

        $root = $this->pickRootDir($tmpExtractPath);
        if (!$root || !is_dir("{$tmpExtractPath}/{$root}")) {
            File::deleteDirectory($tmpExtractPath);
            throw new \Exception("❌ Unexpected ZIP structure for project {$projectId}");
        }

        if (is_dir($finalExtractPath)) File::deleteDirectory($finalExtractPath);
        mkdir($finalExtractPath, 0777, true);

        File::copyDirectory("{$tmpExtractPath}/{$root}", $finalExtractPath);
        File::deleteDirectory($tmpExtractPath);

        Log::info("✅ Project {$projectId} extracted to {$finalExtractPath}");
    }

    /** تأكد من وجود نسخة مستخرجة، وإلا استخرج من الـ ZIP */
    protected function ensureExtractedIfNeeded(int $projectId): void
    {
        $zipPath          = storage_path("app/private/zips/project_{$projectId}.zip");
        $finalExtractPath = storage_path("app/projects/project_{$projectId}");
        if (is_dir($finalExtractPath) && count(glob($finalExtractPath . DIRECTORY_SEPARATOR . '*'))) {
            return;
        }
        if (!file_exists($zipPath) || !$this->looksLikeZip($zipPath)) {
            throw new \Exception("❌ ZIP not found or invalid for project {$projectId}");
        }
        $this->extractZipToProject($projectId, $zipPath);
    }

    // =========================
    // CRUD/Access + Report
    // =========================

    public function evaluate($id) { return "📝 Evaluation for project ID {$id}"; }
    public function show($id) { /* ... */ }
    public function update(Request $request, $id) { /* ... */ }

    /** صفحة تقرير المشروع */
    public function report(Project $project)
    {
        $user = Auth::user();

        // السماح للمشرف بالمشاهدة، أو أي طالب عضو/مالك
        if ($user?->role !== 'supervisor') {
            $student = $this->currentStudent();
            abort_unless($this->canAccessProject($project, $student), 403);
        }

        // تحميل العلاقات اللازمة للعرض
        $project->load([
            'owner.user',
            'supervisor.user',
            'students.user',
            'repository',
            'evaluation',
            'studentEvaluations.student.user',
            'plagiarismChecks.project1',             // project_id = this مع معلومات المشروع الأول
            'plagiarismChecks.project2',             // project_id = this مع معلومات المشروع الثاني
            'plagiarismChecksAsProject2.project1',   // project2_id = this مع معلومات المشروع الأول
            'plagiarismChecksAsProject2.project2',   // project2_id = this مع معلومات المشروع الثاني
            'codeAnalysisReport',           // آخر تقرير (أو اجعله latestOfMany في الموديل)
        ]);

        // نتائج تفصيلية (إن وُجدت)
        $analysisResults = \App\Models\CodeAnalysisResult::where('project_id', $project->id)->get();

        // آخر فحص سرقة أدبي (يدمج الجدولين ويأخذ الأحدث)
        $plagAll = $project->plagiarismChecks->concat($project->plagiarismChecksAsProject2);
        $latestPlagiarism = $plagAll->sortByDesc('id')->first();

        return view('projects.report', compact('project', 'analysisResults', 'latestPlagiarism'));
    }


    /** حذف مشروع (المالك فقط) */
    public function destroy(Project $project)
    {
        $student = $this->currentStudent();

        $isOwner = ($project->owner_student_id === $student->id);
        $isPivotOwner = $project->students()
            ->where('students.id', $student->id)
            ->wherePivot('role', 'owner')
            ->exists();

        abort_unless($isOwner || $isPivotOwner, 403);

        $project->delete();

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Project deleted successfully.');
    }

    /** الطالب الحالي المرتبط بالمستخدم */
    protected function currentStudent(): Student
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user, 403);

        // يحمّل العلاقة فقط إذا كانت غير محمّلة
        $user->loadMissing('student');

        abort_unless($user->student, 403);
        return $user->student;
    }


    /** صلاحية وصول الطالب للمشروع */
    protected function canAccessProject(Project $project, Student $student): bool
    {
        if ($project->owner_student_id === $student->id) {
            return true;
        }

        return $project->students()
            ->where('students.id', $student->id)
            ->exists();
    }

    /** تفاصيل مشروع (API) */
    public function details(Project $project)
    {
        $student = Student::where('user_id', Auth::id())->firstOrFail();

        $isOwner  = $project->owner_student_id === $student->id;
        $isMember = $project->students()->where('students.id', $student->id)->exists();
        abort_unless($isOwner || $isMember, 403);

        $project->load(['supervisor.user', 'students.user']);

        $supervisorName = $project->supervisor->user->name
            ?? trim(($project->supervisor->first_name ?? '').' '.($project->supervisor->last_name ?? ''));

        return response()->json([
            'id'          => $project->id,
            'title'       => $project->title,
            'description' => $project->description,
            'supervisor'  => $supervisorName ?: null,
            'team'        => $project->students->map(function ($st) {
                return [
                    'id'   => $st->id,
                    'name' => $st->user->name ?? trim(($st->first_name ?? '').' '.($st->last_name ?? '')),
                ];
            })->values(),
        ]);
    }

    /** حفظ ملاحظة المشرف */
    public function saveSupervisorNote(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $project = Project::findOrFail($id);

        // السماح للمشرف المنسّب فقط
        $canSupervisor = ($user->role === "supervisor" && optional($user->supervisor)->id === $project->supervisor_id);
        abort_unless($canSupervisor, 403, "Only assigned supervisor can add notes.");

        $request->validate([
            "supervisor_note" => "nullable|string|max:1000",
        ]);

        $project->update([
            "supervisor_note" => $request->supervisor_note,
        ]);

        return $this->ok(
            $request,
            "✅ Supervisor note saved successfully!",
            route("supervisor.projects.accepted")
        );
    }
}
