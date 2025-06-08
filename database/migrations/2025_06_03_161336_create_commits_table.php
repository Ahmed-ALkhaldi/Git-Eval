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
        $table->string('commit_sha');                  // رقم الـ commit
        $table->string('author_name');                 // اسم الكاتب
        $table->string('author_email')->nullable();    // الإيميل (قد يكون null)
        $table->timestamp('commit_date');              // وقت الـ commit
        $table->text('message');                       // رسالة الـ commit
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
