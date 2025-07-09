<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'name',
        'description',
        'report_format',
        'data_source',
        'device_id',
        'device_group_id',
        'start_date',
        'end_date',
        'metrics',
        'file_path',
        'file_type',
        'status',
        'generated_at',
        'created_by',
    ];

    protected $casts = [
        'metrics' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'generated_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}