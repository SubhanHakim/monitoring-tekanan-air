<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'device_type',
        'location',
        'status',
        'configuration',
        'last_active_at',
        'device_group_id',
    ];

    protected $casts = [
        'configuration' => 'array',
        'last_active_at' => 'datetime',
    ];

    public function sensorData()
    {
        return $this->hasMany(SensorData::class);
    }

    public function group()
    {
        return $this->belongsTo(DeviceGroup::class, 'device_group_id');
    }

    public function latestSensorData()
    {
        return $this->hasOne(SensorData::class)->latest('recorded_at');
    }
}
