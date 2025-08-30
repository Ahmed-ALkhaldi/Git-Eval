<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supervisor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'university_name',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    /** علاقة */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** مشرفون متاحون */
    public function scopeAvailable($q)
    {
        return $q->where('is_available', true);
    }
}
