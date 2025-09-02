<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMember extends Model
{
  protected $fillable = ['project_id','student_id','role'];
  public function project(){ return $this->belongsTo(Project::class); }
  public function student(){ return $this->belongsTo(Student::class); }
}
