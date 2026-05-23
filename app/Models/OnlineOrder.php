<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OnlineOrder extends Model
{
    use HasFactory, BelongsToShop;

    protected $fillable = [
        'order_number',
        'confirmation_token',
        'shop_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'customer_city',
        'subtotal',
        'shipping_cost',
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
        'delivery_method',
        'notes',
        'admin_notes',
        'processed_by',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY = 'ready';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_PROCESSING => 'En préparation',
            self::STATUS_READY => 'Prête',
            self::STATUS_SHIPPED => 'Expédiée',
            self::STATUS_DELIVERED => 'Livrée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }

    public static function getStatusBadgeClass(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_PROCESSING => 'primary',
            self::STATUS_READY => 'success',
            self::STATUS_SHIPPED => 'info',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_CANCELLED => 'danger',
        ];
    }

    public static function getPaymentLabels(): array
    {
        return [
            'cash_on_delivery' => 'Paiement à la livraison',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement bancaire',
        ];
    }

    public static function getDeliveryLabels(): array
    {
        return [
            'pickup' => 'Retrait en boutique',
            'delivery' => 'Livraison',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            if (empty($order->confirmation_token)) {
                $order->confirmation_token = Str::random(64);
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'WEB';
        $date = now()->format('Ymd');
        $last = self::where('order_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($last) {
            $parts = explode('-', $last->order_number);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::getStatusBadgeClass()[$this->status] ?? 'secondary';
    }

    public function getPaymentLabelAttribute(): string
    {
        return self::getPaymentLabels()[$this->payment_method] ?? $this->payment_method;
    }

    public function getDeliveryLabelAttribute(): string
    {
        return self::getDeliveryLabels()[$this->delivery_method] ?? $this->delivery_method;
    }

    // Relations
    public function items()
    {
        return $this->hasMany(OnlineOrderItem::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }
}
