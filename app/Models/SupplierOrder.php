<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les commandes fournisseurs
 */
class SupplierOrder extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'supplier_id',
        'user_id',
        'reference',
        'status',
        'order_date',
        'expected_date',
        'received_date',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    // Relations
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SupplierOrderItem::class);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'confirmed' => 'Confirmée',
            'received' => 'Reçue',
            'cancelled' => 'Annulée',
            default => 'Inconnu',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'sent' => 'info',
            'confirmed' => 'primary',
            'received' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity_ordered');
    }

    // Scopes
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'confirmed']);
    }

    // Méthodes
    public static function generateReference(): string
    {
        $prefix = 'CMD-' . date('Ym');
        $lastOrder = self::where('reference', 'like', $prefix . '%')
            ->orderByDesc('reference')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->reference, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotal(): void
    {
        $this->total_amount = $this->items->sum('total_price');
        $this->save();
    }

    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    public function markAsReceived(): void
    {
        $this->update([
            'status' => self::STATUS_RECEIVED,
            'received_date' => now(),
        ]);
    }
}
