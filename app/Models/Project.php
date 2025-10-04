<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "description",
        "supervisor_note",
        "sonar_project_key",
        "owner_student_id", // مالك المشروع
        "supervisor_id",    // المشرف (اختياري لحين القبول)
    ];

    /** مالك المشروع (طالب) */
    public function owner()
    {
        return $this->belongsTo(Student::class, "owner_student_id");
    }

    /** أعضاء الفريق عبر pivot project_members (مع role إن لزم) */
    public function students()
    {
        return $this->belongsToMany(Student::class, "project_members")
                    ->withPivot(["role"])
                    ->withTimestamps();
    }

    /** المشرف */
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }

    /** الريبو المرتبط بالمشروع (نستخدمه لعرض رابط GitHub) */
    public function repository()
    {
        return $this->hasOne(Repository::class);
    }

    /** تقارير تحليل السونار (الموديل عندك CodeAnalysisReport) */
    public function codeAnalysisReport()
    {
        return $this->hasOne(CodeAnalysisReport::class);
    }

    /** نتائج كشف الانتحال: كـ project1 */
    public function plagiarismChecks()
    {
        return $this->hasMany(PlagiarismCheck::class, "project1_id");
    }

    /** نتائج كشف الانتحال: كـ project2 */
    public function plagiarismChecksAsProject2()
    {
        return $this->hasMany(PlagiarismCheck::class, "project2_id");
    }

    /** الدعوات */
    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /** تقييم جماعي للمشروع (Evaluation) */
    public function evaluation()
    {
        return $this->hasOne(Evaluation::class);
    }

    public function evaluations()        { return $this->hasMany(\App\Models\Evaluation::class); }
    public function studentEvaluations() { return $this->hasMany(\App\Models\StudentEvaluation::class); }

    /** تقارير تحليل الكود الجديدة */
    public function codeAnalysisReports()
    {
        return $this->hasMany(\App\Models\CodeAnalysisReport::class);
    }

    /** آخر تقرير تحليل كود */
    public function latestCodeAnalysisReport()
    {
        return $this->hasOne(\App\Models\CodeAnalysisReport::class)->latest('analysis_at');
    }

    /** قضايا تحليل الكود */
    public function codeAnalysisIssues()
    {
        return $this->hasMany(\App\Models\CodeAnalysisResult::class);
    }

}
