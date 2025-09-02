<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvitation extends Model
{
  protected $fillable = ['project_id','to_student_id','invited_by_user_id','status','responded_at'];
  protected $casts = ['responded_at'=>'datetime'];

  public function project(){ return $this->belongsTo(Project::class); }
  public function toStudent(){ return $this->belongsTo(Student::class, 'to_student_id'); }
  public function invitedBy(){ return $this->belongsTo(User::class, 'invited_by_user_id'); }

  public function scopePending($q){ return $q->where('status','pending'); }
}
