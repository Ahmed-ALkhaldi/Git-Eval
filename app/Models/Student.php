<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'university_name',
        'university_num',
        'enrollment_certificate_path',
        'verification_status',
        'verified_by',
        'verified_at',
        'resubmission_reason',
        'github_username', // 👈 مهم للتقييم من GitHub
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /** علاقات مساعدة */
    public function user()               { return $this->belongsTo(User::class); }
    public function ownedProject()       { return $this->hasOne(Project::class, 'owner_student_id'); }
    public function memberships()        { return $this->hasMany(ProjectMember::class, 'student_id'); }
    public function projects()           { return $this->belongsToMany(Project::class, 'project_members'); }
    public function supervisionRequests(){ return $this->hasMany(SupervisorRequest::class, 'student_id'); }
    public function teamInvitations()    { return $this->hasMany(TeamInvitation::class, 'to_student_id'); }

    public function hasAnyMembership(): bool
    {
        return $this->memberships()->exists();
    }

    public function canCreateProject(): bool
    {
        return !$this->ownedProject()->exists() && 
               !$this->hasAnyMembership() && 
               $this->isVerified();
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }
    
    public function studentEvaluations() { return $this->hasMany(\App\Models\StudentEvaluation::class); }

}
