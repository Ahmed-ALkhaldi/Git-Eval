<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supervisors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('university_name', 190);
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisors');
    }
};
