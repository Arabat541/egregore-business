<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ventes en attente pour les revendeurs
 * Permet de cumuler les achats d'un revendeur sur la journée
 * Validé en fin de journée par la caissière
 */
class PendingSale extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'reseller_id',
        'user_id',
        'total_amount',
        'notes',
        'sale_date',
        'status',
        'validated_at',
        'validated_by',
        'sale_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'sale_date' => 'date',
        'validated_at' => 'datetime',
    ];

    // Relations
    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validatedByUser()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(PendingSaleItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    public function scopeForReseller($query, $resellerId)
    {
        return $query->where('reseller_id', $resellerId);
    }

    // Méthodes
    public function recalculateTotal()
    {
        $this->total_amount = $this->items->sum('total_price');
        $this->save();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    /**
     * Obtenir ou créer une vente en attente pour un revendeur aujourd'hui
     */
    public static function getOrCreateForResellerToday(int $resellerId, int $userId, int $shopId): self
    {
        return self::firstOrCreate(
            [
                'reseller_id' => $resellerId,
                'sale_date' => today(),
                'status' => 'pending',
                'shop_id' => $shopId,
            ],
            [
                'user_id' => $userId,
                'total_amount' => 0,
            ]
        );
    }
}
