<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};
use App\Models\{User, Student};
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    protected function redirectToRole(User $user)
    {
        return match ($user->role) {
            'student'    => redirect()->route('student.dashboard'),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            'admin'      => redirect()->route('admin.panel'),
            default      => redirect()->route('welcome'), // نضمن وجوده بالأسفل
        };
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectToRole(Auth::user());
        }

        return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name'             => ['required','string','max:100'],
            'last_name'              => ['required','string','max:100'],
            'email'                  => ['required','email','max:255','unique:users,email'],
            'password'               => ['required','confirmed', Password::min(8)],
            'university_name'        => ['required','in:IUG,AUG,UCAS'],
            'university_num'         => ['required','string','max:100'],
            'enrollment_certificate' => ['required','file','mimes:jpg,jpeg,png,pdf','max:4096'],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'student',
        ]);

        $path = $request->file('enrollment_certificate')->store('public/docs');

        Student::create([
            'user_id'                       => $user->id,
            'university_name'               => $data['university_name'],
            'university_num'                => $data['university_num'],
            'enrollment_certificate_path'   => $path,
        ]);

        Auth::login($user);
        return $this->redirectToRole($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome'); // نضمن وجود route('welcome')
    }
}
