<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use Carbon\Carbon;

class SupervisorVerificationController extends Controller
{
    /**
     * Middleware على مستوى الكنترولر
     */
    protected $middleware = [
        'auth',
    ];

    public function index(Request $request)
    {
        $me = Auth::user();
        if ($me->role !== 'supervisor') {
            abort(403, 'Only supervisors can access this page.');
        }

        $q = trim((string) $request->get('q'));

        $students = Student::with('user')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($b) use ($q) {
                    $b->whereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    })
                    ->orWhere('university_num', 'like', "%{$q}%")
                    ->orWhere('university_name', 'like', "%{$q}%");
                });
            })
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('supervisor.student_verification', compact('students'));
    }

    public function approve(Request $request, Student $student)
    {
        $me = Auth::user();
        if ($me->role !== 'supervisor') {
            abort(403);
        }

        if ($student->verification_status === 'approved') {
            return back()->with('success', 'Student is already approved.');
        }
        if ($student->verification_status === 'rejected') {
            return back()->with('error', 'Student was already rejected. Contact admin to revert.');
        }

        DB::transaction(function () use ($student, $me) {
            $student->forceFill([
                'verification_status' => 'approved',
                'verified_by'         => $me->id,
                'verified_at'         => Carbon::now(),
            ])->save();
        });

        return back()->with('success', 'Student approved successfully.');
    }

    public function reject(Request $request, Student $student)
    {
        $me = Auth::user();
        if ($me->role !== 'supervisor') {
            abort(403);
        }

        if ($student->verification_status === 'rejected') {
            return back()->with('success', 'Student is already rejected.');
        }
        if ($student->verification_status === 'approved') {
            return back()->with('error', 'Student is already approved. Contact admin to revert.');
        }

        DB::transaction(function () use ($student, $me) {
            $student->forceFill([
                'verification_status' => 'rejected',
                'verified_by'         => $me->id,
                'verified_at'         => Carbon::now(),
            ])->save();
        });

        return back()->with('success', 'Student rejected.');
    }
}
