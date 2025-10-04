<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentProfileController extends Controller
{
    /**
     * عرض صفحة تعديل الملف الشخصي
     */
    public function edit()
    {
        $user = Auth::user();
        $student = $user->student;
        
        return view('student.profile.edit', compact('user', 'student'));
    }

    /**
     * تحديث الملف الشخصي
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $student = $user->student;

        // التحقق من صحة البيانات
        $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|min:8|confirmed',
            'university_name' => 'nullable|string|max:255',
            'university_num' => 'nullable|string|max:50',
        ]);

        try {
            // تحديث بيانات المستخدم
            $updateData = [
                'email' => $request->email,
            ];
            
            // تحديث كلمة المرور إذا تم إدخالها
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            
            \App\Models\User::where('id', $user->id)->update($updateData);

            // تحديث بيانات الطالب
            if ($student) {
                $student->update([
                    'university_name' => $request->university_name,
                    'university_num' => $request->university_num,
                ]);
            }

            return redirect()->route('student.dashboard')
                ->with('success', '✅ Profile updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Failed to update profile. Please try again.');
        }
    }
}