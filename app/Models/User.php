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
        'name','email','password','role','program_id','batch_id','shift_id'
    ];

    protected $hidden = ['password'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class, 'cr_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'cr_id');
    }
}

