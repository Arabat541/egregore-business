<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'last_login_at',
        'shop_id',
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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Relations
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function repairsCreated()
    {
        return $this->hasMany(Repair::class, 'created_by');
    }

    public function repairsAssigned()
    {
        return $this->hasMany(Repair::class, 'technician_id');
    }

    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTechnicians($query)
    {
        return $query->role('technicien');
    }

    public function scopeCashiers($query)
    {
        return $query->role('caissiere');
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isCashier(): bool
    {
        return $this->hasRole('caissiere');
    }

    public function isTechnician(): bool
    {
        return $this->hasRole('technicien');
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Nom de la boutique de l'utilisateur
     */
    public function getShopNameAttribute(): string
    {
        return $this->shop?->name ?? 'Toutes boutiques';
    }

    /**
     * Scope pour filtrer par boutique
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope pour les utilisateurs sans boutique (admins)
     */
    public function scopeWithoutShop($query)
    {
        return $query->whereNull('shop_id');
    }
}
