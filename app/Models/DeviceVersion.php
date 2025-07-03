<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceVersion extends Model
{
    protected $fillable = [
        'device_id', 'name', 'device_type', 'location', 'status',
        'latitude', 'longitude', 'installed_at', 'installation_year',
        'configuration', 'effective_from', 'effective_to',
    ];

    protected $casts = [
        'configuration' => 'array',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'installed_at' => 'date',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
