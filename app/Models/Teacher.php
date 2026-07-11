<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['name'];

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }
}
