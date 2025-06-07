<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Project;
use App\Models\Repository;


class ProjectController extends Controller
{
    public function index() {

    } // List all projects (for supervisor or admin)

    public function create(){
        return view('projects.create');
    }

    public function store(Request $request) {
        if (!Auth::check() || Auth::user()->role !== 'student') {
            abort(403, 'Only students can create projects.');
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'github_url' => 'required|url',
        ]);

        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'student_id' => Auth::id(),
        ]);

        // استخراج اسم المستودع من الرابط
        $repoUrl = $request->github_url;
        $repoPath = explode('/', trim(parse_url($repoUrl, PHP_URL_PATH), '/'));
        $repoName = $repoPath[1] ?? null;

        Repository::create([
            'project_id' => $project->id,
            'github_url' => $repoUrl,
            'repo_name' => $repoName,
        ]);

        return redirect()->route('dashboard.student')->with('success', 'Project created successfully!');
    }

 // Create project

    public function show($id) {
        
    } // Get project details

    public function update(Request $request, $id) {
        
    }
    
    public function destroy($id) {
        
    }

}
