<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'class_id','cr_id','date','arrived_time','left_time','status'
    ];

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class,'class_id');
    }

    public function cr()
    {
        return $this->belongsTo(User::class,'cr_id');
    }
}
