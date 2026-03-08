<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les retours de produits par les revendeurs
 * Permet de réduire une dette en retournant des produits non vendus
 */
class ProductReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'reseller_payment_id',
        'sale_id',
        'sale_item_id',
        'product_id',
        'user_id',
        'shop_id',
        'quantity',
        'unit_price',
        'total_value',
        'condition',
        'restock',
        'reason',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'restock' => 'boolean',
    ];

    const CONDITION_NEW = 'new';
    const CONDITION_GOOD = 'good';
    const CONDITION_DAMAGED = 'damaged';

    // Relations
    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function payment()
    {
        return $this->belongsTo(ResellerPayment::class, 'reseller_payment_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // Accessors
    public function getConditionLabelAttribute(): string
    {
        return match($this->condition) {
            self::CONDITION_NEW => 'Neuf',
            self::CONDITION_GOOD => 'Bon état',
            self::CONDITION_DAMAGED => 'Endommagé',
            default => 'Inconnu',
        };
    }

    public function getConditionBadgeClassAttribute(): string
    {
        return match($this->condition) {
            self::CONDITION_NEW => 'bg-success',
            self::CONDITION_GOOD => 'bg-info',
            self::CONDITION_DAMAGED => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    // Scopes
    public function scopeForReseller($query, $resellerId)
    {
        return $query->where('reseller_id', $resellerId);
    }

    public function scopeRestocked($query)
    {
        return $query->where('restock', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Boot - Gérer la remise en stock
    protected static function boot()
    {
        parent::boot();

        static::created(function ($return) {
            if ($return->restock && $return->condition !== self::CONDITION_DAMAGED) {
                $product = $return->product;
                $quantityBefore = (int) $product->quantity_in_stock;
                
                // Remettre le produit en stock
                $product->increment('quantity_in_stock', $return->quantity);
                
                $quantityAfter = $quantityBefore + $return->quantity;
                
                // Enregistrer le mouvement de stock
                StockMovement::create([
                    'product_id' => $return->product_id,
                    'shop_id' => $return->shop_id,
                    'user_id' => $return->user_id,
                    'type' => 'return',
                    'quantity' => $return->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'moveable_type' => ProductReturn::class,
                    'moveable_id' => $return->id,
                    'reference' => 'RET-' . str_pad($return->id, 6, '0', STR_PAD_LEFT),
                    'reason' => "Retour revendeur: {$return->reseller->company_name}",
                ]);
            }
        });
    }
}
