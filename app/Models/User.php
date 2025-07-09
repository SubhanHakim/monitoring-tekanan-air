<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'notes',
        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function isUnitUser()
    {
        return $this->unit_id !== null;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // ✅ TAMBAH METHOD hasRole() YANG DIPERLUKAN
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // ✅ ALTERNATIVE METHOD untuk multiple roles
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    // ✅ METHOD untuk check role dengan array
    public function hasRoles(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    // ✅ GETTER untuk role label
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'unit_user' => 'Unit User',
            'super_admin' => 'Super Administrator',
            default => 'User'
        };
    }

    // ✅ SCOPES
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeUnitUsers($query)
    {
        return $query->where('role', 'unit_user');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // ✅ HELPER METHODS
    public function canAccessUnit(Unit $unit): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isUnitUser()) {
            return $this->unit_id === $unit->id;
        }
        
        return false;
    }

    public function getUnitNameAttribute(): string
    {
        return $this->unit ? $this->unit->name : 'No Unit';
    }
}
