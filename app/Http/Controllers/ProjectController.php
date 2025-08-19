<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Project;
use App\Models\Repository;
use App\Models\commits;
use App\Models\CodeAnalysisReport;
use App\Models\PlagiarismCheck;
use App\Services\SonarQubeService;
use App\Services\MossService;
use ZipArchive;
use Carbon\Carbon;



class ProjectController extends Controller
{
    

    public function index() {

    } // List all projects (for supervisor or admin)

    public function create(){
        $students = User::where('role', 'student')->get();
        return view('projects.create', compact('students'));
    }


    public function store(Request $request)
    {
        set_time_limit(180);

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

        $repoUrl = $request->github_url;
        $repoName = $this->extractRepoName($repoUrl);
        $parsed = $this->parseGitHubUrl($repoUrl);

        $repoResponse = Http::withHeaders(['User-Agent' => 'Laravel'])
            ->get("https://api.github.com/repos/{$parsed['user']}/{$parsed['repo']}");

        if (!$repoResponse->ok()) {
            return redirect()->back()->with('error', '❌ Failed to fetch GitHub repository info.');
        }

        $repository = Repository::create([
            'project_id' => $project->id,
            'github_url' => $repoUrl,
            'repo_name' => $repoName,
            'description' => $repoResponse['description'] ?? null,
            'stars' => $repoResponse['stargazers_count'] ?? 0,
            'forks' => $repoResponse['forks_count'] ?? 0,
            'open_issues' => $repoResponse['open_issues_count'] ?? 0,
        ]);

        // تحميل ملف ZIP فقط
        $defaultBranch = $repoResponse['default_branch'] ?? 'main';
        $zipUrl = "https://github.com/{$parsed['user']}/{$parsed['repo']}/archive/refs/heads/{$defaultBranch}.zip";
        $zipFileName = "project_{$project->id}.zip";
        $zipContents = @file_get_contents($zipUrl);

        if (!$zipContents) {
            return redirect()->back()->with('error', '❌ Failed to download the GitHub ZIP archive.');
        }

        Storage::put("zips/{$zipFileName}", $zipContents);

        $studentIds = $request->students ?? [];
        $studentIds[] = Auth::id();
        $project->students()->attach($studentIds);

        return redirect()->route('dashboard.student')->with('success', '✅ Project created and ZIP downloaded!');
    }


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


    public function acceptedProjects(){
        $supervisor = Auth::user();

        // تحقق من كونه مشرفًا
        if ($supervisor->role !== 'supervisor') {
            abort(403, 'Access denied.');
        }

        $projects = Project::where('supervisor_id', $supervisor->id)->get();

        return view('supervisor.accepted-projects', compact('projects'));
    }


