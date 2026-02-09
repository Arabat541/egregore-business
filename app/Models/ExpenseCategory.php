<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'name',
        'icon',
        'color',
        'description',
        'is_active',
        'requires_approval',
        'monthly_budget',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'monthly_budget' => 'decimal:2',
    ];

    /**
     * Relation avec la boutique
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Relation avec les dépenses
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Obtenir les dépenses du mois en cours
     */
    public function currentMonthExpenses()
    {
        return $this->expenses()
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
    }

    /**
     * Vérifier si le budget mensuel est dépassé
     */
    public function isBudgetExceeded(): bool
    {
        if (!$this->monthly_budget) {
            return false;
        }
        return $this->currentMonthExpenses() > $this->monthly_budget;
    }

    /**
     * Pourcentage du budget utilisé
     */
    public function budgetUsagePercentage(): float
    {
        if (!$this->monthly_budget || $this->monthly_budget == 0) {
            return 0;
        }
        return min(100, ($this->currentMonthExpenses() / $this->monthly_budget) * 100);
    }

    /**
     * Scope pour les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
