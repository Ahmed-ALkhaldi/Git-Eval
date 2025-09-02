<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Http, Storage, Log, File, DB};
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
use ZipArchive;
use Symfony\Component\Process\Process;

class ProjectController extends Controller
{
    /** عرض قائمة المشاريع (يمكن تخصيصها لاحقاً حسب الدور) */
    public function index(Request $request)
    {
        $projects = Project::with(['owner.user','students.user','supervisor'])->latest()->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $projects], 200);
        }

        return view('projects.index', compact('projects'));
    }

    /** صفحة إنشاء مشروع (ويب): يعرض فقط الطلاب المؤهَّلين للدعوة */
    public function create()
    {
        $me = Auth::user();
        if (!$me || $me->role !== 'student') {
            abort(403, 'Only students can access this page.');
        }

        $meStudent = $me->student;
        $eligible = Student::query()
            ->whereDoesntHave('ownedProject')
            ->whereDoesntHave('memberships')
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

        // المالك لا يملك مشروعاً ولا عضوية حالية
        $alreadyMember = ProjectMember::where('student_id', $owner->id)->exists();
        if ($owner->ownedProject || $alreadyMember) {
            return $this->fail($request, 422, 'لا يمكنك إنشاء مشروع لأنك تملك/منضم لمشروع آخر.');
        }

        // الفريق: 2..5 => المالك + (1..4) دعوات
        $request->validate([
            'title'                 => 'required|string|max:190',
            'description'           => 'nullable|string',
            'github_url'            => 'required|url',
            'invite_student_ids'    => 'required|array|min:1|max:4',
            'invite_student_ids.*'  => 'integer|exists:students,id',
        ]);

        // أهلية المدعوين
        $inviteeIds = array_values(array_unique($request->invite_student_ids));
        $ineligibleMembers = ProjectMember::whereIn('student_id', $inviteeIds)->pluck('student_id')->all();
        $owners = Project::whereIn('owner_student_id', $inviteeIds)->pluck('owner_student_id')->all();
        $bad = array_values(array_unique(array_merge($ineligibleMembers, $owners)));
        if (!empty($bad)) {
            return $this->fail($request, 422, 'بعض الطلاب المدعوين غير مؤهلين (لديهم مشروع أو عضوية).');
        }

        // معلومات المستودع من GitHub
        $repoUrl = $request->github_url;
        $parsed  = $this->parseGitHubUrl($repoUrl);
        if (!$parsed) {
            return $this->fail($request, 422, '❌ Bad GitHub URL.');
        }
        $ghUser = $parsed['user'];
        $ghRepo = $parsed['repo'];

        $repoResponse = Http::withHeaders(['User-Agent' => 'GitEvalAI'])
            ->timeout(60)
            ->get("https://api.github.com/repos/{$ghUser}/{$ghRepo}");

        if (!$repoResponse->ok()) {
            return $this->fail($request, 422, '❌ Failed to fetch GitHub repository info.');
        }

        $defaultBranch = $repoResponse['default_branch'] ?? 'main';
        $repoName      = $repoResponse['name'] ?? $this->extractRepoName($repoUrl);

        // إنشاء المشروع + العضوية + المستودع + تنزيل/فك ZIP + الدعوات
        DB::transaction(function () use ($request, $owner, $repoUrl, $repoResponse, $defaultBranch, $repoName, $inviteeIds, $ghUser, $ghRepo) {

            $project = Project::create([
                'title'            => $request->title,
                'description'      => $request->description,
                'owner_student_id' => $owner->id,
                // 'supervisor_id'  => اختياري لاحقًا
            ]);

            // المالك عضو بدور owner
            ProjectMember::create([
                'project_id' => $project->id,
                'student_id' => $owner->id,
                'role'       => 'owner',
            ]);

            // صف المستودع
            Repository::create([
                'project_id'  => $project->id,
                'github_url'  => $repoUrl,
                'repo_name'   => $repoName,
                'description' => $repoResponse['description'] ?? null,
                'stars'       => $repoResponse['stargazers_count'] ?? 0,
                'forks'       => $repoResponse['forks_count'] ?? 0,
                'open_issues' => $repoResponse['open_issues_count'] ?? 0,
            ]);

            // تنزيل ZIP ثم فكّه
            Storage::makeDirectory('private/zips');
            $zipPath = storage_path("app/private/zips/project_{$project->id}.zip");
            $this->downloadRepoZip($ghUser, $ghRepo, $defaultBranch, $zipPath);
            $this->extractZipToProject($project->id, $zipPath);

            // دعوات PENDING
            foreach ($inviteeIds as $sid) {
                TeamInvitation::firstOrCreate(
                    ['project_id' => $project->id, 'to_student_id' => $sid, 'status' => 'pending'],
                    ['invited_by_user_id' => $owner->user_id] // مرسل الدعوة: user المرتبط بالطالب
                );
            }
        });

        return $this->ok(
            $request,
            '✅ Project created, ZIP downloaded and extracted! Invitations sent.',
            route('dashboard.student')
        );
    }

    /** مشاريع المشرف المقبولة لديه (يربط projects.supervisor_id → supervisors.id) */
    public function acceptedProjects(Request $request)
    {
        $me = Auth::user();
        if ($me?->role !== 'supervisor') {
            return $this->fail($request, 403, 'Access denied.');
        }

        $supervisorModel = $me->supervisor; // علاقة user → supervisor
        if (!$supervisorModel) {
            return $this->fail($request, 422, 'Supervisor profile not found.');
        }

        $projects = Project::with(['owner.user','students.user'])
            ->where('supervisor_id', $supervisorModel->id)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $projects], 200);
        }

        return view('supervisor.accepted-projects', compact('projects'));
    }

    /** تحليل السونار وحفظ النتائج */
    public function analyze(Request $request, $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            return $this->fail($request, 403, '❌ Access denied. Supervisors only.');
        }

        $project = Project::findOrFail($id);

        // تأكد أن الملفات مفكوكة
        $this->ensureExtractedIfNeeded($project->id);
        $finalExtractPath = storage_path("app/projects/project_{$project->id}");

        // sonar-project.properties
        $props = <<<EOL
        sonar.projectKey=project_{$project->id}
        sonar.projectName={$project->title}
        sonar.projectVersion=1.0
        sonar.sources=.
        sonar.sourceEncoding=UTF-8
        sonar.inclusions=**/*.php
        sonar.exclusions=vendor/**,node_modules/**,storage/**,bootstrap/**,public/**,tests/**
        sonar.scm.disabled=true
        sonar.host.url=http://localhost:9000
        sonar.token=squ_cfa867d438a1c77f4faed40ef162cf348b460374
        EOL;

        file_put_contents("{$finalExtractPath}/sonar-project.properties", $props);

        // تشغيل sonar-scanner (Windows مثال)
        $env = [
            'JAVA_HOME' => 'C:\Program Files\Java\jdk-17',
            'TEMP'      => 'C:\Users\HP\AppData\Local\Temp',
            'TMP'       => 'C:\Users\HP\AppData\Local\Temp',
            'PATH'      => 'C:\Program Files\Java\jdk-17\bin;' . getenv('PATH'),
        ];
        $process = new Process(
            ['C:\sonar-scanner-4.3.0.2102-windows\bin\sonar-scanner.bat'],
            $finalExtractPath,
            $env
        );
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error("❌ SonarQube analysis failed: " . $process->getErrorOutput());
            return $this->fail($request, 500, '❌ SonarQube analysis failed: ' . $process->getErrorOutput());
        }

        // جلب النتائج من خدمة مخصّصة
        $service = new \App\Services\SonarQubeService();
        $results = $service->analyzeProject("project_{$project->id}");
        if (!$results) {
            return $this->fail($request, 422, '❌ Failed to fetch SonarQube analysis results.');
        }

        CodeAnalysisReport::updateOrCreate(['project_id' => $project->id], $results);

        return $this->ok(
            $request,
            '✅ Code analyzed and saved!',
            route('dashboard.supervisor')
        );
    }

    // =========================
    // Helpers
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


    private function extractRepoName(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        $segments = explode('/', $path);
        return (string) end($segments);
    }

    private function parseGitHubUrl(string $url): ?array
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        return count($segments) >= 2 ? ['user' => $segments[0], 'repo' => $segments[1]] : null;
    }

    private function looksLikeZip(string $path): bool
    {
        if (!file_exists($path) || filesize($path) < 4) return false;
        $fh = fopen($path, 'rb');
        $sig = fread($fh, 2);
        fclose($fh);
        return $sig === "PK";
    }

    private function pickRootDir(string $path): ?string
    {
        $items = array_values(array_filter(scandir($path), function ($e) use ($path) {
            if ($e === '.' || $e === '..') return false;
            if (strpos($e, '__MACOSX') === 0) return false;
            if (strpos($e, '.') === 0) return false; // .DS_Store أو مخفية
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

    private function downloadRepoZip(string $owner, string $repo, string $ref, string $destPath): void
    {
        $headers = [
            'User-Agent' => 'GitEvalAI',
            'Accept'     => 'application/zip',
        ];
        if ($token = env('GITHUB_TOKEN')) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        $candidates = [
            "https://codeload.github.com/{$owner}/{$repo}/zip/refs/heads/{$ref}",
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

                $size = @filesize($destPath) ?: 0;
                if ($size > 1024 && $this->looksLikeZip($destPath)) {
                    $ok = true;
                    break;
                } else {
                    $head = @file_get_contents($destPath, false, null, 0, 120);
                    Log::warning("Bad ZIP content from {$url} (size={$size}). Head=" . substr((string)$head, 0, 120));
                }
            } catch (\Throwable $e) {
                Log::warning("ZIP download exception {$url}: ".$e->getMessage());
            }
        }

        if (!$ok) {
            if (file_exists($destPath)) @unlink($destPath);
            throw new \Exception('❌ Failed to download a valid ZIP (rate limit/redirect?). Add/verify GITHUB_TOKEN in .env');
        }
    }

    private function extractZipToProject(int $projectId, string $zipPath): void
    {
        $tmpExtractPath   = storage_path("app/projects/tmp_project_{$projectId}");
        $finalExtractPath = storage_path("app/projects/project_{$projectId}");

        if (!is_dir($tmpExtractPath)) mkdir($tmpExtractPath, 0777, true);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \Exception("❌ Failed to open ZIP for project {$projectId}");
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

        // (اختياري) احذف الـ ZIP لتوفير مساحة
        // @unlink($zipPath);

        Log::info("✅ Project {$projectId} extracted to {$finalExtractPath}");
    }

    private function ensureExtractedIfNeeded(int $projectId): void
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
    // ------- بقية CRUD placeholders --------
    public function evaluate($id) { return "📝 Evaluation for project ID {$id}"; }
    public function show($id) { /* ... */ }
    public function update(Request $request, $id) { /* ... */ }
    public function destroy($id) { /* ... */ }
}
