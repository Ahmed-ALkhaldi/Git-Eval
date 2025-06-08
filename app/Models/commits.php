<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class commits extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'repository_id',
        'commit_sha',
        'author_name',
        'author_email',
        'commit_date',
        'message'
    ];

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

}
