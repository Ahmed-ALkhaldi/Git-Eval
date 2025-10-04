<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\Repository;
use App\Models\ProjectMember;
use App\Models\Student;
use App\Models\Supervisor;

class FixedProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch seeded supervisor and students by known emails from DatabaseSeeder
        $supervisorUserEmail = 'supervisor@example.com';
        $studentEmails = [
            'student@example.com',
            'student2@example.com',
            'student3@example.com',
        ];

        $supervisor = Supervisor::whereHas('user', fn($q) => $q->where('email', $supervisorUserEmail))->first();
        if (!$supervisor) {
            return; // supervisor not seeded; skip
        }

        $students = Student::whereHas('user', fn($q) => $q->whereIn('email', $studentEmails))->get();
        if ($students->count() < 3) {
            return; // students not ready; skip
        }

        $owner = $students->first();
        $members = $students->slice(1)->values();

        DB::transaction(function () use ($supervisor, $owner, $members) {
            // Create or find the project
            $project = Project::firstOrCreate(
                [
                    'title' => 'Book Review App',
                    'owner_student_id' => $owner->id,
                    'supervisor_id' => $supervisor->id, // accepted by supervisor
                ],
                [
                    'description' => 'Seeded project for quick testing: Laravel Book Review App.',
                ]
            );

            // Ensure membership pivot
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

            // Attach repository pointing to the example repo
            $repoName = 'Ahmed-ALkhaldi/Book-Review-App';
            Repository::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'repo_name' => $repoName,
                    'github_url' => 'https://github.com/'.$repoName,
                    'default_branch' => 'master',
                    'description' => 'Seeded repo link for testing.',
                    'stars' => 0,
                    'forks' => 0,
                    'open_issues' => 0,
                ]
            );
        });
    }
} 