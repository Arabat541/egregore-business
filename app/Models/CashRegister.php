<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour la gestion de la caisse
 */
class CashRegister extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'user_id',
        'date',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'difference',
        'status',
        'opened_at',
        'closed_at',
        'opening_notes',
        'closing_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(CashTransaction::class);
    }

    // Accessors
    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open';
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 'closed';
    }

    public function getTotalIncomeAttribute(): float
    {
        // Utilise la collection si eager loaded, sinon requête DB
        if ($this->relationLoaded('transactions')) {
            return (float) $this->transactions->where('type', 'income')->sum('amount');
        }
        return (float) $this->transactions()->where('type', 'income')->sum('amount');
    }

    public function getTotalExpenseAttribute(): float
    {
        // Utilise la collection si eager loaded, sinon requête DB
        if ($this->relationLoaded('transactions')) {
            return (float) $this->transactions->where('type', 'expense')->sum('amount');
        }
        return (float) $this->transactions()->where('type', 'expense')->sum('amount');
    }

    public function getCashIncomeAttribute(): float
    {
        if ($this->relationLoaded('transactions')) {
            return (float) $this->transactions
                ->where('type', 'income')
                ->where('payment_method', 'cash')
                ->sum('amount');
        }
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('payment_method', 'cash')
            ->sum('amount');
    }

    public function getMobileMoneyIncomeAttribute(): float
    {
        if ($this->relationLoaded('transactions')) {
            return (float) $this->transactions
                ->where('type', 'income')
                ->where('payment_method', 'mobile_money')
                ->sum('amount');
        }
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('payment_method', 'mobile_money')
            ->sum('amount');
    }

    public function getCardIncomeAttribute(): float
    {
        if ($this->relationLoaded('transactions')) {
            return (float) $this->transactions
                ->where('type', 'income')
                ->where('payment_method', 'card')
                ->sum('amount');
        }
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('payment_method', 'card')
            ->sum('amount');
    }

    public function getCalculatedBalanceAttribute(): float
    {
        return (float) $this->opening_balance + $this->total_income - $this->total_expense;
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    // Méthodes métier
    public static function getOpenRegisterForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Vérifier si une caisse existe pour un utilisateur à une date donnée
     */
    public static function existsForUserAndDate(int $userId, $date = null): bool
    {
        return self::where('user_id', $userId)
            ->whereDate('date', $date ?? today())
            ->exists();
    }

    /**
     * Récupérer la caisse du jour pour un utilisateur (ouverte ou fermée)
     */
    public static function getTodayRegisterForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->whereDate('date', today())
            ->first();
    }

    public static function openRegister(User $user, float $openingBalance, ?string $notes = null): self
    {
        // Vérifier si une caisse existe déjà pour aujourd'hui
        $existingRegister = self::getTodayRegisterForUser($user->id);
        
        if ($existingRegister) {
            // Si elle est fermée, on ne peut pas en ouvrir une nouvelle
            if ($existingRegister->is_closed) {
                throw new \Exception('Une caisse a déjà été fermée pour aujourd\'hui. Vous ne pouvez pas en ouvrir une nouvelle.');
            }
            // Si elle est ouverte, on la retourne simplement
            return $existingRegister;
        }

        return self::create([
            'user_id' => $user->id,
            'date' => today(),
            'opening_balance' => $openingBalance,
            'status' => 'open',
            'opened_at' => now(),
            'opening_notes' => $notes,
        ]);
    }

    public function close(float $closingBalance, ?string $notes = null): void
    {
        $expectedBalance = $this->calculated_balance;
        $difference = $closingBalance - $expectedBalance;

        $this->update([
            'closing_balance' => $closingBalance,
            'expected_balance' => $expectedBalance,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => now(),
            'closing_notes' => $notes,
        ]);
    }

    public function addTransaction(string $type, string $category, float $amount, string $paymentMethod, $transactionable = null, ?string $description = null): CashTransaction
    {
        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'transactionable_type' => $transactionable ? get_class($transactionable) : null,
            'transactionable_id' => $transactionable?->id,
            'description' => $description,
        ]);
    }
}
