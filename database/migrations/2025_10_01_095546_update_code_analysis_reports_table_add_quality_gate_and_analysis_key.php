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
        Schema::table('code_analysis_reports', function (Blueprint $table) {
            $table->string('quality_gate', 20)->nullable()->after('security_hotspots'); // OK / ERROR / WARN
            $table->string('analysis_key')->nullable()->after('quality_gate');     // مفتاح التحليل من Sonar
            $table->timestamp('analysis_at')->nullable()->after('analysis_key');
            $table->renameColumn('lines_of_code', 'ncloc'); // توحيد الأسماء مع SonarQube
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('code_analysis_reports', function (Blueprint $table) {
            $table->dropColumn(['quality_gate', 'analysis_key', 'analysis_at']);
            $table->renameColumn('ncloc', 'lines_of_code');
        });
    }
};
