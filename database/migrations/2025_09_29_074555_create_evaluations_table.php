<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();

            // المشروع المرتبط
            $table->foreignId('project_id')
                  ->constrained('projects')
                  ->cascadeOnDelete();

            // ملخص التقييم الكلّي للمشروع
            $table->decimal('average_score', 5, 2)->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
