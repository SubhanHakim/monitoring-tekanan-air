<?php
// filepath: app/Models/Unit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'status',
        'koordinate',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ UBAH KE hasMany UNTUK MULTIPLE USERS PER UNIT
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_id');
    }

    // ✅ JIKA MASIH PERLU 1 USER UTAMA, BISA GUNAKAN INI
    public function primaryUser(): HasOne
    {
        return $this->hasOne(User::class, 'unit_id')->where('is_primary', true);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(UnitReport::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'unit_id');
    }

    // ✅ METHOD HELPER
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

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

    // ✅ TAMBAH METHOD UNTUK COUNT USERS
    public function getActiveUsersCountAttribute(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    public function getTotalUsersCountAttribute(): int
    {
        return $this->users()->count();
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

    public function scopeWithUsersCount($query)
    {
        return $query->withCount('users');
    }
}