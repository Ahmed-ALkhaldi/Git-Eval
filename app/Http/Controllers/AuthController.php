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
            // نجاح تسجيل الدخول
            return redirect()->intended('/dashboard'); // أو أي صفحة رئيسية
        }

        return back()->withErrors([
            'email' => 'Email or password incorrect',
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
