<?php

use App\Models\Project;
use Illuminate\Support\Facades\Http;

class GitHubRepositoryController extends Controller
{
    public function fetch(Project $project)
    {
        $url = $project->repository->github_url;

        $parts = $this->parseGitHubUrl($url);
        if (!$parts) {
            return response()->json(['error' => 'Invalid GitHub URL'], 422);
        }

        $username = $parts['user'];
        $repo = $parts['repo'];

        $response = Http::get("https://api.github.com/repos/{$username}/{$repo}");

        if ($response->failed()) {
            return response()->json(['error' => 'GitHub API fetch failed'], 500);
        }

        $data = $response->json();

        // حفظ البيانات داخل السجل المرتبط
        $project->repository->update([
            'description' => $data['description'] ?? null,
            'stars' => $data['stargazers_count'] ?? 0,
            'forks' => $data['forks_count'] ?? 0,
            'open_issues' => $data['open_issues_count'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Repository data fetched and saved successfully.',
            'data' => $project->repository
        ]);
    }

    private function parseGitHubUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        return count($segments) >= 2 ? ['user' => $segments[0], 'repo' => $segments[1]] : null;
    }
}

