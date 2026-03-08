<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historique des prix fournisseurs pour analyse des tendances
 */
class SupplierPriceHistory extends Model
{
    protected $table = 'supplier_price_history';

    protected $fillable = [
        'supplier_id',
        'product_id',
        'unit_price',
        'currency',
        'supplier_order_id',
        'recorded_by',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'recorded_at' => 'datetime',
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

    public function order()
    {
        return $this->belongsTo(SupplierOrder::class, 'supplier_order_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
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

    public function scopeRecent($query, $days = 90)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }

    /**
     * Obtenir l'évolution des prix pour un produit
     */
    public static function getPriceEvolution($productId, $days = 365)
    {
        return static::where('product_id', $productId)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->with('supplier:id,company_name')
            ->orderBy('recorded_at', 'desc')
            ->get();
    }
}
