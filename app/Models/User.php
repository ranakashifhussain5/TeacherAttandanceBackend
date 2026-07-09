<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name','email','password','role','department','shift','start_session','end_session'
    ];

    protected $hidden = ['password'];

    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'cr_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'cr_id');
    }
}

