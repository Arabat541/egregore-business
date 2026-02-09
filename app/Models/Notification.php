<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Modèle pour les notifications internes
 * Gère les alertes et notifications pour les utilisateurs
 */
class Notification extends Model
{
    // Utiliser UUID comme clé primaire
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'color',
        'link',
        'notifiable_type',
        'notifiable_id',
        'read_at',
        'is_important',
        'play_sound',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_important' => 'boolean',
        'play_sound' => 'boolean',
    ];

    // Types de notifications disponibles
    const TYPE_REPAIR_READY = 'repair_ready';
    const TYPE_REPAIR_NEW = 'repair_new';
    const TYPE_REPAIR_ASSIGNED = 'repair_assigned';
    const TYPE_STOCK_LOW = 'stock_low';
    const TYPE_STOCK_CRITICAL = 'stock_critical';
    const TYPE_SAV_NEW = 'sav_new';
    const TYPE_SAV_URGENT = 'sav_urgent';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_SALE_COMPLETED = 'sale_completed';
    const TYPE_RESELLER_PAYMENT = 'reseller_payment';
    const TYPE_SYSTEM = 'system';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->id)) {
                $notification->id = (string) Str::uuid();
            }
        });
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accesseurs
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getIconClassAttribute(): string
    {
        return "bi {$this->icon} text-{$this->color}";
    }

    // Méthodes
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    // Configuration des types de notifications
    public static function getTypeConfig(string $type): array
    {
        $configs = [
            self::TYPE_REPAIR_NEW => [
                'icon' => 'bi-tools',
                'color' => 'info',
                'play_sound' => true,
            ],
            self::TYPE_REPAIR_READY => [
                'icon' => 'bi-check-circle',
                'color' => 'success',
                'play_sound' => true,
            ],
            self::TYPE_REPAIR_ASSIGNED => [
                'icon' => 'bi-person-check',
                'color' => 'primary',
                'play_sound' => false,
            ],
            self::TYPE_STOCK_LOW => [
                'icon' => 'bi-exclamation-triangle',
                'color' => 'warning',
                'play_sound' => false,
            ],
            self::TYPE_STOCK_CRITICAL => [
                'icon' => 'bi-x-octagon',
                'color' => 'danger',
                'play_sound' => true,
                'is_important' => true,
            ],
            self::TYPE_SAV_NEW => [
                'icon' => 'bi-ticket',
                'color' => 'info',
                'play_sound' => false,
            ],
            self::TYPE_SAV_URGENT => [
                'icon' => 'bi-exclamation-circle',
                'color' => 'danger',
                'play_sound' => true,
                'is_important' => true,
            ],
            self::TYPE_PAYMENT_RECEIVED => [
                'icon' => 'bi-cash-coin',
                'color' => 'success',
                'play_sound' => false,
            ],
            self::TYPE_SALE_COMPLETED => [
                'icon' => 'bi-cart-check',
                'color' => 'success',
                'play_sound' => false,
            ],
            self::TYPE_RESELLER_PAYMENT => [
                'icon' => 'bi-credit-card',
                'color' => 'primary',
                'play_sound' => false,
            ],
            self::TYPE_SYSTEM => [
                'icon' => 'bi-gear',
                'color' => 'secondary',
                'play_sound' => false,
            ],
        ];

        return $configs[$type] ?? [
            'icon' => 'bi-bell',
            'color' => 'primary',
            'play_sound' => false,
        ];
    }
}
