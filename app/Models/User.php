<?php
// filepath: app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'notes',
        'unit_id',  // ✅ TAMBAHKAN INI
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',  // ✅ TAMBAHKAN CAST
        ];
    }

    // ✅ PERBAIKI RELASI - TAMBAHKAN TYPE HINT
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function isUnitUser(): bool
    {
        return $this->unit_id !== null;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // ✅ PERBAIKI ROLE CHECK - TAMBAHKAN UNIT ROLE
    public function isUnit(): bool
    {
        return $this->hasRole('unit');
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function hasRoles(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    // ✅ UPDATE ROLE LABELS
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'unit' => 'Unit User',  // ✅ UBAH DARI unit_user KE unit
            'super_admin' => 'Super Administrator',
            default => 'User'
        };
    }

    // ✅ UPDATE SCOPES
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeUnitUsers($query)
    {
        return $query->where('role', 'unit');  // ✅ UBAH KE 'unit'
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ✅ PERBAIKI HELPER METHODS
    public function canAccessUnit(Unit $unit): bool
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            return true;
        }
        
        if ($this->isUnit()) {
            return $this->unit_id === $unit->id;
        }
        
        return false;
    }

    public function getUnitNameAttribute(): string
    {
        return $this->unit?->name ?? 'No Unit';  // ✅ USE NULL SAFE OPERATOR
    }

    // ✅ TAMBAHAN HELPER METHODS
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Tidak Aktif';
    }

    public function canViewPanel(): bool
    {
        return $this->is_active && in_array($this->role, ['admin', 'unit', 'super_admin']);
    }
}