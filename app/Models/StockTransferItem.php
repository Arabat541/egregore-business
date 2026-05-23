<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity',
        'quantity_received',
        'purchase_price',
        'notes',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'quantity_received' => 'integer',
        'purchase_price'    => 'decimal:2',
    ];

    /**
     * Transfert parent
     */
    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    /**
     * Produit
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Valeur totale de cet item
     */
    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->purchase_price;
    }

    public function getHasDiscrepancyAttribute(): bool
    {
        return $this->quantity_received !== null && $this->quantity_received !== $this->quantity;
    }
}
