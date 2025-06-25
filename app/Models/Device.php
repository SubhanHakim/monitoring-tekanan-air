<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'unit_id',
        'api_key',
        'device_type',
        'status',
        'configuration',
        'last_active_at',
        'device_group_id',
    ];

    protected $casts = [
        'configuration' => 'array',
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the unit that owns the device
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function group()
    {
        return $this->belongsTo(\App\Models\DeviceGroup::class, 'device_group_id');
    }

    /**
     * Get all sensor data for this device
     */
    public function sensorData()
    {
        return $this->hasMany(SensorData::class);
    }

    /**
     * Get the most recent sensor data for this device
     */
    public function lastData()
    {
        return $this->hasOne(SensorData::class)->latest('recorded_at');
    }

    /**
     * Get the status of the device
     */
    public function getStatusAttribute($value)
    {
        // Jika status sudah di-set secara eksplisit, gunakan itu
        if ($value !== 'active') {
            return $value;
        }

        // Jika tidak, tentukan status berdasarkan data terakhir
        if (!$this->lastData) {
            return 'offline';
        }

        $lastRecording = $this->lastData->recorded_at;

        if ($lastRecording->diffInMinutes(now()) > 30) {
            return 'offline';
        }

        if ($this->lastData->error_code || $this->lastData->battery < 15) {
            return 'error';
        }

        return 'active';
    }
}
