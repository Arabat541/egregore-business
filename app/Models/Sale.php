<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les ventes
 * Particuliers: paiement comptant | Revendeurs: comptant ou crédit
 */
class Sale extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'invoice_number',
        'user_id',
        'customer_id',
        'reseller_id',
        'repair_id',
        'is_repair_parts',
        'client_type',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'amount_due',
        'payment_status',
        'payment_method',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'completed_at' => 'datetime',
        'is_repair_parts' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->invoice_number)) {
                $sale->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'moveable');
    }

    public function cashTransactions()
    {
        return $this->morphMany(CashTransaction::class, 'transactionable');
    }

    public function resellerPayments()
    {
        return $this->hasMany(ResellerPayment::class);
    }

    // Accessors
    public function getClientAttribute()
    {
        return $this->client_type === 'customer' ? $this->customer : $this->reseller;
    }

    public function getClientNameAttribute(): string
    {
        if ($this->client_type === 'customer') {
            return $this->customer?->full_name ?? 'Client anonyme';
        }
        return $this->reseller?->company_name ?? 'Revendeur inconnu';
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->payment_status === 'credit';
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->amount_paid);
    }

    // Scopes
    public function scopeForCustomers($query)
    {
        return $query->where('client_type', 'customer');
    }

    public function scopeForResellers($query)
    {
        return $query->where('client_type', 'reseller');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeOnCredit($query)
    {
        return $query->where('payment_status', 'credit');
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
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'VTE';
        $date = now()->format('Ymd');
        $lastSale = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastSale ? intval(substr($lastSale->invoice_number, -4)) + 1 : 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    // Stats
    public function getItemCountAttribute(): int
    {
        return $this->items()->sum('quantity');
    }
}
