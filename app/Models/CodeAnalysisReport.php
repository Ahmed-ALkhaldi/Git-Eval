<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodeAnalysisReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'bugs','vulnerabilities','code_smells',
        'coverage','duplicated_lines_density',
        'ncloc','security_hotspots',
        'quality_gate','analysis_key','analysis_at',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
