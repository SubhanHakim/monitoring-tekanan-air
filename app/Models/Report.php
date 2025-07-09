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
        'report_format',
        'metrics',
        'data_source',
        'description',
        'device_id',
        'device_group_id',
        'parameters',
        'data',
        'start_date',
        'end_date',
        'last_generated_at',
        'last_generated_file',
        'is_scheduled',
        'schedule_frequency',
        'recipients',
        'email_on_completion',
        'email_recipients',        // ✅ TAMBAHKAN INI
        'include_anomalies',       // ✅ TAMBAHKAN INI
        'anomaly_threshold',
        'include_charts',
        'chart_type',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
        'metrics' => 'array',
        'recipients' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_generated_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'email_on_completion' => 'boolean',
        'include_anomalies' => 'boolean',    // ✅ TAMBAHKAN INI
        'include_charts' => 'boolean',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Device::class);
    }

    public function deviceGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DeviceGroup::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // Accessor untuk nama data source
    public function getDataSourceNameAttribute(): string
    {
        return match ($this->data_source) {
            'all' => 'Semua Perangkat',
            'device' => $this->device?->name ?? 'Perangkat Tidak Ditemukan',
            'group' => $this->deviceGroup?->name ?? 'Grup Tidak Ditemukan',
            default => 'Tidak Diketahui',
        };
    }

    // Accessor untuk metrics yang dipilih
    public function getSelectedMetricsAttribute(): array
    {
        if (!$this->metrics) return [];

        $metricLabels = [
            'flowrate' => 'Flowrate (l/s)',
            'pressure1' => 'Tekanan 1 (bar)',
            'pressure2' => 'Tekanan 2 (bar)',
            'totalizer' => 'Totalizer (m³)',
            'battery' => 'Battery (Volt)',
        ];

        return collect($this->metrics)
            ->map(fn($metric) => $metricLabels[$metric] ?? $metric)
            ->toArray();
    }

    // Accessor untuk format laporan
    public function getReportFormatLabelAttribute(): string
    {
        return match ($this->report_format) {
            'summary' => 'Ringkasan',
            'detailed' => 'Detail',
            'statistical' => 'Statistik',
            default => 'Ringkasan',
        };
    }

    // Scope untuk laporan yang dijadwalkan
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    // Scope untuk laporan berdasarkan user
    public function scopeForUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
}
