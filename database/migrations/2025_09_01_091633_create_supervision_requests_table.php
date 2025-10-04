<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supervision_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('supervisor_id')
                ->constrained('supervisors')
                ->cascadeOnDelete();

            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('is_active');

            // فريد: طالب واحد لا يملك إلا طلبًا نشطًا واحدًا
            $table->unique(['student_id', 'is_active'], 'uniq_student_active_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervision_requests');
    }
};
