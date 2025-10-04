<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEvaluation extends Model
{
    protected $fillable = [
        'evaluation_id',   // nullable: يتعبّى بعد إنشاء Evaluation
        'project_id',
        'student_id',

        // معايير الأداء
        'commits',
        'additions',
        'deletions',
        'issues_opened',
        'prs_opened',
        'prs_merged',
        'reviews',

        // نتيجة فردية
        'score',
        'comments',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
