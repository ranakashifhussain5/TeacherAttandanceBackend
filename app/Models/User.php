<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name','email','password','role','status','blocked_reason','program_id','batch_id','profile_pic'
    ];

    protected $hidden = ['password'];

    protected $appends = ['profile_pic_url'];

    public function getProfilePicUrlAttribute()
    {
        return $this->profile_pic ? Storage::disk('public')->url($this->profile_pic) : null;
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
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

