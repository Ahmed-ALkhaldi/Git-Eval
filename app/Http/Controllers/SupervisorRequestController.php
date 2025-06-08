<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Project;
use App\Models\SupervisorRequest;

class SupervisorRequestController extends Controller
{
    public function indexForStudent()
    {
        $supervisors = User::where('role', 'supervisor')->get();
        return view('requests.supervisors', compact('supervisors'));
    }

    public function sendRequest($supervisor_id)
    {
        $student_id = auth()->id();

        SupervisorRequest::create([
            'student_id' => $student_id,
            'supervisor_id' => $supervisor_id,
        ]);

        return redirect()->back()->with('success', 'Request sent to supervisor.');
    }

    public function indexForSupervisor()
    {
        $requests = SupervisorRequest::where('supervisor_id', auth()->id())->where('status', 'pending')->get();
        return view('requests.pending', compact('requests'));
    }

    public function respond($id, $action)
    {
        $request = SupervisorRequest::findOrFail($id);

        if ($request->supervisor_id != auth()->id()) {
            abort(403);
        }

        $request->status = $action === 'accept' ? 'accepted' : 'rejected';
        $request->save();

        // ✅ إذا تم قبول الطلب، ابحث عن مشروع الطالب وضع له المشرف
        if ($action === 'accept') {
            $project = Project::where('student_id', $request->student_id)->first();

            if ($project) {
                $project->supervisor_id = $request->supervisor_id;
                $project->save();
            }
        }

        return redirect()->back()->with('success', 'Request updated.');
    }


}
