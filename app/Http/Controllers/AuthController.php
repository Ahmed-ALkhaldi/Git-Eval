<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{

    
    public function showRegisterForm(){
        return view('register'); // يعرض register.blade.php
    }


    public function showLoginForm(){
        return view('login'); // يعرض login.blade.php
    }

    public function login(Request $request){
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // توجيه حسب الدور
            if ($user->role === 'student') {
                return redirect()->route('dashboard.student'); // لاحظ المسار الصحيح
            } elseif ($user->role === 'supervisor') {
                return redirect()->route('dashboard.supervisor');
            }

            // توجيه افتراضي في حال لم يكن الدور معروفًا
            return redirect('/login')->withErrors(['role' => 'User role is not valid.']);
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }



    //
    public function register(Request $request) {
        // Validate input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:student,supervisor,admin', // حسب مشروعك
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        auth()->login($user); // تسجيل الدخول مباشرة بعد التسجيل

        // التوجيه حسب الدور
        if ($user->role === 'supervisor') {
            return redirect()->route('dashboard.supervisor'); // لاحظ الاسم هنا
        } elseif ($user->role === 'student') {
            return redirect()->route('dashboard.student');
        }

        // توجيه احتياطي إن لم يكن الدور معروفًا
        return redirect('/login')->withErrors(['role' => 'Invalid user role.']);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }


    

}
