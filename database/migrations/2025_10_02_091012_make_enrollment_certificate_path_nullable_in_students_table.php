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
        Schema::table('students', function (Blueprint $table) {
            // جعل enrollment_certificate_path nullable لأن الطلاب المضافين من admin panel لا يحتاجون شهادة قيد
            $table->string('enrollment_certificate_path', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // إرجاع enrollment_certificate_path إلى required
            $table->string('enrollment_certificate_path', 255)->nullable(false)->change();
        });
    }
};
