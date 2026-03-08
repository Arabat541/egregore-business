<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les bonus de fidélité des revendeurs
 * Bonus accordé en fin d'année civile basé sur le total des achats
 */
class ResellerLoyaltyBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'year',
        'yearly_purchases',
        'tier',
        'bonus_rate',
        'bonus_amount',
        'status',
        'payment_method',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'yearly_purchases' => 'decimal:2',
        'bonus_rate' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Relations
    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'paid' => 'Payé',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match($this->payment_type) {
            'cash' => 'Espèces',
            'credit' => 'Crédit sur compte',
            'discount' => 'Remise sur prochains achats',
            default => 'Autre',
        };
    }

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'paid']);
    }

    // Méthodes métier
    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'user_id' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid(string $paymentMethod = 'credit'): void
    {
        $this->update([
            'status' => 'paid',
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
        ]);

        // Si payé en crédit, réduire la dette du revendeur
        if ($paymentMethod === 'credit') {
            $this->reseller->reduceDebt((float) $this->bonus_amount);
        }
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
