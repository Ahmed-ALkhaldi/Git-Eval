<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log, File, Http, Storage};
use App\Models\{Project, PlagiarismCheck, Repository};
use ZipArchive;

class PlagiarismCheckController extends Controller
{
    // تأكد من وجود الملفات مفكوكة للمشروع (مع fallback لتنزيل ZIP إن مفقود/تالف)
    protected function ensureExtracted(int $projectId): void
    {
        $zipPath          = storage_path("app/private/zips/project_{$projectId}.zip");
        $tmpExtractPath   = storage_path("app/projects/tmp_project_{$projectId}");
        $finalExtractPath = storage_path("app/projects/project_{$projectId}");

        if (file_exists($finalExtractPath) && count(glob("$finalExtractPath/*"))) {
            Log::info("✅ Project {$projectId} already extracted.");
            return;
        }

        // لو الـ ZIP غير موجود/تالف: حاول إعادة تنزيله من قاعدة البيانات
        if (!file_exists($zipPath) || !$this->looksLikeZip($zipPath)) {
            $repo = Repository::where('project_id', $projectId)->first();
            if (!$repo) throw new \Exception("❌ No repository row for project {$projectId}");

            $parsed = $this->parseGitHubUrl($repo->github_url);
            $owner  = $parsed['user'] ?? null;
            $name   = $parsed['repo'] ?? null;
            if (!$owner || !$name) throw new \Exception("❌ Bad GitHub URL for project {$projectId}");

            $defaultBranch = 'main';
            try {
                $res = Http::withHeaders(['User-Agent' => 'GitEvalAI'])
                    ->timeout(60)
                    ->get("https://api.github.com/repos/{$owner}/{$name}");
                if ($res->ok()) $defaultBranch = $res['default_branch'] ?? 'main';
            } catch (\Throwable $e) {
                Log::warning("Fetch default_branch failed: ".$e->getMessage());
            }

            Storage::makeDirectory('private/zips');
            $this->downloadRepoZip($owner, $name, $defaultBranch, $zipPath);
        }

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

    public function plagiarism($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            abort(403, '❌ Access denied. Supervisors only.');
        }

        $project1      = Project::findOrFail($id);
        $otherProjects = Project::where('id', '!=', $id)->get();

        return view('supervisor.plagiarism_select', compact('project1', 'otherProjects'));
    }

    public function checkPlagiarism(Request $request)
    {
        $request->validate([
            'project1_id' => 'required|different:project2_id|exists:projects,id',
            'project2_id' => 'required|exists:projects,id',
        ]);

        $project1 = Project::findOrFail($request->project1_id);
        $project2 = Project::findOrFail($request->project2_id);

        // فقط تأكد من الفك — لا تنادِي store()
        $this->ensureExtracted($project1->id);
        $this->ensureExtracted($project2->id);

        $dir1 = storage_path("app/projects/project_{$project1->id}");
        $dir2 = storage_path("app/projects/project_{$project2->id}");

        Log::info("🔍 Starting plagiarism check using MOSS for: $dir1 vs $dir2");

        $moss   = new \App\Services\MossService();
        $result = $moss->compareProjects($dir1, $dir2);

        if (!$result) {
            Log::error('❌ MOSS comparison failed, no results were generated.');
            return back()->with('error', '❌ Failed to generate plagiarism report. Please try again.');
        }

        $report = PlagiarismCheck::create([
            'project1_id'           => $project1->id,
            'project2_id'           => $project2->id,
            'similarity_percentage' => $result['average_similarity'] ?? null,
            'matches'               => json_encode($result['details'] ?? []),
            'report_url'            => $result['report_url'] ?? null,
        ]);

        Log::info("✅ Plagiarism report saved. ID {$report->id}");

        return redirect()->route('projects.plagiarism.report', $report->id)
            ->with('success', '✅ Plagiarism report generated successfully.');
    }

    public function viewPlagiarismReport($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            abort(403, '❌ Access denied. Supervisors only.');
        }

        $report = PlagiarismCheck::findOrFail($id);

        return view('supervisor.plagiarism-result', [
            'report'  => $report,
            'matches' => json_decode($report->matches, true),
        ]);
    }

    // =========================
    // Helpers (مكررة هنا لتكون الكلاس مستقل)
    // =========================
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
        $headers = [
            'User-Agent' => 'GitEvalAI',
            'Accept'     => 'application/zip',
        ];
        if ($token = env('GITHUB_TOKEN')) {
            $headers['Authorization'] = "token {$token}";
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
                    Log::warning("ZIP download failed {$url}: HTTP {$resp->status()}");
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
                    Log::warning("Bad ZIP content from {$url} (size={$size}).");
                }
            } catch (\Throwable $e) {
                Log::warning("ZIP download exception {$url}: ".$e->getMessage());
            }
        }

        if (!$ok) {
            if (file_exists($destPath)) @unlink($destPath);
            throw new \Exception('❌ Failed to download a valid ZIP (rate limit/redirect?). Add GITHUB_TOKEN in .env');
        }
    }
}
