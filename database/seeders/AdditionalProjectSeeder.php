<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Repository;

class AdditionalProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Use the already-seeded supervisor by email
        $supervisorUserEmail = 'supervisor@example.com';
        $supervisor = Supervisor::whereHas('user', fn($q) => $q->where('email', $supervisorUserEmail))->first();
        if (!$supervisor) {
            return; // Supervisor not found, skip
        }

        // Create two new approved students (new users + student profiles)
        $newStudentsData = [
            [
                'first_name' => 'New',
                'last_name'  => 'StudentA',
                'email'      => 'studentA@example.com',
                'university_num' => 'S900001',
                // Use repo owner so metrics resolve publicly
                'github_username' => 'Ahmed-ALkhaldi',
            ],
            [
                'first_name' => 'New',
                'last_name'  => 'StudentB',
                'email'      => 'studentB@example.com',
                'university_num' => 'S900002',
                // Second contributor on the repo
                'github_username' => 'younisbahaa',
            ],
        ];

        $createdStudents = collect();

        DB::transaction(function () use ($newStudentsData, $supervisor, $createdStudents) {
            foreach ($newStudentsData as $data) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'first_name' => $data['first_name'],
                        'last_name'  => $data['last_name'],
                        'role'       => 'student',
                        'password'   => Hash::make('stud123456'),
                    ]
                );

                $student = Student::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'university_name'             => 'IUG',
                        'university_num'              => $data['university_num'],
                        'enrollment_certificate_path' => 'dev/dummy.pdf',
                        'verification_status'         => 'approved',
                        'verified_by'                 => $supervisor->id,
                        'verified_at'                 => now(),
                        'github_username'             => $data['github_username'],
                    ]
                );

                $createdStudents->push($student);
            }
        });

        if ($createdStudents->isEmpty()) {
            // Nothing to do
            return;
        }

        $owner = $createdStudents->first();
        $members = $createdStudents->slice(1)->values();

        DB::transaction(function () use ($owner, $members, $supervisor) {
            // Create a second project under the same supervisor using Git-Eval repo
            $project = Project::firstOrCreate(
                [
                    'title' => 'Git-Eval',
                    'owner_student_id' => $owner->id,
                    'supervisor_id' => $supervisor->id,
                ],
                [
                    'description' => 'Seeded project linked to Ahmed-ALkhaldi/Git-Eval repository.',
                ]
            );

            // Ensure memberships
            ProjectMember::firstOrCreate([
                'project_id' => $project->id,
                'student_id' => $owner->id,
            ], [
                'role' => 'owner',
            ]);

            foreach ($members as $m) {
                ProjectMember::firstOrCreate([
                    'project_id' => $project->id,
                    'student_id' => $m->id,
                ], [
                    'role' => 'member',
                ]);
            }

            // Attach repository: Ahmed-ALkhaldi/Git-Eval (main)
            $repoName = 'Ahmed-ALkhaldi/Git-Eval';
            Repository::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'repo_name' => $repoName,
                    'github_url' => 'https://github.com/'.$repoName,
                    'default_branch' => 'main',
                    'description' => 'Git-Eval public repository',
                    'stars' => 0,
                    'forks' => 0,
                    'open_issues' => 0,
                ]
            );
        });
    }
} 