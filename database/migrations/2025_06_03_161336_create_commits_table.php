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
        Schema::create('commits', function (Blueprint $table) {
        $table->id();
        $table->foreignId('repository_id')->constrained()->onDelete('cascade');
        $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
        $table->string('commit_hash')->unique();
        $table->string('message');
        $table->timestamp('committed_at');
        $table->integer('additions')->default(0);
        $table->integer('deletions')->default(0);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
};
