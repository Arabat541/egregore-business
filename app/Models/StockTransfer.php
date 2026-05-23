<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    const STATUS_PENDING    = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit'; // Expédié par la source, pas encore reçu
    const STATUS_RECEIVED   = 'received';   // Reçu et confirmé par la destination
    const STATUS_COMPLETED  = 'completed';  // Anciens transferts validés (legacy)
    const STATUS_CANCELLED  = 'cancelled';

    protected $fillable = [
        'reference',
        'from_shop_id',
        'to_shop_id',
        'user_id',
        'validated_by',
        'sent_by',
        'received_by',
        'status',
        'validated_at',
        'in_transit_at',
        'received_at',
        'notes',
        'reception_notes',
        'reception_status',
    ];

    protected $casts = [
        'validated_at'  => 'datetime',
        'in_transit_at' => 'datetime',
        'received_at'   => 'datetime',
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
     * Utilisateur qui a validé/expédié le transfert
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
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
            self::STATUS_PENDING    => 'En attente',
            self::STATUS_IN_TRANSIT => 'En transit',
            self::STATUS_RECEIVED   => 'Reçu',
            self::STATUS_COMPLETED  => 'Validé',
            self::STATUS_CANCELLED  => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING    => 'warning',
            self::STATUS_IN_TRANSIT => 'info',
            self::STATUS_RECEIVED   => 'success',
            self::STATUS_COMPLETED  => 'success',
            self::STATUS_CANCELLED  => 'secondary',
            default => 'secondary',
        };
    }

    public function getHasDiscrepancyAttribute(): bool
    {
        return $this->reception_status === 'discrepancy';
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
