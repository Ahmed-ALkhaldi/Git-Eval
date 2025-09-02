<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Supervisor;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // مثال "Test User" القديم كان يستخدم name => استبدله بآتي:
        User::factory()->create([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@example.com',
            'role'       => 'student',
            'password'   => bcrypt('password'),
        ]);

        // Admin
        User::factory()->admin()->create([
            'first_name' => 'Admin',
            'last_name'  => 'Root',
            'email'      => 'admin@example.com',
            'password'   => bcrypt('admin123'),
        ]);

        // Supervisor + سجل supervisors
        $supUser = User::factory()->supervisor()->create([
            'first_name' => 'Ali',
            'last_name'  => 'Saleh',
            'email'      => 'supervisor@example.com',
            'password'   => bcrypt('sup123456'),
        ]);

        Supervisor::create([
            'user_id'         => $supUser->id,
            'university_name' => 'IUG',
            'is_available'    => true,
        ]);

        // Student + سجل students (مقبول لتستطيع الدخول أثناء التطوير)
        $stuUser = User::factory()->create([
            'first_name' => 'Student',
            'last_name'  => 'One',
            'email'      => 'student@example.com',
            'role'       => 'student',
            'password'   => bcrypt('stud123456'),
        ]);

        Student::create([
            'user_id'                       => $stuUser->id,
            'university_name'               => 'IUG',
            'university_num'                => 'S123456',
            'enrollment_certificate_path'   => 'dev/dummy.pdf', // ملف وهمي للتجارب
            'verification_status'           => 'approved',      // مهم: ليعدّي ميدلوير المنع
            'verified_by'                   => $supUser->id,
            'verified_at'                   => now(),
        ]);

        // ... أي seeders إضافية
    }
}
