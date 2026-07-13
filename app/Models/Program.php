<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['name', 'hod_id'];

    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
