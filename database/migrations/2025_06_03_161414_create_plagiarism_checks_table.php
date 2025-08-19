<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plagiarism_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project1_id')
                  ->constrained('projects')
                  ->onDelete('cascade');

            $table->foreignId('project2_id')
                  ->constrained('projects')
                  ->onDelete('cascade');

            // أدق من float
            $table->decimal('similarity_percentage', 5, 2)->nullable();

            $table->json('matches')->nullable();
            $table->string('report_url')->nullable();

            // اختياري: منع تكرار نفس الزوج
            // $table->unique(['project1_id', 'project2_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plagiarism_checks');
    }
};

