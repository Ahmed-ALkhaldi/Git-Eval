<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminStudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with('user')
            ->orderByDesc('id')
            ->get()
            ->map(fn($s)=>[
                'id' => $s->id,
                'name' => $s->user?->full_name ?? trim(($s->user?->first_name ?? '').' '.($s->user?->last_name ?? '')),
                'email'=> $s->user?->email,
                'university_name' => $s->university_name,
                'university_num'  => $s->university_num,
            ]);

        return response()->json($students);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required','string','max:200'],
            'email'            => ['required','email','max:255','unique:users,email'],
            'password'         => ['required','min:6'],
            'university_name'  => ['required','string','max:255'],
            'university_num'   => ['required','string','max:255', 'unique:students,university_num'],
        ]);

        [$first_name, $last_name] = $this->splitName($data['name']);

        $user = User::create([
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'student',
        ]);

        $student = Student::create([
            'user_id'          => $user->id,
            'university_name'  => $data['university_name'],
            'university_num'   => $data['university_num'],
            'verification_status' => 'pending',
        ]);

        return response()->json(['id'=>$student->id], 201);
    }

    public function update(Request $request, $id)
    {
        $student = Student::with('user')->findOrFail($id);

        $data = $request->validate([
            'name'             => ['sometimes','required','string','max:200'],
            'email'            => ['sometimes','required','email','max:255', Rule::unique('users','email')->ignore($student->user_id)],
            'password'         => ['nullable','min:6'],
            'university_name'  => ['sometimes','required','string','max:255'],
            'university_num'   => ['sometimes','required','string','max:255', Rule::unique('students','university_num')->ignore($student->id)],
        ]);

        if (isset($data['name'])) {
            [$first_name, $last_name] = $this->splitName($data['name']);
            $student->user->first_name = $first_name;
            $student->user->last_name  = $last_name;
        }
        if (isset($data['email']))    $student->user->email = $data['email'];
        if (!empty($data['password']))$student->user->password = Hash::make($data['password']);
        $student->user->save();

        if (isset($data['university_name'])) $student->university_name = $data['university_name'];
        if (isset($data['university_num']))  $student->university_num  = $data['university_num'];
        $student->save();

        return response()->json(['ok'=>true]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        // cascade: Student belongsTo User (unique). نحذف اليوزر أيضًا
        $userId = $student->user_id;
        $student->delete();
        if ($userId) User::where('id',$userId)->delete();
        return response()->json(['ok'=>true]);
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }
}
