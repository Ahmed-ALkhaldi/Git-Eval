<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisorRequest extends Model
{
    use HasFactory;

    protected $table = 'supervision_requests';

    protected $fillable = [
        'student_id',
        'supervisor_id',
        'status',       // pending | accepted | rejected
        'message',
        'is_active',
        'responded_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'responded_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'supervisor_id');
    }
}