    private function ensureProjectExtracted($projectId)
    {
        // 📦 مسار ملف الـ ZIP (تم حفظه في storage/app/private/zips)
        $zipPath = storage_path("app/private/zips/project_{$projectId}.zip");

        // 🗂️ المسارات المؤقتة والنهائية
        $tmpExtractPath = storage_path("app/projects/tmp_project_{$projectId}");
        $finalExtractPath = storage_path("app/projects/project_{$projectId}");

        // ✅ إذا كان المشروع مستخرج مسبقًا، لا حاجة لإعادة الفك
        if (file_exists($finalExtractPath) && count(glob("$finalExtractPath/*"))) {
            \Log::info("✅ Project {$projectId} already extracted.");
            return;
        }

        // ❌ تحقق من وجود الملف ZIP
        if (!file_exists($zipPath)) {
            throw new \Exception("❌ ZIP archive not found for project {$projectId}");
        }

        // 🗂️ أنشئ مجلد مؤقت إن لم يكن موجودًا
        if (!file_exists($tmpExtractPath)) {
            mkdir($tmpExtractPath, 0777, true);
        }

        // 🔓 فك الضغط
        $zip = new \ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tmpExtractPath);
            $zip->close();
        } else {
            throw new \Exception("❌ Failed to extract ZIP for project {$projectId}");
        }

        // 📂 تحقق من البنية: احصل على المجلد الرئيسي المستخرج
        $entries = array_values(array_diff(scandir($tmpExtractPath), ['.', '..']));
        $subfolder = $entries[0] ?? null;

        if (!$subfolder || !is_dir("{$tmpExtractPath}/{$subfolder}")) {
            throw new \Exception("❌ Unexpected ZIP structure for project {$projectId}");
        }

        // 🗑️ احذف أي نسخة سابقة للمجلد النهائي
        if (file_exists($finalExtractPath)) {
            \File::deleteDirectory($finalExtractPath);
        }

        mkdir($finalExtractPath, 0777, true);

        // ✅ انسخ الملفات إلى المجلد النهائي
        \File::copyDirectory("{$tmpExtractPath}/{$subfolder}", $finalExtractPath);

        // 🧹 احذف المجلد المؤقت
        \File::deleteDirectory($tmpExtractPath);

        \Log::info("✅ Project {$projectId} extracted to {$finalExtractPath}");
    }



    public function analyze($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            abort(403, '❌ Access denied. Supervisors only.');
        }

        $service2 = new \App\Services\SonarQubeService();
        if (!$service2->isSonarQubeRunning()) {
            \Log::error('❌ SonarQube is not running. Please start the server at http://localhost:9000');
            throw new \Exception('SonarQube is not running. Please start the server at http://localhost:9000');
        }

        $project = Project::findOrFail($id);

        // ✅ تأكد أن المشروع مفكوك
        $this->ensureProjectExtracted($project->id);

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
            'TMP' => 'C:\Users\HP\AppData\Local\Temp',
            'PATH' => 'C:\Program Files\Java\jdk-17\bin;' . getenv('PATH'),
        ];
        $process = new \Symfony\Component\Process\Process(['C:\sonar-scanner-4.3.0.2102-windows\bin\sonar-scanner.bat'], $finalExtractPath, $env);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            \Log::error("❌ SonarQube analysis failed: " . $process->getErrorOutput());
            return back()->with('error', '❌ SonarQube analysis failed: ' . $process->getErrorOutput());
        }

        // 🔹 جلب النتائج من الخدمة
        $service = new \App\Services\SonarQubeService();
        $results = $service->analyzeProject("project_{$project->id}");

        if (!$results) {
            return back()->with('error', '❌ Failed to fetch SonarQube analysis results.');
        }

        // 🔹 حفظ النتائج
        CodeAnalysisReport::updateOrCreate(['project_id' => $project->id], $results);

        return redirect()->route('dashboard.supervisor')->with('success', '✅ Code analyzed and saved!');
    }


    public function evaluate($id) {
        // TODO: حساب معدل التقييم وعرضه
        return "📝 Evaluation for project ID {$id}";
    }


    // //Choos project to compare with
    // public function plagiarism($id)
    // {
    //     if (!Auth::check() || Auth::user()->role !== 'supervisor') {
    //         abort(403, '❌ Access denied. Supervisors only.');
    //     }

    //     // المشروع الأساسي
    //     $project1 = Project::findOrFail($id);

    //     // جميع المشاريع الأخرى باستثناء المشروع المحدد
    //     $otherProjects = Project::where('id', '!=', $id)->get();

    //     return view('supervisor.plagiarism_select', compact('project1', 'otherProjects'));
    // }

    // public function checkPlagiarism(Request $request)
    // {
        
    //     $request->validate([
    //         'project1_id' => 'required|different:project2_id|exists:projects,id',
    //         'project2_id' => 'required|exists:projects,id',
    //     ]);

    //     $project1 = Project::findOrFail($request->project1_id);
    //     $project2 = Project::findOrFail($request->project2_id);

    //     // تأكد من فك الضغط
    //     $this->ensureProjectExtracted($project1->id);
    //     $this->ensureProjectExtracted($project2->id);

    //     $dir1 = storage_path("app/projects/project_{$project1->id}");
    //     $dir2 = storage_path("app/projects/project_{$project2->id}");

    //     \Log::info("🔍 Starting plagiarism check using MOSS for: $dir1 vs $dir2");

    //     $moss = new \App\Services\MossService();
    //     $result = $moss->compareProjects($dir1, $dir2);

    //     if (!$result) {
    //         \Log::error('❌ MOSS comparison failed, no results were generated.');
    //         return back()->with('error', '❌ Failed to generate plagiarism report. Please try again.');
    //     }

    //     $report = \App\Models\PlagiarismCheck::create([
    //         'project1_id' => $project1->id,
    //         'project2_id' => $project2->id,
    //         'similarity_percentage' => $result['average_similarity'],
    //         'matches' => json_encode($result['details']),
    //         'report_url'            => $result['report_url'] ?? null,
    //     ]);

    //     \Log::info("✅ Plagiarism report successfully saved. Redirecting to report ID {$report->id}");

    //     return redirect()->route('projects.plagiarism.report', $report->id)
    //         ->with('success', '✅ Plagiarism report generated successfully.');
    // }

    // public function viewPlagiarismReport($id)
    // {
    //     if (!Auth::check() || Auth::user()->role !== 'supervisor') {
    //         abort(403, '❌ Access denied. Supervisors only.');
    //     }

    //     $report = \App\Models\PlagiarismCheck::findOrFail($id);

    //     return view('supervisor.plagiarism-result', [
    //         'report' => $report,
    //         'matches' => json_decode($report->matches, true),
    //     ]);
    // }

    







 // Create project

    public function show($id) {
        
    } // Get project details

    public function update(Request $request, $id) {
        
    }
    
    public function destroy($id) {
        
    }

}
