<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'class_name',
        'teacher_name',
        'day',
        'start_time',
        'end_time', 
        'room',
        'cr_id',
        'department',
        'start_session',
        'end_session'
    ];

    public function cr()
    {
        return $this->belongsTo(User::class, 'cr_id');
    }
     public function attendances()
    {
        return $this->hasMany(Attendance::class,'class_id');
    }
}
