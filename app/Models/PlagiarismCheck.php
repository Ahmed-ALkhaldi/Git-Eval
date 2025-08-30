<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlagiarismCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'project1_id',
        'project2_id',
        'similarity_percentage',
        'matches',
        'report_url',
    ];
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

}
