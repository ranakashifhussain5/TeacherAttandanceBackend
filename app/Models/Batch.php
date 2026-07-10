<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = ['start_year', 'end_year'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function getNameAttribute()
    {
        return "{$this->start_year}-{$this->end_year}";
    }
}
