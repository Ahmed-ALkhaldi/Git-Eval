<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plagiarism_checks extends Model
{
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

}
