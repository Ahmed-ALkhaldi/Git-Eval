<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\URL;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'university_name',
        'university_num',
        'enrollment_certificate_path',
        'verification_status', // pending | approved | rejected
        'verified_by',
        'verified_at',
        'resubmissions_count', // إن أضفتها
        'last_submitted_at',   // إن أضفتها
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'last_submitted_at' => 'datetime',
    ];

    /** علاقات */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Scopes مفيدة للوحة المشرف */
    public function scopePending($q)  { return $q->where('verification_status', 'pending'); }
    public function scopeApproved($q) { return $q->where('verification_status', 'approved'); }
    public function scopeRejected($q) { return $q->where('verification_status', 'rejected'); }

    /** منطق إعادة التحقق تلقائياً عند تغييرات حساسة */
    protected static function booted()
    {
        static::updating(function (Student $student) {
            $dirtySensitive = $student->isDirty('university_num')
                || $student->isDirty('enrollment_certificate_path');

            if ($dirtySensitive) {
                $student->verification_status = 'pending';
                $student->verified_by = null;
                $student->verified_at = null;

                if ($student->isFillable('resubmissions_count') && !is_null($student->resubmissions_count)) {
                    $student->resubmissions_count = (int) $student->resubmissions_count + 1;
                }
                if ($student->isFillable('last_submitted_at')) {
                    $student->last_submitted_at = now();
                }
            }
        });
    }

    /** (اختياري) URL موقّع لعرض شهادة القيد بشكل آمن */
    protected $appends = ['enrollment_certificate_url'];

    public function getEnrollmentCertificateUrlAttribute(): ?string
    {
        if (!$this->enrollment_certificate_path) return null;

        // تأكّد من وجود Route باسم students.certificate.show
        // يعود بملف بعد التحقق من الصلاحيات (مشرف/أدمن)
        return URL::temporarySignedRoute(
            'students.certificate.show',
            now()->addMinutes(20),
            ['student' => $this->getKey()]
        );
    }

    /** واجهات بسيطة للاعتماد/الرفض (يمكن استخدامها بخدمة أو Controller) */
    public function approve(int $byUserId): void
    {
        $this->update([
            'verification_status' => 'approved',
            'verified_by' => $byUserId,
            'verified_at' => now(),
        ]);
    }

    public function reject(int $byUserId): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by' => $byUserId,
            'verified_at' => now(),
        ]);
    }

    public function ownedProject() { return $this->hasOne(\App\Models\Project::class, 'owner_student_id'); }
    public function memberships()  { return $this->hasMany(\App\Models\ProjectMember::class, 'student_id'); }
    public function projects()     { return $this->belongsToMany(\App\Models\Project::class, 'project_members'); }
    public function supervisionRequests() { return $this->hasMany(\App\Models\SupervisorRequest::class, 'student_id'); }
    public function teamInvitations()     { return $this->hasMany(\App\Models\TeamInvitation::class, 'to_student_id'); }

    public function hasAnyMembership(): bool {
    return $this->memberships()->exists();
    }
    public function canCreateProject(): bool {
    return !$this->ownedProject()->exists() && !$this->hasAnyMembership();
    }

}
