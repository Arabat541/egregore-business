<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les transactions de caisse
 */
class CashTransaction extends Model
{
    use HasFactory;

    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    const CATEGORY_SALE = 'sale';
    const CATEGORY_REPAIR = 'repair';
    const CATEGORY_DEBT_PAYMENT = 'debt_payment';
    const CATEGORY_EXPENSE = 'expense';
    const CATEGORY_ADJUSTMENT = 'adjustment';
    const CATEGORY_SAV_REFUND = 'sav_refund';

    protected $fillable = [
        'cash_register_id',
        'user_id',
        'type',
        'category',
        'amount',
        'payment_method',
        'transactionable_type',
        'transactionable_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relations
    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable()
    {
        return $this->morphTo();
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_SALE => 'Vente',
            self::CATEGORY_REPAIR => 'Réparation',
            self::CATEGORY_DEBT_PAYMENT => 'Paiement créance',
            self::CATEGORY_EXPENSE => 'Dépense',
            self::CATEGORY_ADJUSTMENT => 'Ajustement',
            self::CATEGORY_SAV_REFUND => 'Remboursement SAV',
            default => 'Autre',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_INCOME ? 'Entrée' : 'Sortie';
    }

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
    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
