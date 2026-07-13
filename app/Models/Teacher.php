<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['name', 'hod_id'];

    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }
}
