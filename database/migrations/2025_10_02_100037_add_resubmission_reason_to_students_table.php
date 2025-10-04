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
            // إضافة عمود resubmission_reason للطلاب الذين يعيدون تقديم شهادة القيد
            $table->text('resubmission_reason')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // حذف عمود resubmission_reason
            $table->dropColumn('resubmission_reason');
        });
    }
};
