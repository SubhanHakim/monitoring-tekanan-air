<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorData extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'recorded_at',
        'flowrate',
        'totalizer',
        'battery',
        'pressure1',
        'pressure2',
        'additional_data',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'flowrate' => 'float',
        'totalizer' => 'float',
        'battery' => 'float',
        'pressure1' => 'float',
        'pressure2' => 'float',
        'additional_data' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}