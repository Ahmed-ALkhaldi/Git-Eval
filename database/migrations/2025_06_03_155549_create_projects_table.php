<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            // مالك المشروع: طالب واحد فقط (قيد فريد لضمان مشروع واحد لكل طالب كمالك)
            $table->foreignId('owner_student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
            $table->unique('owner_student_id', 'projects_owner_student_unique');

            // (اختياري) ربط المشروع بمشرف (من جدول supervisors)
            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->constrained('supervisors')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
