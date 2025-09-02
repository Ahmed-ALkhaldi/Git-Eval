<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // مالك | عضو (اختياريًا اجعل المالك يظهر أيضًا كعضو بدور owner)
            $table->enum('role', ['owner', 'member'])->default('member');

            $table->timestamps();

            // لا يتكرر نفس الطالب في نفس المشروع
            $table->unique(['project_id', 'student_id'], 'project_members_unique');
            $table->index(['student_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
