<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Project;
use App\Models\Repository;
use App\Models\commits;


class ProjectController extends Controller
{
    public function index() {

    } // List all projects (for supervisor or admin)

    public function create(){
        $students = User::where('role', 'student')->get();
        return view('projects.create', compact('students'));
    }


    public function store(Request $request){
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
            $repoResponse = Http::get("https://api.github.com/repos/{$parsed['user']}/{$parsed['repo']}");
            $commitsResponse = Http::get("https://api.github.com/repos/{$parsed['user']}/{$parsed['repo']}/commits");
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
                Commit::create([
                    'repository_id' => $repository->id,
                    'commit_sha' => $commit['sha'],
                    'author_name' => $commit['commit']['author']['name'],
                    'author_email' => $commit['commit']['author']['email'] ?? null,
                    'commit_date' => $commit['commit']['author']['date'],
                    'message' => $commit['commit']['message'],
                ]);
            }
        }

        $studentIds = $request->students ?? [];
        $studentIds[] = Auth::id();
        $project->students()->attach($studentIds);

        return redirect()->route('dashboard.student')->with('success', 'Project created and GitHub data + commits fetched!');
    }


    private function extractRepoName($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        $segments = explode('/', $path);
        return end($segments);
    }

    private function parseGitHubUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        return count($segments) >= 2 ? ['user' => $segments[0], 'repo' => $segments[1]] : null;
    }




    // public function supervisorIndex(){
    //     // جلب المشاريع التي لا يوجد لها مشرف
    //     $projects = Project::whereNull('supervisor_id')->get();
    //     return view('supervisor.projects.index', compact('projects'));
    // }

    // public function approve($id){
    //     $project = Project::findOrFail($id);
    //     $project->supervisor_id = Auth::id();
    //     $project->save();

    //     return redirect()->route('supervisor.projects')->with('success', 'Project approved successfully.');
    // }



 // Create project

    public function show($id) {
        
    } // Get project details

    public function update(Request $request, $id) {
        
    }
    
    public function destroy($id) {
        
    }

}
