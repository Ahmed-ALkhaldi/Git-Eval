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


    public function store(Request $request){
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

        $repoResponse = null;
        $commitsResponse = null;

        if ($parsed) {
            $repoResponse = Http::withHeaders(['User-Agent' => 'Laravel'])
                ->get("https://api.github.com/repos/{$parsed['user']}/{$parsed['repo']}");
            $commitsResponse = Http::get("https://api.github.com/repos/{$parsed['user']}/{$parsed['repo']}/commits");
        }

        if (!$repoResponse || !$repoResponse->ok()) {
            return redirect()->back()->with('error', '❌ Failed to fetch GitHub repository info.');
        }

        $repoData = [
            'project_id' => $project->id,
            'github_url' => $repoUrl,
            'repo_name' => $repoName,
            'description' => $repoResponse['description'] ?? null,
            'stars' => $repoResponse['stargazers_count'] ?? 0,
            'forks' => $repoResponse['forks_count'] ?? 0,
            'open_issues' => $repoResponse['open_issues_count'] ?? 0,
        ];

        $repository = Repository::create($repoData);

        if ($commitsResponse && $commitsResponse->ok()) {
            foreach ($commitsResponse->json() as $commit) {
                commits::create([
                    'repository_id' => $repository->id,
                    'commit_sha' => $commit['sha'],
                    'author_name' => $commit['commit']['author']['name'],
                    'author_email' => $commit['commit']['author']['email'] ?? null,
                    'commit_date' => Carbon::parse($commit['commit']['author']['date'])->format('Y-m-d H:i:s'),
                    'message' => $commit['commit']['message'],
                ]);
            }
        }

        // ✅ تحميل ملف ZIP فقط (بدون فك الضغط)
        $defaultBranch = $repoResponse['default_branch'] ?? 'main';
        $zipUrl = "https://github.com/{$parsed['user']}/{$parsed['repo']}/archive/refs/heads/{$defaultBranch}.zip";
        $zipFileName = "project_{$project->id}.zip";
        $zipContents = @file_get_contents($zipUrl);

        if (!$zipContents) {
            return redirect()->back()->with('error', '❌ Failed to download the GitHub ZIP archive.');
        }

        Storage::put("sonarqube_zips/{$zipFileName}", $zipContents);

        // ربط الطلاب
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


    public function analyze($id)
    {

        
        $project = Project::findOrFail($id);
        //dd($project);
        // 1. فك الضغط من zip
        $zipFileName = "project_{$project->id}.zip";
        $zipPath = storage_path("app/sonarqube_zips/{$zipFileName}");
        $tmpExtractPath = storage_path("app/sonarqube_projects/tmp_project_{$project->id}");

        if (!file_exists($zipPath)) {
            Log::warning("❌ ZIP archive not found at path: $zipPath");
            return redirect()->route('supervisor.accepted-projects')->with('error', '❌ ZIP archive not found. Make sure the project was downloaded.');
        }
        
        if (!file_exists($tmpExtractPath)) {
            mkdir($tmpExtractPath, 0777, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tmpExtractPath);
            $zip->close();
        } else {
            return back()->with('error', '❌ Failed to extract ZIP archive.');
        }

        $entries = array_values(array_diff(scandir($tmpExtractPath), ['.', '..']));
        $subfolder = $entries[0] ?? null;

        if (!$subfolder || !is_dir("{$tmpExtractPath}/{$subfolder}")) {
            return back()->with('error', '❌ Unexpected ZIP structure.');
        }

        $finalExtractPath = storage_path("app/sonarqube_projects/project_{$project->id}");
        if (!file_exists($finalExtractPath)) {
            mkdir($finalExtractPath, 0777, true);
        }

        File::copyDirectory("{$tmpExtractPath}/{$subfolder}", $finalExtractPath);
        File::deleteDirectory($tmpExtractPath);

        // 2. إنشاء ملف sonar-project.properties
        $props = <<<EOL
        sonar.projectKey=project_{$project->id}
        sonar.projectName={$project->title}
        sonar.projectVersion=1.0
        sonar.sources=.
        sonar.language=php
        sonar.sourceEncoding=UTF-8
        sonar.php.exclusions=vendor/**,node_modules/**,storage/**
        sonar.host.url=http://localhost:9000
        sonar.login=squ_cfa867d438a1c77f4faed40ef162cf348b460374
        EOL;

        file_put_contents("{$finalExtractPath}/sonar-project.properties", $props);

        // 3. تنفيذ تحليل SonarQube
        $process = new Process(['sonar-scanner'], $finalExtractPath);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            return back()->with('error', '❌ SonarQube analysis failed: ' . $process->getErrorOutput());
        }

        // 4. جلب النتائج
        $sonarToken = 'squ_cfa867d438a1c77f4faed40ef162cf348b460374';
        $response = Http::withBasicAuth($sonarToken, '')
            ->get("http://localhost:9000/api/measures/component", [
                'component' => "project_{$project->id}",
                'metricKeys' => 'bugs,vulnerabilities,code_smells,coverage',
            ]);

        if ($response->failed() || !isset($response['component']['measures'])) {
            return back()->with('error', '❌ Failed to fetch analysis results.');
        }

        $measures = collect($response['component']['measures'])->keyBy('metric');

        CodeAnalysisReport::updateOrCreate(
            ['project_id' => $project->id],
            [
                'bugs' => (int) ($measures['bugs']['value'] ?? 0),
                'vulnerabilities' => (int) ($measures['vulnerabilities']['value'] ?? 0),
                'code_smells' => (int) ($measures['code_smells']['value'] ?? 0),
                'coverage' => isset($measures['coverage']['value']) ? floatval($measures['coverage']['value']) : null,
            ]
        );

        return redirect()->route('supervisor.accepted-projects')->with('success', '✅ Code analyzed and saved!');
    }


    public function plagiarism($id) {
        // TODO: استدعاء MOSS أو Codequiry لاحقاً
        return "🔎 Plagiarism check for project ID {$id}";
    }

    public function evaluate($id) {
        // TODO: حساب معدل التقييم وعرضه
        return "📝 Evaluation for project ID {$id}";
    }







 // Create project

    public function show($id) {
        
    } // Get project details

    public function update(Request $request, $id) {
        
    }
    
    public function destroy($id) {
        
    }

}
