<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les produits (téléphones, accessoires, pièces détachées)
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'name',
        'barcode',
        'sku',
        'category_id',
        'description',
        'purchase_price',
        'selling_price',
        'reseller_price',
        'quantity_in_stock',
        'stock_alert_threshold',
        'brand',
        'model',
        'type',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reseller_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function repairParts()
    {
        return $this->hasMany(RepairPart::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Prix de ce produit chez les fournisseurs
     */
    public function supplierPrices()
    {
        return $this->hasMany(SupplierProductPrice::class);
    }

    /**
     * Fournisseurs de ce produit
     */
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_product_prices')
            ->withPivot('unit_price', 'last_price', 'min_order_quantity', 'lead_time_days', 'price_updated_at')
            ->withTimestamps();
    }

    /**
     * Historique des prix fournisseurs
     */
    public function priceHistory()
    {
        return $this->hasMany(SupplierPriceHistory::class);
    }

    /**
     * Obtenir le fournisseur le moins cher
     */
    public function getCheapestSupplierAttribute()
    {
        return $this->supplierPrices()
            ->with('supplier:id,company_name,phone,whatsapp')
            ->orderBy('unit_price', 'asc')
            ->first();
    }

    /**
     * Obtenir tous les prix fournisseurs triés par prix
     */
    public function getSupplierPricesRankedAttribute()
    {
        return $this->supplierPrices()
            ->with('supplier:id,company_name,phone,whatsapp')
            ->orderBy('unit_price', 'asc')
            ->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity_in_stock', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_in_stock', '<=', 'stock_alert_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_in_stock', '<=', 0);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePhones($query)
    {
        return $query->where('type', 'phone');
    }

    public function scopeAccessories($query)
    {
        return $query->where('type', 'accessory');
    }

    public function scopeSpareParts($query)
    {
        return $query->where('type', 'spare_part');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%");
        });
    }

    public function scopeByBarcode($query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    // Accessors
    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity_in_stock <= $this->stock_alert_threshold;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity_in_stock <= 0;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->purchase_price <= 0) {
            return 0;
        }
        return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    public function getResellerPriceOrDefaultAttribute(): float
    {
        return $this->reseller_price ?? $this->selling_price;
    }

    // Méthodes métier
    public function getPriceForClient(string $clientType): float
    {
        if ($clientType === 'reseller') {
            return $this->reseller_price_or_default;
        }
        return $this->selling_price;
    }

    public function hasStock(int $quantity = 1): bool
    {
        return $this->quantity_in_stock >= $quantity;
    }

    public function decrementStock(int $quantity): void
    {
        $this->decrement('quantity_in_stock', $quantity);
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('quantity_in_stock', $quantity);
    }

    // Stats
    public function getTotalSoldAttribute(): int
    {
        return $this->saleItems()->sum('quantity');
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->saleItems()->sum('total_price');
    }
}
