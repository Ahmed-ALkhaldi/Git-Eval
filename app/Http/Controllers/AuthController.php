<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, Storage};
use App\Models\{User, Student};

class AuthController extends Controller
{
    /** عرض فورم التسجيل (للطلاب فقط) */
    public function showRegisterForm()
    {
        return view('register');
    }

    /** عرض فورم الدخول */
    public function showLoginForm()
    {
        return view('login');
    }

    /** تسجيل الدخول */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // لو طالب: لا تسمح بالدخول إن لم يكن معتمدًا
        if ($user->role === 'student') {
            $student = $user->student;
            if (!$student || $student->verification_status !== 'approved') {
                Auth::logout(); // حارس web
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'حسابك بحاجة لاعتماد المشرف قبل تسجيل الدخول.']);
            }
            return redirect()->route('dashboard.student');
        }

        if ($user->role === 'supervisor') {
            return redirect()->route('dashboard.supervisor');
        }

        // أدمن أو دور غير معروف
        return redirect()->route('login')->withErrors(['role' => 'User role is not valid.']);
    }

    /** تسجيل الطالب فقط */
    public function register(Request $request)
    {
        // هذه الصفحة خاصة بتسجيل الطلاب فقط حسب متطلباتك
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6|confirmed',

            // حقول الطالب
            'university_name' => 'required|string|max:190',
            'university_num'  => 'required|string|max:190|unique:students,university_num',
            'enrollment_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ]);

        // رفع شهادة القيد إلى مجلد خاص
        $certPath = $request->file('enrollment_certificate')
                            ->store('private/enrollments');

        // إنشاء المستخدم بدور طالب فقط
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => 'student', // ثابت: لا يُسمح بتسجيل مشرف من الواجهة
        ]);

        // إنشاء سجل الطالب بحالة pending
        Student::create([
            'user_id'                     => $user->id,
            'university_name'             => $request->university_name,
            'university_num'              => $request->university_num,
            'enrollment_certificate_path' => $certPath,
            'verification_status'         => 'pending',
            'verified_by'                 => null,
            'verified_at'                 => null,
        ]);

        // لا تسجّل الدخول تلقائيًا؛ الطالب لا يستطيع الدخول قبل الاعتماد
        return redirect()->route('login')
            ->with('success', 'تم إنشاء حسابك بنجاح. نرجو انتظار اعتماد المشرف قبل تسجيل الدخول.');
    }
}
