<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Http, Storage, Log, File};
use App\Models\{User, Project, Repository, CodeAnalysisReport};
use App\Services\SonarQubeService;
use App\Services\MossService;
use ZipArchive;
use Carbon\Carbon;

class ProjectController extends Controller
{
    public function index() {
        // TODO: List all projects (for supervisor or admin)
    }

    public function create(){
        $students = User::where('role', 'student')->get();
        return view('projects.create', compact('students'));
    }

    public function store(Request $request)
    {
        set_time_limit(360);

        if (!Auth::check() || Auth::user()->role !== 'student') {
            abort(403, 'Only students can create projects.');
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'github_url' => 'required|url',
            'students' => 'nullable|array',
            'students.*' => 'exists:users,id',
        ]);

        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'student_id' => Auth::id(),
        ]);

        $repoUrl  = $request->github_url;
        $repoName = $this->extractRepoName($repoUrl);
        $parsed   = $this->parseGitHubUrl($repoUrl);

        $repoResponse = Http::withHeaders(['User-Agent' => 'Laravel'])
            ->timeout(60)
            ->get("https://api.github.com/repos/{$parsed['user']}/{$parsed['repo']}");

        if (!$repoResponse->ok()) {
            return back()->with('error', '❌ Failed to fetch GitHub repository info.');
        }

        Repository::create([
            'project_id'   => $project->id,
            'github_url'   => $repoUrl,
            'repo_name'    => $repoName,
            'description'  => $repoResponse['description'] ?? null,
            'stars'        => $repoResponse['stargazers_count'] ?? 0,
            'forks'        => $repoResponse['forks_count'] ?? 0,
            'open_issues'  => $repoResponse['open_issues_count'] ?? 0,
        ]);

        // تنزيل ZIP بشكل موثوق + فك الضغط
        $defaultBranch = $repoResponse['default_branch'] ?? 'main';
        $zipFileName   = "project_{$project->id}.zip";
        $zipPath       = storage_path("app/private/zips/{$zipFileName}");
        Storage::makeDirectory('private/zips');

        $this->downloadRepoZip($parsed['user'], $parsed['repo'], $defaultBranch, $zipPath);
        $this->extractZipToProject($project->id, $zipPath);

        // إرفاق الطلاب
        $studentIds   = $request->students ?? [];
        $studentIds[] = Auth::id();
        $project->students()->attach($studentIds);

        return redirect()->route('dashboard.student')
            ->with('success', '✅ Project created, ZIP downloaded and extracted!');
    }

    public function acceptedProjects(){
        $supervisor = Auth::user();

        if ($supervisor->role !== 'supervisor') {
            abort(403, 'Access denied.');
        }

        $projects = Project::where('supervisor_id', $supervisor->id)->get();
        return view('supervisor.accepted-projects', compact('projects'));
    }

    public function analyze($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            abort(403, '❌ Access denied. Supervisors only.');
        }

        $service2 = new SonarQubeService();
        if (!$service2->isSonarQubeRunning()) {
            Log::error('❌ SonarQube is not running. Please start the server at http://localhost:9000');
            throw new \Exception('SonarQube is not running. Please start the server at http://localhost:9000');
        }

        $project = Project::findOrFail($id);

        // ✅ تأكد أن المشروع مفكوك (لو zip غير موجود/تالف لن نعيد التنزيل هنا)
        $this->ensureExtractedIfNeeded($project->id);

        $finalExtractPath = storage_path("app/projects/project_{$project->id}");

        // 🔹 إنشاء sonar-project.properties
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

        // 🔹 تشغيل sonar-scanner
        $env = [
            'JAVA_HOME' => 'C:\Program Files\Java\jdk-17',
            'TEMP' => 'C:\Users\HP\AppData\Local\Temp',
            'TMP'  => 'C:\Users\HP\AppData\Local\Temp',
            'PATH' => 'C:\Program Files\Java\jdk-17\bin;' . getenv('PATH'),
        ];
        $process = new \Symfony\Component\Process\Process(
            ['C:\sonar-scanner-4.3.0.2102-windows\bin\sonar-scanner.bat'],
            $finalExtractPath,
            $env
        );
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error("❌ SonarQube analysis failed: " . $process->getErrorOutput());
            return back()->with('error', '❌ SonarQube analysis failed: ' . $process->getErrorOutput());
        }

        // 🔹 جلب النتائج من الخدمة
        $service = new SonarQubeService();
        $results = $service->analyzeProject("project_{$project->id}");

        if (!$results) {
            return back()->with('error', '❌ Failed to fetch SonarQube analysis results.');
        }

        // 🔹 حفظ النتائج
        CodeAnalysisReport::updateOrCreate(['project_id' => $project->id], $results);

        return redirect()->route('dashboard.supervisor')->with('success', '✅ Code analyzed and saved!');
    }

    // =========================
    // Helpers
    // =========================

    private function extractRepoName($url){
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        $segments = explode('/', $path);
        return end($segments);
    }

    private function parseGitHubUrl($url){
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

    private function downloadRepoZip(string $owner, string $repo, string $ref, string $destPath): void
    {
        $token = env('GITHUB_TOKEN');
        $headers = [
            'User-Agent' => 'GitEvalAI',
            // ملاحظة: zipball يُرجع ZIP حتى بدون هذا الـ Accept
            'Accept'     => 'application/zip',
        ];
        if ($token) {
            // كلا الشكلين مقبولان لدى GitHub؛ نستخدم Bearer للأحدث
            $headers['Authorization'] = "Bearer {$token}";
        }

        // جرّب codeload أولاً (يرجع ZIP مباشر من غير HTML)
        $candidates = [
            "https://codeload.github.com/{$owner}/{$repo}/zip/refs/heads/{$ref}",     // 1) أفضل خيار
            "https://api.github.com/repos/{$owner}/{$repo}/zipball/{$ref}",           // 2) API zipball
            "https://github.com/{$owner}/{$repo}/archive/refs/heads/{$ref}.zip",      // 3) أرشيف مع ريديركت
        ];

        $ok = false;
        foreach ($candidates as $url) {
            try {
                $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)
                    ->timeout(120)
                    ->withOptions(['allow_redirects' => true, 'stream' => true])
                    ->get($url);

                $status = $resp->status();
                $rem    = $resp->header('X-RateLimit-Remaining');
                $rid    = $resp->header('X-GitHub-Request-Id');

                if (!$resp->ok()) {
                    Log::warning("ZIP GET failed {$url}: HTTP {$status} (RateRemaining={$rem}, ReqId={$rid})");
                    continue;
                }

                // اكتب ستريميًا
                $stream = fopen($destPath, 'w+b');
                foreach ($resp->toPsrResponse()->getBody() as $chunk) {
                    fwrite($stream, $chunk);
                }
                fclose($stream);
                clearstatcache(true, $destPath);

                $size = @filesize($destPath) ?: 0;

                // تحقّق أنه فعلاً ZIP (يبدأ بـ PK)
                if ($size > 1024 && $this->looksLikeZip($destPath)) {
                    $ok = true;
                    Log::info("ZIP downloaded OK from {$url} (size={$size}, RateRemaining={$rem}, ReqId={$rid})");
                    break;
                } else {
                    // اطبع 80 بايت الأولى لنفهم إذا HTML/JSON
                    $head = @file_get_contents($destPath, false, null, 0, 120);
                    Log::warning("Bad ZIP content from {$url} (size={$size}). Head=".substr((string)$head, 0, 120));
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

        if (!(file_exists($finalExtractPath) && count(glob("$finalExtractPath/*")))) {
            if (!file_exists($tmpExtractPath)) mkdir($tmpExtractPath, 0777, true);

            $zip = new ZipArchive;
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($tmpExtractPath);
                $zip->close();
            } else {
                throw new \Exception("❌ Failed to extract ZIP for project {$projectId}");
            }

            $entries   = array_values(array_diff(scandir($tmpExtractPath), ['.', '..']));
            $subfolder = $entries[0] ?? null;
            if (!$subfolder || !is_dir("{$tmpExtractPath}/{$subfolder}")) {
                throw new \Exception("❌ Unexpected ZIP structure for project {$projectId}");
            }

            if (file_exists($finalExtractPath)) File::deleteDirectory($finalExtractPath);
            mkdir($finalExtractPath, 0777, true);

            File::copyDirectory("{$tmpExtractPath}/{$subfolder}", $finalExtractPath);
            File::deleteDirectory($tmpExtractPath);

            Log::info("✅ Project {$projectId} extracted to {$finalExtractPath}");
        }
    }

    private function ensureExtractedIfNeeded(int $projectId): void
    {
        $zipPath          = storage_path("app/private/zips/project_{$projectId}.zip");
        $finalExtractPath = storage_path("app/projects/project_{$projectId}");
        if (file_exists($finalExtractPath) && count(glob("$finalExtractPath/*"))) {
            return;
        }
        if (!file_exists($zipPath) || !$this->looksLikeZip($zipPath)) {
            throw new \Exception("❌ ZIP not found or invalid for project {$projectId}");
        }
        $this->extractZipToProject($projectId, $zipPath);
    }

    public function evaluate($id) {
        // TODO: حساب معدل التقييم وعرضه
        return "📝 Evaluation for project ID {$id}";
    }

    public function show($id) {
        // TODO: Get project details
    }

    public function update(Request $request, $id) {
        // TODO: Update project
    }

    public function destroy($id) {
        // TODO: Delete project
    }
}




