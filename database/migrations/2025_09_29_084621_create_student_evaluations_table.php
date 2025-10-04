<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_evaluations', function (Blueprint $table) {
            $table->id();

            // الربط مع المشروع
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            // الطالب الذي يتم تقييمه
            $table->foreignId('student_id')->constrained()->onDelete('cascade');

            // الربط مع جدول evaluations (لو بدك تجمعهم)
            $table->foreignId('evaluation_id')->nullable()
                ->constrained('evaluations')
                ->onDelete('cascade');

            // معايير الأداء
            $table->unsignedInteger('commits')->default(0);
            $table->unsignedInteger('additions')->default(0);
            $table->unsignedInteger('deletions')->default(0);
            $table->unsignedInteger('issues_opened')->default(0);
            $table->unsignedInteger('prs_opened')->default(0);
            $table->unsignedInteger('prs_merged')->default(0);
            $table->unsignedInteger('reviews')->default(0);

            // النتيجة النهائية
            $table->decimal('score', 5, 2)->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();

            // منع التكرار: طالب واحد لا يمكن أن يقيم مرتين في نفس المشروع
            $table->unique(['project_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_evaluations');
    }
};
