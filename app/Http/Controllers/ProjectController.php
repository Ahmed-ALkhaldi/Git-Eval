<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index() {

    } // List all projects (for supervisor or admin)

    public function create(){
        return view('projects.create');
    }

    public function store(Request $request){
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'repository_url' => 'required|url',
        ]);

        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'student_id' => Auth::id(),
        ]);

        // إنشاء مستودع مرتبط بالمشروع
        Repository::create([
            'project_id' => $project->id,
            'url' => $request->repository_url,
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
