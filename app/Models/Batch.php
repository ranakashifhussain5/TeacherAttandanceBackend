<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = ['program_id', 'shift_id', 'start_year', 'end_year'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

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
        $range = "{$this->start_year}-{$this->end_year}";
        $shiftName = optional($this->shift)->name;

        return $shiftName ? "{$range} {$shiftName}" : $range;
    }
}
