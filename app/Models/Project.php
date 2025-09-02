<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'owner_student_id', // مالك المشروع (طالب)
        'supervisor_id',    // مشرف من جدول supervisors (اختياري)
    ];

    /** المالك (طالب واحد فقط) */
    public function owner()
    {
        return $this->belongsTo(Student::class, 'owner_student_id');
    }

    /** المشرف المرتبط بالمشروع (اختياري) */
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }

    /** صفوف العضويات (pivot rows) */
    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    /** جميع الطلاب في الفريق عبر pivot project_members */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'project_members', 'project_id', 'student_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /** المستودع */
    public function repository()
    {
        return $this->hasOne(Repository::class);
    }

    /** التقييم */
    public function evaluation()
    {
        return $this->hasOne(Evaluation::class);
    }

    /** نتائج كشف السرقة */
    public function plagiarismChecks()
    {
        return $this->hasMany(PlagiarismCheck::class);
    }

    /** الدعوات */
    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }
}
