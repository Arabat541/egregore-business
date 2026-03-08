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
        'sku',
        'category_id',
        'description',
        'purchase_price',
        'normal_price',           // Prix normal client (1-2 pièces)
        'reseller_price',         // Prix réparateur/revendeur (1-2 pièces)
        'semi_wholesale_price',   // Prix demi-gros (3-9 pièces)
        'wholesale_price',        // Prix de gros (10+ pièces)
        'quantity_in_stock',
        'stock_alert_threshold',
        'brand',
        'model',
        'type',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'normal_price' => 'decimal:2',
        'semi_wholesale_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'reseller_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Seuils de quantité pour les prix
     */
    const QUANTITY_NORMAL_MAX = 2;        // 1-2 pièces = prix normal
    const QUANTITY_SEMI_WHOLESALE_MIN = 3; // 3-9 pièces = prix demi-gros
    const QUANTITY_SEMI_WHOLESALE_MAX = 9;
    const QUANTITY_WHOLESALE_MIN = 10;     // 10+ pièces = prix de gros

    /**
     * Obtenir le prix approprié selon la quantité
     */
    public function getPriceForQuantity(int $quantity): float
    {
        if ($quantity >= self::QUANTITY_WHOLESALE_MIN && $this->wholesale_price) {
            return (float) $this->wholesale_price;
        }
        
        if ($quantity >= self::QUANTITY_SEMI_WHOLESALE_MIN && $this->semi_wholesale_price) {
            return (float) $this->semi_wholesale_price;
        }
        
        return (float) $this->normal_price;
    }

    /**
     * Obtenir le type de prix selon la quantité
     */
    public function getPriceTypeForQuantity(int $quantity): string
    {
        if ($quantity >= self::QUANTITY_WHOLESALE_MIN) {
            return 'wholesale';
        }
        
        if ($quantity >= self::QUANTITY_SEMI_WHOLESALE_MIN) {
            return 'semi_wholesale';
        }
        
        return 'normal';
    }

    /**
     * Obtenir le libellé du type de prix
     */
    public static function getPriceTypeLabel(string $type): string
    {
        return match($type) {
            'wholesale' => 'Prix de gros (10+)',
            'semi_wholesale' => 'Prix demi-gros (3-9)',
            'reseller' => 'Prix réparateur (1-2)',
            'normal' => 'Prix normal (1-2)',
            default => 'Prix normal',
        };
    }

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
     * Boutique associée (via trait BelongsToShop)
     * Note: La relation shop() est définie dans le trait BelongsToShop
     */

    /**
     * Obtenir le prix minimum (seuil) - utilise le prix normal comme référence
     */
    public function getMinimumPriceForShop(?int $shopId): ?float
    {
        // Le produit est déjà associé à une boutique spécifique
        // Le prix de gros est le prix minimum acceptable
        return $this->wholesale_price ?? $this->semi_wholesale_price ?? $this->normal_price;
    }

    /**
     * Obtenir le prix de vente pour une boutique spécifique
     * Retourne le prix normal comme prix de référence
     */
    public function getSellingPriceForShop(?int $shopId): float
    {
        return (float) ($this->normal_price ?? 0);
    }

    /**
     * Obtenir le stock pour une boutique spécifique
     */
    public function getStockForShop(?int $shopId): int
    {
        if (!$shopId || $this->shop_id == $shopId) {
            return $this->quantity_in_stock;
        }
        return 0;
    }

    /**
     * Vérifier si le prix est en dessous du seuil minimum (prix de gros)
     */
    public function isPriceBelowMinimum(float $price, ?int $shopId = null): bool
    {
        $minimumPrice = $this->wholesale_price ?? $this->semi_wholesale_price ?? $this->normal_price;
        return $price < $minimumPrice;
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
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%");
        });
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
        return (($this->normal_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    public function getResellerPriceOrDefaultAttribute(): float
    {
        return (float) ($this->reseller_price ?? $this->normal_price);
    }

    // Méthodes métier
    public function getPriceForClient(string $clientType): float
    {
        if ($clientType === 'reseller') {
            return (float) $this->reseller_price_or_default;
        }
        return (float) $this->normal_price;
    }

    /**
     * Obtenir le prix selon le type de client ET la quantité
     * 
     * Structure des prix:
     * - Client normal: toujours prix normal
     * - Revendeur/Réparateur:
     *   - 1-2 pcs: prix réparateur (ou normal si non défini)
     *   - 3-9 pcs: prix demi-gros (ou réparateur ou normal)
     *   - 10+ pcs: prix de gros (ou demi-gros ou réparateur ou normal)
     */
    public function getPriceForClientAndQuantity(string $clientType, int $quantity): float
    {
        // Client normal = toujours prix normal
        if ($clientType !== 'reseller') {
            return (float) $this->normal_price;
        }

        // Revendeur: appliquer la grille dégressive
        if ($quantity >= self::QUANTITY_WHOLESALE_MIN && $this->wholesale_price) {
            return (float) $this->wholesale_price;
        }

        if ($quantity >= self::QUANTITY_SEMI_WHOLESALE_MIN) {
            return (float) ($this->semi_wholesale_price ?? $this->reseller_price ?? $this->normal_price);
        }

        // 1-2 pièces pour revendeur
        return (float) $this->reseller_price_or_default;
    }

    /**
     * Obtenir le type de prix appliqué selon client et quantité
     */
    public function getPriceTypeForClientAndQuantity(string $clientType, int $quantity): string
    {
        if ($clientType !== 'reseller') {
            return 'normal';
        }

        if ($quantity >= self::QUANTITY_WHOLESALE_MIN && $this->wholesale_price) {
            return 'wholesale';
        }

        if ($quantity >= self::QUANTITY_SEMI_WHOLESALE_MIN && $this->semi_wholesale_price) {
            return 'semi_wholesale';
        }

        return 'reseller';
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
