<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Modèle pour les revendeurs
 * Peuvent acheter à crédit avec plafond défini par admin
 * Système de fidélité avec bonus en fin d'année civile
 */
class Reseller extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_name',
        'phone',
        'email',
        'address',
        'tax_number',
        'credit_limit',
        'current_debt',
        'total_purchases_year',
        'loyalty_points',
        'loyalty_bonus_rate',
        'loyalty_year_start',
        'notes',
        'is_active',
        'credit_allowed',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_debt' => 'decimal:2',
        'total_purchases_year' => 'decimal:2',
        'loyalty_points' => 'decimal:2',
        'loyalty_bonus_rate' => 'decimal:2',
        'loyalty_year_start' => 'date',
        'is_active' => 'boolean',
        'credit_allowed' => 'boolean',
    ];

    // Relations
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(ResellerPayment::class);
    }

    public function loyaltyBonuses()
    {
        return $this->hasMany(ResellerLoyaltyBonus::class);
    }

    // Accessors
    public function getAvailableCreditAttribute(): float
    {
        if (!$this->credit_allowed) {
            return 0;
        }
        return max(0, $this->credit_limit - $this->current_debt);
    }

    public function getCreditUsagePercentageAttribute(): float
    {
        if ($this->credit_limit <= 0) {
            return 0;
        }
        return min(100, ($this->current_debt / $this->credit_limit) * 100);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithDebt($query)
    {
        return $query->where('current_debt', '>', 0);
    }

    public function scopeCreditAllowed($query)
    {
        return $query->where('credit_allowed', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('company_name', 'like', "%{$search}%")
              ->orWhere('contact_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // Méthodes métier
    /**
     * Dette du reseller dans une boutique spécifique, calculée dynamiquement
     * à partir des ventes dont le montant restant est > 0.
     */
    public function getShopDebt(int $shopId): float
    {
        return (float) Sale::withoutGlobalScope('shop')
            ->where('reseller_id', $this->id)
            ->where('shop_id', $shopId)
            ->where('amount_due', '>', 0)
            ->sum('amount_due');
    }

    public function canPurchaseOnCredit(float $amount): bool
    {
        if (!$this->credit_allowed || !$this->is_active) {
            return false;
        }
        return $this->available_credit >= $amount;
    }

    public function addDebt(float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->increment('current_debt', $amount);
    }

    public function reduceDebt(float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        // Clamp to 0: only decrement by what's actually owed
        $actual = min($amount, (float) $this->current_debt);
        if ($actual > 0) {
            $this->decrement('current_debt', $actual);
        }
    }

    /**
     * Alias de reduceDebt() — cohérence de nommage avec addDebt().
     * Utilisé dans SaleController lors d'annulations et modifications.
     */
    public function removeDebt(float $amount): void
    {
        $this->reduceDebt($amount);
    }

    /**
     * Inverser un achat (annulation de vente) : déduit du total annuel et des points.
     */
    public function removePurchase(float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($amount): void {
            $currentYear = now()->year;
            if ($this->loyalty_year_start && $this->loyalty_year_start->year === $currentYear) {
                $decrement = min($amount, (float) $this->total_purchases_year);
                if ($decrement > 0) {
                    $this->decrement('total_purchases_year', $decrement);
                }
            }

            $pointsToRemove = min($amount / 10000, (float) $this->loyalty_points);
            if ($pointsToRemove > 0) {
                $this->decrement('loyalty_points', $pointsToRemove);
            }

            $this->refresh();
            $this->updateLoyaltyBonusRate();
        });
    }

    /**
     * Ajouter un achat au total annuel et calculer les points de fidélité
     */
    public function addPurchase(float $amount): void
    {
        DB::transaction(function () use ($amount): void {
            $currentYear = now()->year;
            if ($this->loyalty_year_start && $this->loyalty_year_start->year < $currentYear) {
                $this->update([
                    'total_purchases_year' => $amount,
                    'loyalty_year_start'   => now()->startOfYear(),
                ]);
            } else {
                $this->increment('total_purchases_year', $amount);
                if (!$this->loyalty_year_start) {
                    $this->update(['loyalty_year_start' => now()->startOfYear()]);
                }
            }

            $this->increment('loyalty_points', $amount / 10000);
            $this->refresh();
            $this->updateLoyaltyBonusRate();
        });
    }

    /**
     * Mettre à jour le taux de bonus de fidélité selon les paliers
     */
    public function updateLoyaltyBonusRate(): void
    {
        $total = $this->total_purchases_year;

        // Paliers de fidélité (à personnaliser selon les besoins)
        $rate = match(true) {
            $total >= 50000000 => 5.0,   // 50M+ : 5% de bonus
            $total >= 25000000 => 4.0,   // 25M+ : 4% de bonus
            $total >= 10000000 => 3.0,   // 10M+ : 3% de bonus
            $total >= 5000000 => 2.0,    // 5M+ : 2% de bonus
            $total >= 2000000 => 1.5,    // 2M+ : 1.5% de bonus
            $total >= 1000000 => 1.0,    // 1M+ : 1% de bonus
            default => 0,
        };

        $this->update(['loyalty_bonus_rate' => $rate]);
    }

    /**
     * Calculer le bonus de fidélité potentiel
     */
    public function getExpectedBonusAttribute(): float
    {
        return $this->total_purchases_year * ($this->loyalty_bonus_rate / 100);
    }

    /**
     * Obtenir le relevé de compte (toutes les transactions)
     */
    public function getAccountStatement($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->startOfYear();
        $endDate = $endDate ?? now();

        // Récupérer les ventes
        $sales = $this->sales()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('id', 'invoice_number', 'total_amount', 'amount_paid', 'payment_status', 'created_at')
            ->get()
            ->map(function ($sale) {
                return [
                    'date' => $sale->created_at,
                    'type' => 'sale',
                    'reference' => $sale->invoice_number,
                    'debit' => $sale->total_amount - $sale->amount_paid, // Dette ajoutée
                    'credit' => 0,
                    'description' => 'Vente - ' . $sale->invoice_number,
                    'model' => $sale,
                ];
            });

        // Récupérer les paiements
        $payments = $this->payments()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('id', 'reference', 'amount', 'payment_method', 'created_at')
            ->get()
            ->map(function ($payment) {
                return [
                    'date' => $payment->created_at,
                    'type' => 'payment',
                    'reference' => $payment->reference,
                    'debit' => 0,
                    'credit' => $payment->amount, // Dette réduite
                    'description' => 'Paiement - ' . ($payment->reference ?? 'N/A'),
                    'model' => $payment,
                ];
            });

        // Récupérer les bonus payés
        $bonuses = $this->loyaltyBonuses()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->get()
            ->map(function ($bonus) {
                return [
                    'date' => $bonus->paid_at,
                    'type' => 'bonus',
                    'reference' => 'BONUS-' . $bonus->year,
                    'debit' => 0,
                    'credit' => $bonus->payment_type === 'credit' ? $bonus->bonus_amount : 0,
                    'description' => 'Bonus fidélité ' . $bonus->year,
                    'model' => $bonus,
                ];
            });

        // Fusionner et trier par date
        return $sales->concat($payments)->concat($bonuses)->sortBy('date')->values();
    }

    // Stats
    public function getTotalPurchasesAttribute(): float
    {
        return $this->sales()->sum('total_amount');
    }

    public function getTotalPaymentsAttribute(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Obtenir le palier de fidélité actuel
     */
    public function getLoyaltyTierAttribute(): string
    {
        // Si les données de fidélité sont d'une année antérieure, on retombe sur Nouveau
        if ($this->loyalty_year_start && $this->loyalty_year_start->year < now()->year) {
            return 'Nouveau';
        }

        $total = (float) $this->total_purchases_year;

        return match(true) {
            $total >= 50000000 => 'Platine',
            $total >= 25000000 => 'Or',
            $total >= 10000000 => 'Argent',
            $total >= 5000000 => 'Bronze',
            $total >= 1000000 => 'Standard',
            default => 'Nouveau',
        };
    }

    public function getLoyaltyTierColorAttribute(): string
    {
        return match($this->loyalty_tier) {
            'Platine' => 'purple',
            'Or' => 'warning',
            'Argent' => 'secondary',
            'Bronze' => 'dark',
            'Standard' => 'info',
            default => 'light',
        };
    }
}
