<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference',
        'from_shop_id',
        'to_shop_id',
        'user_id',
        'validated_by',
        'status',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    /**
     * Boutique source
     */
    public function fromShop()
    {
        return $this->belongsTo(Shop::class, 'from_shop_id');
    }

    /**
     * Boutique destination
     */
    public function toShop()
    {
        return $this->belongsTo(Shop::class, 'to_shop_id');
    }

    /**
     * Utilisateur qui a créé le transfert
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Utilisateur qui a validé le transfert
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Articles du transfert
     */
    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /**
     * Label du statut
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_COMPLETED => 'Validé',
            self::STATUS_CANCELLED => 'Annulé',
            default => $this->status,
        };
    }

    /**
     * Couleur du statut
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Valeur totale du transfert
     */
    public function getTotalValueAttribute()
    {
        return $this->items->sum(function($item) {
            return $item->quantity * $item->purchase_price;
        });
    }

    /**
     * Nombre total d'articles
     */
    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }
}
