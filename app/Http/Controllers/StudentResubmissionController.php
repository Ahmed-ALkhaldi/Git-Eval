<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;

class StudentResubmissionController extends Controller
{
    /**
     * عرض صفحة إعادة تقديم شهادة القيد للطلاب المرفوضين
     */
    public function show()
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'student') {
            abort(403, 'Only students can access this page.');
        }

        $student = $user->student;
        
        if (!$student->isRejected()) {
            return redirect()->route('student.dashboard')
                ->with('info', 'You do not need to resubmit your enrollment certificate.');
        }

        return view('student.resubmit-certificate', compact('student'));
    }

    /**
     * معالجة إعادة تقديم شهادة القيد
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'student') {
            abort(403, 'Only students can resubmit certificates.');
        }

        $student = $user->student;
        
        if (!$student->isRejected()) {
            return back()->with('error', 'You cannot resubmit at this time.');
        }

        // التحقق من صحة البيانات
        $request->validate([
            'enrollment_certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'resubmission_note' => ['nullable', 'string', 'max:500']
        ]);

        // حذف الشهادة القديمة إن وجدت
        if ($student->enrollment_certificate_path) {
            Storage::disk('public')->delete($student->enrollment_certificate_path);
        }

        // رفع الشهادة الجديدة
        $path = $request->file('enrollment_certificate')->store('public/docs');

        // تحديث بيانات الطالب
        $student->update([
            'enrollment_certificate_path' => $path,
            'verification_status' => 'pending', // إعادة إلى pending
            'verified_by' => null,
            'verified_at' => null,
            'resubmission_reason' => $request->resubmission_note,
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'Enrollment certificate resubmitted successfully. It will be reviewed by the supervisor.');
    }
}
