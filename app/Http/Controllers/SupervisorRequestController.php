<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Project;
use App\Models\Supervisor;
use App\Models\SupervisorRequest;
use Illuminate\Support\Facades\Log;

class SupervisorRequestController extends Controller
{
    /** صفحة اختيار المشرفين (للطالب) */
    public function indexForStudent()
    {
        $supervisors = User::where('role', 'supervisor')
                          ->where('is_active', true)
                          ->get();
        return view('requests.supervisors', compact('supervisors'));
    }

    /**
     * إرسال طلب إشراف.
     * يقبل مفتاح المشرف سواء supervisors.id أو users.id (يُكتشف تلقائياً).
     */
    public function sendRequest($supervisorKey)
    {
        Log::info('sendRequest: entered', ['key' => $supervisorKey, 'user_id' => Auth::id()]);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user && $user->student, 403, 'Student profile not found.');
        $student = $user->student;

        // اكتشاف المشرف
        $supervisor = Supervisor::find($supervisorKey);
        if (!$supervisor) {
            $supervisor = Supervisor::where('user_id', $supervisorKey)->firstOrFail();
        }

        // التحقق من أن المشرف نشط
        $supervisorUser = $supervisor->user;
        if (!$supervisorUser || !$supervisorUser->is_active) {
            return back()->withErrors(['supervisor' => 'This supervisor is currently inactive and cannot accept new requests.']);
        }

        // عطّل أي طلب نشط سابق لنفس الطالب (إن وجد)
        SupervisorRequest::where('student_id', $student->id)
            ->where('is_active', true)
            ->update([
                'is_active'    => false,
                'status'       => 'rejected', // أو 'cancelled' حسب سياسة منتجك
                'responded_at' => now(),
            ]);

        // أنشئ/حدّث طلبًا نشطًا واحدًا
        SupervisorRequest::updateOrCreate(
            [
                'student_id' => $student->id,
                'is_active'  => true,
            ],
            [
                'supervisor_id' => $supervisor->id,
                'status'        => 'pending',
                'message'       => request('message'),
                'responded_at'  => null,
            ]
        );

        Log::info('sendRequest: saved/updated', [
        'student_id' => $student->id,
        'supervisor_id' => $supervisor->id,
        'count' => \App\Models\SupervisorRequest::where('student_id',$student->id)->count(),
        ]);

        return back()->with('success', 'تم إرسال طلب الإشراف بنجاح.');
    }

    /**
     * قائمة الطلبات المعلّقة للمشرف الحالي.
     * نحمّل أيضًا مشروع الطالب المالك (ownedProject) لعرض العنوان.
     */
    public function indexForSupervisor()
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'supervisor', 403);

        $supervisor = Supervisor::where('user_id', $user->id)->firstOrFail();

        $requests = SupervisorRequest::with([
                'student.user',
                // نفترض لديك علاقة ownedProject على Student:
                // Student::hasOne(Project, 'owner_student_id', 'id')
                'student.ownedProject',
            ])
            ->where('supervisor_id', $supervisor->id)
            ->where('status', 'pending')
            ->where('is_active', true)
            ->latest()
            ->get();

        return view('supervisor.pending-requests', compact('requests'));
    }

    /**
     * رد المشرف على الطلب (accept|reject).
     */
    public function respond($id, $action)
    {
        $requestModel = SupervisorRequest::findOrFail($id);

        $user = Auth::user();
        abort_unless($user && $user->role === 'supervisor', 403);

        $supervisor = Supervisor::where('user_id', $user->id)->firstOrFail();
        abort_if((int) $requestModel->supervisor_id !== (int) $supervisor->id, 403);

        $action = strtolower($action);
        abort_unless(in_array($action, ['accept','reject']), 400);

        if ($action === 'accept') {
            $requestModel->update([
                'status'       => 'accepted',
                'is_active'    => false,
                'responded_at' => now(),
            ]);

            // اربط مشروع الطالب بالمشرف (لو يوجد مشروع مملوك لهذا الطالب)
            $project = Project::where('owner_student_id', $requestModel->student_id)->first();
            if ($project) {
                $project->supervisor_id = $supervisor->id;
                $project->save();
            }

            // تعطيل أي طلبات نشطة أخرى لنفس الطالب
            SupervisorRequest::where('student_id', $requestModel->student_id)
                ->where('is_active', true)
                ->where('id', '!=', $requestModel->id)
                ->update([
                    'is_active'    => false,
                    'status'       => 'rejected',
                    'responded_at' => now(),
                ]);

        } else { // reject
            $requestModel->update([
                'status'       => 'rejected',
                'is_active'    => false,
                'responded_at' => now(),
            ]);
        }

        return back()->with('success', 'Request updated.');
    }
}
