<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Prix actuel d'un produit chez un fournisseur
 */
class SupplierProductPrice extends Model
{
    protected $fillable = [
        'supplier_id',
        'product_id',
        'unit_price',
        'last_price',
        'currency',
        'min_order_quantity',
        'lead_time_days',
        'notes',
        'price_updated_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'last_price' => 'decimal:2',
        'min_order_quantity' => 'integer',
        'lead_time_days' => 'integer',
        'price_updated_at' => 'datetime',
    ];

    // Relations
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    
    /**
     * Variation de prix en pourcentage
     */
    public function getPriceVariationAttribute(): ?float
    {
        if (!$this->last_price || $this->last_price == 0) {
            return null;
        }
        return (($this->unit_price - $this->last_price) / $this->last_price) * 100;
    }

    /**
     * Indique si le prix a augmenté
     */
    public function getHasIncreasedAttribute(): bool
    {
        return $this->last_price && $this->unit_price > $this->last_price;
    }

    /**
     * Indique si le prix a baissé
     */
    public function getHasDecreasedAttribute(): bool
    {
        return $this->last_price && $this->unit_price < $this->last_price;
    }

    // Scopes
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Obtenir le fournisseur le moins cher pour un produit
     */
    public static function cheapestForProduct($productId)
    {
        return static::where('product_id', $productId)
            ->orderBy('unit_price', 'asc')
            ->first();
    }

    /**
     * Mettre à jour le prix et garder l'historique
     */
    public function updatePrice(float $newPrice, ?int $orderId = null, ?int $userId = null, ?string $notes = null): void
    {
        // Sauvegarder dans l'historique
        SupplierPriceHistory::create([
            'supplier_id' => $this->supplier_id,
            'product_id' => $this->product_id,
            'unit_price' => $newPrice,
            'currency' => $this->currency,
            'supplier_order_id' => $orderId,
            'recorded_by' => $userId,
            'notes' => $notes,
            'recorded_at' => now(),
        ]);

        // Mettre à jour le prix actuel
        $this->last_price = $this->unit_price;
        $this->unit_price = $newPrice;
        $this->price_updated_at = now();
        $this->save();
    }
}
