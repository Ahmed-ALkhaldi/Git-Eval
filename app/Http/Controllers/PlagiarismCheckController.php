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
use App\Services\MossService;
use ZipArchive;
use Carbon\Carbon;

class PlagiarismCheckController extends ProjectController
{
    //Choos project to compare with
    public function plagiarism($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            abort(403, '❌ Access denied. Supervisors only.');
        }

        // المشروع الأساسي
        $project1 = Project::findOrFail($id);

        // جميع المشاريع الأخرى باستثناء المشروع المحدد
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

        // تأكد من فك الضغط
        $this->ensureProjectExtracted($project1->id);
        $this->ensureProjectExtracted($project2->id);

        $dir1 = storage_path("app/projects/project_{$project1->id}");
        $dir2 = storage_path("app/projects/project_{$project2->id}");

        \Log::info("🔍 Starting plagiarism check using MOSS for: $dir1 vs $dir2");

        $moss = new \App\Services\MossService();
        $result = $moss->compareProjects($dir1, $dir2);

        if (!$result) {
            \Log::error('❌ MOSS comparison failed, no results were generated.');
            return back()->with('error', '❌ Failed to generate plagiarism report. Please try again.');
        }

        $report = \App\Models\PlagiarismCheck::create([
            'project1_id' => $project1->id,
            'project2_id' => $project2->id,
            'similarity_percentage' => $result['average_similarity'],
            'matches' => json_encode($result['details']),
            'report_url'            => $result['report_url'] ?? null,
        ]);

        \Log::info("✅ Plagiarism report successfully saved. Redirecting to report ID {$report->id}");

        return redirect()->route('projects.plagiarism.report', $report->id)
            ->with('success', '✅ Plagiarism report generated successfully.');
    }

    public function viewPlagiarismReport($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'supervisor') {
            abort(403, '❌ Access denied. Supervisors only.');
        }

        $report = \App\Models\PlagiarismCheck::findOrFail($id);

        return view('supervisor.plagiarism-result', [
            'report' => $report,
            'matches' => json_decode($report->matches, true),
        ]);
    }

}
