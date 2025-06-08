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
        'student_id',
    ];
    
    // الطلاب المرتبطين بالمشروع
    public function students()
    {
        return $this->belongsToMany(User::class, 'project_user');
    }


    // المشرف المسؤول
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
    // المستودع
    public function repository()
    {
        return $this->hasOne(Repository::class);
    }
    // التقييم
    public function evaluation()
    {
        return $this->hasOne(Evaluation::class);
    }

    // نتائج كشف السرقة
    public function plagiarismChecks()
    {
        return $this->hasMany(PlagiarismCheck::class);
    }

}
