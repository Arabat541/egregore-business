<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les fournisseurs
 */
class Supplier extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'phone_secondary',
        'whatsapp',
        'address',
        'city',
        'country',
        'notes',
        'categories',
        'is_active',
    ];

    protected $casts = [
        'categories' => 'array',
        'is_active' => 'boolean',
    ];

    // Relations
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function orders()
    {
        return $this->hasMany(SupplierOrder::class);
    }

    /**
     * Prix des produits chez ce fournisseur
     */
    public function productPrices()
    {
        return $this->hasMany(SupplierProductPrice::class);
    }

    /**
     * Produits fournis par ce fournisseur
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_product_prices')
            ->withPivot('unit_price', 'last_price', 'min_order_quantity', 'lead_time_days', 'price_updated_at')
            ->withTimestamps();
    }

    /**
     * Historique des prix
     */
    public function priceHistory()
    {
        return $this->hasMany(SupplierPriceHistory::class);
    }

    // Accessors
    public function getFullContactAttribute(): string
    {
        $contact = $this->company_name;
        if ($this->contact_name) {
            $contact .= ' - ' . $this->contact_name;
        }
        return $contact;
    }

    public function getCategoriesListAttribute(): string
    {
        if (!$this->categories || empty($this->categories)) {
            return 'Toutes catégories';
        }
        return implode(', ', $this->categories);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithEmail($query)
    {
        return $query->whereNotNull('email')->where('email', '!=', '');
    }

    public function scopeWithWhatsapp($query)
    {
        return $query->whereNotNull('whatsapp')->where('whatsapp', '!=', '');
    }
}
