<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'github_url',
        'repo_name',
        'description',
        'stars',
        'forks',
        'open_issues',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }
}
