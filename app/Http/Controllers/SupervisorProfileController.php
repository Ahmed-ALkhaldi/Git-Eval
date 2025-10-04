<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class SupervisorProfileController extends Controller
{
    /**
     * Show the supervisor profile edit form.
     */
    public function edit()
    {
        $user = Auth::user();
        
        // التأكد من أن المستخدم مشرف
        if (!$user || $user->role !== 'supervisor') {
            abort(403, 'Access denied. Supervisors only.');
        }

        return view('supervisor.profile.edit', compact('user'));
    }

    /**
     * Update the supervisor profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // التأكد من أن المستخدم مشرف
        if (!$user || $user->role !== 'supervisor') {
            abort(403, 'Access denied. Supervisors only.');
        }

        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id
            ],
            'current_password' => 'required_with:new_password',
            'new_password' => [
                'nullable',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'active_status' => 'required|boolean'
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'current_password.required_with' => 'Current password is required to change password.',
            'new_password.confirmed' => 'Password confirmation does not match.',
            'new_password.min' => 'Password must be at least 8 characters.',
            'active_status.required' => 'Active status is required.',
            'active_status.boolean' => 'Active status must be true or false.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // التحقق من كلمة المرور الحالية إذا تم إدخال كلمة مرور جديدة
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()
                    ->withErrors(['current_password' => 'Current password is incorrect.'])
                    ->withInput();
            }
        }

        // تحديث البيانات
        $updateData = [
            'email' => $request->email,
            'is_active' => $request->boolean('active_status')
        ];

        // تحديث كلمة المرور إذا تم إدخالها
        if ($request->filled('new_password')) {
            $updateData['password'] = Hash::make($request->new_password);
        }

        User::where('id', $user->id)->update($updateData);

        return redirect()->route('supervisor.profile.edit')
            ->with('success', 'Profile updated successfully!');
    }
}
