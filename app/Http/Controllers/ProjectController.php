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
        $students = User::where('role', 'student')->get();
        return view('projects.create', compact('students'));
    }


    public function store(Request $request){
        // تأكد أن المستخدم مسجل دخوله وطالب
        if (!Auth::check() || Auth::user()->role !== 'student') {
            abort(403, 'Only students can create projects.');
        }

        // التحقق من صحة البيانات
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'github_url' => 'required|url',
            'students' => 'nullable|array',
            'students.*' => 'exists:users,id',
        ]);

        // إنشاء المشروع وربطه بصاحب المشروع (الطالب الحالي)
        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'student_id' => Auth::id(),
        ]);

        

        // إنشاء مستودع GitHub المرتبط بالمشروع
        Repository::create([
            'project_id' => $project->id,
            'github_url' => $request->github_url,
            'repo_name' => basename(parse_url($request->github_url, PHP_URL_PATH)), // استخراج الاسم من الرابط
        ]);

        // إعداد أعضاء الفريق: الطلاب الآخرين بالإضافة للطالب الحالي
        $studentIds = $request->students ?? [];
        $studentIds[] = Auth::id(); // أضف الطالب الحالي دائمًا

        // ربط الطلاب بالمشروع في الجدول الوسيط project_user
        $project->students()->attach($studentIds);

        // إعادة التوجيه لواجهة الطالب مع رسالة نجاح
        return redirect()->route('dashboard.student')->with('success', 'Project created with team members!');
    }

    private function extractRepoName($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        $segments = explode('/', $path);
        return end($segments);
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
