<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockUnverifiedStudents
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); // أو Auth::user()

        if ($user && $user->role === 'student') {
            $student = $user->student;

            if (!$student || $student->verification_status !== 'approved') {

                // 1) طلبات API/JSON: رجّع 403 فقط (لا logout ولا جلسات)
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Your account must be approved by a supervisor before you can sign in.'
                    ], 403);
                }

                // 2) طلبات الويب (جلسات): استخدم حارس web فقط إذا كان مفعّل
                if (Auth::guard('web')->check()) {
                    Auth::guard('web')->logout();
                    // إبطال الجلسة وتجديد التوكن (مهم لحماية CSRF)
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect()->route('login')
                    ->withErrors(['email' => 'حسابك بحاجة لاعتماد المشرف قبل تسجيل الدخول.']);
            }
        }

        return $next($request);
    }
}
