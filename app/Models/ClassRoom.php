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
        'teacher_id',
        'day',
        'start_time',
        'end_time',
        'room',
        'cr_id',
        'program_id',
        'batch_id'
    ];

    public function cr()
    {
        return $this->belongsTo(User::class, 'cr_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

     public function attendances()
    {
        return $this->hasMany(Attendance::class,'class_id');
    }
}
