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
        $students = User::where('role', 'student')->get(); // فقط الطلاب
        return view('projects.create', compact('students'));
    }


    public function store(Request $request) {
        if (!Auth::check() || Auth::user()->role !== 'student') {
            abort(403, 'Only students can create projects.');
        }

        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'github_url' => 'required|url',
            'students' => 'required|array',            // تأكد من إرسال قائمة طلاب
            'students.*' => 'exists:users,id',         // كل طالب يجب أن يكون موجودًا
        ]);

        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // استخراج اسم المستودع من رابط GitHub
        $repoUrl = $request->github_url;
        $repoPath = explode('/', trim(parse_url($repoUrl, PHP_URL_PATH), '/'));
        $repoName = $repoPath[1] ?? null;

        Repository::create([
            'project_id' => $project->id,
            'github_url' => $repoUrl,
            'repo_name' => $repoName,
        ]);

        // ربط الطلاب بالمشروع
        $project->students()->attach($request->students);

        return redirect()->route('dashboard.student')->with('success', 'Project created successfully!');
    }


    public function supervisorIndex(){
        // جلب المشاريع التي لا يوجد لها مشرف
        $projects = Project::whereNull('supervisor_id')->get();
        return view('supervisor.projects.index', compact('projects'));
    }

    public function approve($id){
        $project = Project::findOrFail($id);
        $project->supervisor_id = Auth::id();
        $project->save();

        return redirect()->route('supervisor.projects')->with('success', 'Project approved successfully.');
    }



 // Create project

    public function show($id) {
        
    } // Get project details

    public function update(Request $request, $id) {
        
    }
    
    public function destroy($id) {
        
    }

}
