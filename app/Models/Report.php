<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'device_id',
        'device_group_id',
        'parameters',
        'data',
        'start_date',
        'end_date',
        'last_generated_at',
        'is_scheduled',
        'schedule_frequency',
        'recipients',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_generated_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'recipients' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function deviceGroup(): BelongsTo
    {
        return $this->belongsTo(DeviceGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Method untuk menghasilkan laporan
    public function generateReport()
    {
        // Ubah generate menjadi generateReport
        $reportService = new \App\Services\ReportService();
        $data = $reportService->generateReport($this);

        $this->update([
            'data' => $data,
            'last_generated_at' => now(),
        ]);

        return $data;
    }
}
