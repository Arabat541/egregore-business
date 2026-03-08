<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Lignes de vente en attente
 */
class PendingSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pending_sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->total_price)) {
                $item->total_price = ($item->unit_price * $item->quantity) - $item->discount;
            }
        });

        static::saved(function ($item) {
            $item->pendingSale->recalculateTotal();
        });

        static::deleted(function ($item) {
            $item->pendingSale->recalculateTotal();
        });
    }

    // Relations
    public function pendingSale()
    {
        return $this->belongsTo(PendingSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        return $this->unit_price * $this->quantity;
    }

    public function getNetTotalAttribute(): float
    {
        return $this->subtotal - $this->discount;
    }
}
