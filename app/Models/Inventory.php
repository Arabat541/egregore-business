<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Modèle pour les inventaires
 */
class Inventory extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'user_id',
        'reference',
        'status',
        'started_at',
        'completed_at',
        'validated_at',
        'validated_by',
        'total_products',
        'products_with_difference',
        'total_difference_value',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'validated_at' => 'datetime',
        'total_difference_value' => 'decimal:2',
    ];

    // Constantes de statut
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_VALIDATED = 'validated';
    const STATUS_CANCELLED = 'cancelled';

    // Relations
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    // Scopes
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeValidated($query)
    {
        return $query->where('status', self::STATUS_VALIDATED);
    }

    // Méthodes
    public static function generateReference()
    {
        $year = date('Y');
        $lastInventory = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastInventory ? (intval(substr($lastInventory->reference, -4)) + 1) : 1;
        
        return sprintf('INV-%s-%04d', $year, $number);
    }

    /**
     * Compléter l'inventaire et calculer les statistiques
     */
    public function complete()
    {
        $this->items()->whereNull('physical_quantity')->update([
            'physical_quantity' => 0,
            'counted' => true,
        ]);

        // Calculer les différences
        foreach ($this->items as $item) {
            $item->difference = $item->physical_quantity - $item->theoretical_quantity;
            $item->difference_value = $item->difference * $item->product->normal_price;
            $item->save();
        }

        // Statistiques
        $this->total_products = $this->items()->count();
        $this->products_with_difference = $this->items()->where('difference', '!=', 0)->count();
        $this->total_difference_value = $this->items()->sum('difference_value');
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Valider l'inventaire et corriger le stock
     */
    public function validate($userId)
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            throw new \Exception('L\'inventaire doit être complété avant validation.');
        }

        DB::beginTransaction();
        try {
            foreach ($this->items()->where('difference', '!=', 0)->get() as $item) {
                $product = $item->product;
                $oldStock = $product->quantity_in_stock;
                $newStock = $item->physical_quantity;

                // Mettre à jour le stock
                $product->quantity_in_stock = $newStock;
                $product->save();

                // Créer un mouvement de stock
                StockMovement::create([
                    'shop_id' => $this->shop_id,
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => StockMovement::TYPE_INVENTORY,
                    'quantity' => $item->difference,
                    'quantity_before' => $oldStock,
                    'quantity_after' => $newStock,
                    'reference' => $this->reference,
                    'reason' => "Correction inventaire #{$this->reference}",
                ]);
            }

            $this->status = self::STATUS_VALIDATED;
            $this->validated_at = now();
            $this->validated_by = $userId;
            $this->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Annuler l'inventaire
     */
    public function cancel()
    {
        if ($this->status === self::STATUS_VALIDATED) {
            throw new \Exception('Impossible d\'annuler un inventaire validé.');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    // Accesseurs
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_COMPLETED => 'Terminé',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_CANCELLED => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_VALIDATED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function getProgressAttribute()
    {
        $total = $this->items()->count();
        if ($total === 0) return 0;
        
        $counted = $this->items()->where('counted', true)->count();
        return round(($counted / $total) * 100);
    }
}
