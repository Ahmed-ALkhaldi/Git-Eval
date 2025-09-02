<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('university_name', 190);
            $table->string('university_num', 100);
            $table->string('enrollment_certificate_path', 255);

            $table->enum('verification_status', ['pending','approved','rejected'])
                  ->default('pending');

            $table->foreignId('verified_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // فهارس مفيدة للبحث
            $table->index('university_num');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
