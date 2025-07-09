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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function reports()
    {
        return $this->hasMany(UnitReport::class);
    }
    

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    // ✅ TAMBAH METHOD INI
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ✅ TAMBAH METHOD HELPER LAINNYA
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

     public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'maintenance' => 'Maintenance',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'inactive' => 'danger',
            'maintenance' => 'warning',
            default => 'secondary'
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
