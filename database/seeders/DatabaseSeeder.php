<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Supervisor;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Student (للتجارب، Approved) ---
        $firstStudentUser = User::factory()->create([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@example.com',
            'role'       => 'student',
            'password'   => Hash::make('password'),
        ]);

        // --- Admin ---
        // لو عندك state admin() في UserFactory خليه كما هو، وإلا استعمل السطر التالي:
        $adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name'  => 'Root',
            'email'      => 'admin@example.com',
            'role'       => 'admin',
            'password'   => Hash::make('admin123'),
        ]);

        // --- Supervisor (User + Supervisor) ---
        // لو عندك state supervisor() في UserFactory خليه كما هو، وإلا استعمل السطر التالي:
        $supUser = User::factory()->create([
            'first_name' => 'Ali',
            'last_name'  => 'Saleh',
            'email'      => 'supervisor@example.com',
            'role'       => 'supervisor',
            'password'   => Hash::make('sup123456'),
        ]);

        // أنشئ سجل المشرف المرتبط بالمستخدم
        $supervisor = Supervisor::create([
            'user_id'         => $supUser->id,
            'university_name' => 'IUG',
            'is_available'    => true,
        ]);

        // --- Students Approved للتجارب ---
        // ملاحظة مهمة: verified_by ينبغي أن يشير إلى supervisors.id حسب الاسكيمة المقترحة
        // لو verified_by عندك مربوط بـ users.id، غيّرها إلى $supUser->id

        // Student #1
        $stuUser1 = User::factory()->create([
            'first_name' => 'Student',
            'last_name'  => 'One',
            'email'      => 'student@example.com',
            'role'       => 'student',
            'password'   => Hash::make('stud123456'),
        ]);

        Student::create([
            'user_id'                     => $stuUser1->id,
            'university_name'             => 'IUG',
            'university_num'              => 'S123456',
            'enrollment_certificate_path' => 'dev/dummy.pdf',
            'verification_status'         => 'approved',
            'verified_by'                 => $supervisor->id, // ← لو FK على supervisors
            'verified_at'                 => now(),
        ]);

        // Student #2
        $stuUser2 = User::factory()->create([
            'first_name' => 'Student2',
            'last_name'  => 'Two',
            'email'      => 'student2@example.com',
            'role'       => 'student',
            'password'   => Hash::make('stud123456'),
        ]);

        Student::create([
            'user_id'                     => $stuUser2->id,
            'university_name'             => 'IUG',
            'university_num'              => 'S123356',
            'enrollment_certificate_path' => 'dev/dummy.pdf',
            'verification_status'         => 'approved',
            'verified_by'                 => $supervisor->id, // ← لو FK على supervisors
            'verified_at'                 => now(),
        ]);

        // Student #3
        $stuUser3 = User::factory()->create([
            'first_name' => 'Student3',
            'last_name'  => 'Three',
            'email'      => 'student3@example.com',
            'role'       => 'student',
            'password'   => Hash::make('stud123456'),
        ]);

        Student::create([
            'user_id'                     => $stuUser3->id,
            'university_name'             => 'IUG',
            'university_num'              => 'S654321',
            'enrollment_certificate_path' => 'dev/dummy.pdf',
            'verification_status'         => 'approved',
            'verified_by'                 => $supervisor->id, // ← لو FK على supervisors
            'verified_at'                 => now(),
        ]);
    }
}
