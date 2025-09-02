<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    if (!Schema::hasTable('team_invitations')) {
      Schema::create('team_invitations', function (Blueprint $t) {
        $t->id();
        $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
        $t->foreignId('to_student_id')->constrained('students')->cascadeOnDelete();
        $t->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
        $t->enum('status',['pending','accepted','declined','expired'])->default('pending');
        $t->timestamp('responded_at')->nullable();
        $t->timestamps();
        // منع تكرار دعوة PENDING لنفس الطالب على نفس المشروع
        $t->unique(['project_id','to_student_id','status'],'uniq_pending_invite_per_project_student');
        $t->index(['to_student_id','status']);
      });
    }
  }
  public function down(): void {
    Schema::dropIfExists('team_invitations');
  }
};
