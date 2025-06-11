<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodeAnalysisResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'issue_type', 'message', 'component', 'line'
    ];

}
