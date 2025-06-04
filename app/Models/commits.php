<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class commits extends Model
{
    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

}
