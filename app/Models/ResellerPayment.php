<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les paiements des revendeurs (remboursement de crédit)
 */
class ResellerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'user_id',
        'shop_id',
        'sale_id',
        'amount',
        'cash_amount',
        'return_amount',
        'has_product_return',
        'debt_before',
        'debt_after',
        'payment_method',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount'             => 'decimal:2',
        'cash_amount'        => 'decimal:2',
        'return_amount'      => 'decimal:2',
        'has_product_return' => 'boolean',
        'debt_before'        => 'decimal:2',
        'debt_after'         => 'decimal:2',
        'cancelled_at'       => 'datetime',
    ];

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }

    // Relations
    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class);
    }

    public function productReturns()
    {
        return $this->hasMany(ProductReturn::class, 'reseller_payment_id');
    }

    public function cashTransaction()
    {
        return $this->morphOne(CashTransaction::class, 'transactionable');
    }

    // Accessors
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'card' => 'Carte',
            default => 'Autre',
        };
    }

    // Scopes
    public function scopeForReseller($query, $resellerId)
    {
        return $query->where('reseller_id', $resellerId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    // Méthodes statiques
    public static function recordPayment(Reseller $reseller, User $user, float $amount, string $paymentMethod, ?Sale $sale = null, ?string $notes = null): self
    {
        $debtBefore = $reseller->current_debt;
        
        // Réduire la dette globale
        $reseller->reduceDebt($amount);

        // Distribuer le paiement sur les ventes à crédit (les plus anciennes d'abord)
        self::distributePaymentToSales($reseller, $amount);

        return self::create([
            'reseller_id' => $reseller->id,
            'user_id' => $user->id,
            'sale_id' => $sale?->id,
            'amount' => $amount,
            'debt_before' => $debtBefore,
            'debt_after' => $reseller->fresh()->current_debt,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);
    }

    /**
     * Distribue le paiement sur les ventes à crédit d'une boutique (FIFO).
     * Si $shopId est null, toutes les boutiques sont ciblées (usage admin).
     */
    public static function distributePaymentToSales(Reseller $reseller, float $amount, ?int $shopId = null): void
    {
        $remainingAmount = $amount;

        $salesWithDebt = Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $reseller->id)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->where('payment_status', 'credit')
            ->where('amount_due', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($salesWithDebt as $sale) {
            if ($remainingAmount <= 0) {
                break;
            }

            $debtOnSale = (float) $sale->amount_due;
            $paymentForSale = min($remainingAmount, $debtOnSale);

            // Mettre à jour la vente
            $newAmountPaid = (float) $sale->amount_paid + $paymentForSale;
            $newAmountDue = (float) $sale->amount_due - $paymentForSale;

            $updateData = [
                'amount_paid' => $newAmountPaid,
                'amount_due' => max(0, $newAmountDue),
            ];

            // Si la vente est entièrement payée, changer le statut
            if ($newAmountDue <= 0) {
                $updateData['payment_status'] = 'paid';
            }

            $sale->update($updateData);

            $remainingAmount -= $paymentForSale;
        }
    }
}
