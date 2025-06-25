<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\CodeUnit\FunctionUnit;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'status',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
