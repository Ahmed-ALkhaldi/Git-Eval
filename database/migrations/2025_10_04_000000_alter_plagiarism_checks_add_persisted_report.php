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
        Schema::table('plagiarism_checks', function (Blueprint $table) {
            // تحديث الحقول الموجودة لتكون nullable
            $table->decimal('similarity_percentage', 5, 2)->nullable()->change();
            $table->json('matches')->nullable()->change();
            $table->string('report_url')->nullable()->change();

            // إضافة حقول جديدة
            $table->unsignedInteger('matches_count')->nullable()->after('similarity_percentage');
            $table->string('moss_task_id')->nullable()->after('report_url');
            $table->timestamp('compared_at')->nullable()->after('moss_task_id');
            $table->unsignedInteger('duration_ms')->nullable()->after('compared_at');
            
            // تخزين HTML مضغوط (يوفر مساحة كبيرة)
            $table->longText('report_html_gz')->nullable()->after('duration_ms');
            
            // مسار ملف HTML محلي (بديل للتخزين المضغوط)
            $table->string('report_path')->nullable()->after('report_html_gz');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plagiarism_checks', function (Blueprint $table) {
            $table->dropColumn([
                'matches_count',
                'moss_task_id', 
                'compared_at',
                'duration_ms',
                'report_html_gz',
                'report_path'
            ]);
        });
    }
};
