<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = ['project_id','computed_at','summary'];
    protected $casts = ['computed_at' => 'datetime', 'summary' => 'array'];

    public function project() { return $this->belongsTo(Project::class); }
    public function items() { return $this->hasMany(StudentEvaluation::class); }
}
