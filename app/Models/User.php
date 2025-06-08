<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    // علاقة Many-to-Many مع المشاريع (للطلاب فقط)
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user');
    }


    // علاقة One-to-Many للمشرف
    public function supervisedProjects()
    {
        return $this->hasMany(Project::class, 'supervisor_id');
    }
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

}
