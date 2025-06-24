<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'color',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
    
}
