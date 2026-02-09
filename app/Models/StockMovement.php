<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour l'historique des mouvements de stock
 */
class StockMovement extends Model
{
    use HasFactory, BelongsToShop;

    const TYPE_ENTRY = 'entry';
    const TYPE_EXIT = 'exit';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_REPAIR_USAGE = 'repair_usage';

    protected $fillable = [
        'shop_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference',
        'moveable_type',
        'moveable_id',
        'reason',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moveable()
    {
        return $this->morphTo();
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ENTRY => 'Entrée',
            self::TYPE_EXIT => 'Sortie',
            self::TYPE_ADJUSTMENT => 'Ajustement',
            self::TYPE_REPAIR_USAGE => 'Utilisation réparation',
            default => 'Inconnu',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ENTRY => 'success',
            self::TYPE_EXIT => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            self::TYPE_REPAIR_USAGE => 'info',
            default => 'secondary',
        };
    }

    // Scopes
    public function scopeEntries($query)
    {
        return $query->where('type', self::TYPE_ENTRY);
    }

    public function scopeExits($query)
    {
        return $query->where('type', self::TYPE_EXIT);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Méthodes statiques
    public static function recordEntry(Product $product, User $user, int $quantity, ?string $reason = null): self
    {
        $quantityBefore = $product->quantity_in_stock;
        $product->incrementStock($quantity);

        return self::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => self::TYPE_ENTRY,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $product->quantity_in_stock,
            'reason' => $reason,
        ]);
    }

    public static function recordExit(Product $product, User $user, int $quantity, $moveable = null, ?string $reason = null): self
    {
        $quantityBefore = $product->quantity_in_stock;
        $product->decrementStock($quantity);

        return self::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => self::TYPE_EXIT,
            'quantity' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $product->quantity_in_stock,
            'moveable_type' => $moveable ? get_class($moveable) : null,
            'moveable_id' => $moveable?->id,
            'reason' => $reason,
        ]);
    }

    public static function recordRepairUsage(Product $product, User $user, int $quantity, Repair $repair): self
    {
        $quantityBefore = $product->quantity_in_stock;
        $product->decrementStock($quantity);

        return self::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => self::TYPE_REPAIR_USAGE,
            'quantity' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $product->quantity_in_stock,
            'moveable_type' => Repair::class,
            'moveable_id' => $repair->id,
            'reason' => "Utilisation pour réparation #{$repair->repair_number}",
        ]);
    }
}
