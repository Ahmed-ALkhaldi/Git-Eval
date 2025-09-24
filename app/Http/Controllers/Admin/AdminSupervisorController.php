<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminSupervisorController extends Controller
{
    public function index()
    {
        $supervisors = Supervisor::with('user')
            ->orderByDesc('id')
            ->get()
            ->map(fn($s)=>[
                'id'     => $s->id,
                'name'   => $s->user?->full_name ?? trim(($s->user?->first_name ?? '').' '.($s->user?->last_name ?? '')),
                'email'  => $s->user?->email,
                'status' => $s->is_available ? 'Active' : 'Inactive',
                'university_name' => $s->university_name,
            ]);

        return response()->json($supervisors);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required','string','max:200'],
            'email'           => ['required','email','max:255','unique:users,email'],
            'password'        => ['required','min:6'],
            'status'          => ['required','in:Active,Inactive'],
            'university_name' => ['nullable','string','max:255'],
        ]);

        [$first_name, $last_name] = $this->splitName($data['name']);

        $user = User::create([
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'supervisor',
        ]);

        $sup = Supervisor::create([
            'user_id'         => $user->id,
            'university_name' => $data['university_name'] ?? null,
            'is_available'    => $data['status'] === 'Active',
        ]);

        return response()->json(['id'=>$sup->id], 201);
    }

    public function update(Request $request, $id)
    {
        $sup = Supervisor::with('user')->findOrFail($id);

        $data = $request->validate([
            'name'            => ['sometimes','required','string','max:200'],
            'email'           => ['sometimes','required','email','max:255', Rule::unique('users','email')->ignore($sup->user_id)],
            'password'        => ['nullable','min:6'],
            'status'          => ['sometimes','required','in:Active,Inactive'],
            'university_name' => ['sometimes','nullable','string','max:255'],
        ]);

        if (isset($data['name'])) {
            [$first_name, $last_name] = $this->splitName($data['name']);
            $sup->user->first_name = $first_name;
            $sup->user->last_name  = $last_name;
        }
        if (isset($data['email']))    $sup->user->email = $data['email'];
        if (!empty($data['password']))$sup->user->password = Hash::make($data['password']);
        $sup->user->save();

        if (array_key_exists('status',$data))      $sup->is_available = $data['status'] === 'Active';
        if (array_key_exists('university_name',$data)) $sup->university_name = $data['university_name'];
        $sup->save();

        return response()->json(['ok'=>true]);
    }

    public function destroy($id)
    {
        $sup = Supervisor::findOrFail($id);
        $userId = $sup->user_id;
        $sup->delete();
        if ($userId) User::where('id',$userId)->delete();
        return response()->json(['ok'=>true]);
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }
}
