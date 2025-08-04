<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plagiarism_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project1_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('project2_id')->constrained('projects')->onDelete('cascade');
            $table->float('similarity_percentage')->nullable(); // نسبة التشابه إن أمكن استخراجها
            $table->json('matches')->nullable(); // لتخزين تفاصيل كل ملفين متشابهين
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plagiarism_checks');
    }
};
