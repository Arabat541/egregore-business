<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les boutiques (multi-tenant)
 */
class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'logo',
        'description',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    // Relations
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function resellers()
    {
        return $this->hasMany(Reseller::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class);
    }

    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    public function savTickets()
    {
        return $this->hasMany(SavTicket::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting($key, $value)
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Statistiques de la boutique
     */
    public function getStats()
    {
        return [
            'users_count' => $this->users()->count(),
            'products_count' => $this->products()->count(),
            'sales_today' => $this->sales()->whereDate('created_at', today())->count(),
            'revenue_today' => $this->sales()->whereDate('created_at', today())->sum('total_amount'),
            'repairs_pending' => $this->repairs()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
        ];
    }
}
