<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamInvitationController extends Controller
{
    public function accept(TeamInvitation $invitation)
    {
        // الطالب الحالي
        $student = \App\Models\Student::where('user_id', Auth::id())->firstOrFail();

        // الدعوة تخصّ هذا الطالب؟ وهل لا تزال pending؟
        abort_unless($invitation->to_student_id === $student->id, 403);
        abort_unless($invitation->status === 'pending', 400, 'Invitation is not pending.');

        DB::transaction(function () use ($invitation, $student) {
            // أغلق الصف للتأكد من التزامن
            $project = $invitation->project()->with('students')->lockForUpdate()->firstOrFail();

            // إن لم يكن عضوًا بالفعل، أضِفه كـ member
            $exists = $project->students()->where('students.id', $student->id)->exists();
            if (!$exists) {
                $project->students()->attach($student->id, ['role' => 'member']);
            }

            // حدّث حالة الدعوة وتاريخ الرد
            $invitation->update([
                'status'       => 'accepted',
                'responded_at' => now(),
            ]);
        });

        return redirect()->route('student.dashboard')->with('status', 'Invitation accepted.');
    }

    public function decline(TeamInvitation $invitation)
    {
        $student = \App\Models\Student::where('user_id', Auth::id())->firstOrFail();

        abort_unless($invitation->to_student_id === $student->id, 403);
        abort_unless($invitation->status === 'pending', 400, 'Invitation is not pending.');

        $invitation->update([
            'status'       => 'declined',
            'responded_at' => now(),
        ]);

        return redirect()->route('student.dashboard')->with('status', 'Invitation declined.');
    }
}
