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
        Schema::create('evaluations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained()->onDelete('cascade');
        $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
        $table->float('code_quality')->nullable();         // مثال: من 0 إلى 100
        $table->float('team_collaboration')->nullable();   // مشاركة الطلاب في Git
        $table->float('overall_score')->nullable();        // مجموع أو متوسط
        $table->text('comments')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
