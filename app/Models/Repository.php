<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }

}
