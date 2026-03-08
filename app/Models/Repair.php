<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les réparations
 * Workflow: Création → Paiement → Diagnostic → Réparation → Livraison
 */
class Repair extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    // Constantes pour les statuts
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAID_PENDING_DIAGNOSIS = 'paid_pending_diagnosis';
    const STATUS_IN_DIAGNOSIS = 'in_diagnosis';
    const STATUS_WAITING_PARTS = 'waiting_parts';
    const STATUS_IN_REPAIR = 'in_repair';
    const STATUS_REPAIRED = 'repaired';
    const STATUS_UNREPAIRABLE = 'unrepairable';
    const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shop_id',
        'repair_number',
        'customer_id',
        'created_by',
        'technician_id',
        'device_type',
        'device_brand',
        'device_model',
        'device_imei',
        'device_password',
        'device_condition',
        'accessories_received',
        'reported_issue',
        'diagnosis',
        'repair_notes',
        'status',
        'estimated_cost',
        'final_cost',
        'labor_cost',
        'parts_cost',
        'deposit_amount',
        'amount_paid',
        'payment_method',
        'estimated_completion_date',
        'paid_at',
        'diagnosis_at',
        'repaired_at',
        'delivered_at',
    ];

    protected $casts = [
        'accessories_received' => 'array',
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'estimated_completion_date' => 'date',
        'paid_at' => 'datetime',
        'diagnosis_at' => 'datetime',
        'repaired_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $hidden = [
        'device_password',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($repair) {
            if (empty($repair->repair_number)) {
                $repair->repair_number = self::generateRepairNumber();
            }
        });
    }

    // Relations
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function parts()
    {
        return $this->hasMany(RepairPart::class);
    }

    /**
     * Vente(s) des pièces de rechange liées à cette réparation
     */
    public function partsSales()
    {
        return $this->hasMany(Sale::class)->where('is_repair_parts', true);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'moveable');
    }

    public function cashTransactions()
    {
        return $this->morphMany(CashTransaction::class, 'transactionable');
    }

    public function savTickets()
    {
        return $this->hasMany(SavTicket::class);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING_PAYMENT => 'En attente de paiement',
            self::STATUS_PAID_PENDING_DIAGNOSIS => 'Payé - En attente diagnostic',
            self::STATUS_IN_DIAGNOSIS => 'En cours de diagnostic',
            self::STATUS_WAITING_PARTS => 'En attente de pièces',
            self::STATUS_IN_REPAIR => 'En cours de réparation',
            self::STATUS_REPAIRED => 'Réparé',
            self::STATUS_READY_FOR_PICKUP => 'Prêt pour retrait',
            self::STATUS_DELIVERED => 'Livré',
            self::STATUS_CANCELLED => 'Annulé',
            default => 'Inconnu',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING_PAYMENT => 'warning',
            self::STATUS_PAID_PENDING_DIAGNOSIS => 'info',
            self::STATUS_IN_DIAGNOSIS => 'primary',
            self::STATUS_WAITING_PARTS => 'secondary',
            self::STATUS_IN_REPAIR => 'primary',
            self::STATUS_REPAIRED => 'success',
            self::STATUS_READY_FOR_PICKUP => 'success',
            self::STATUS_DELIVERED => 'dark',
            self::STATUS_CANCELLED => 'danger',
            default => 'light',
        };
    }

    public function getDeviceFullNameAttribute(): string
    {
        $parts = array_filter([$this->device_brand, $this->device_model, $this->device_type]);
        return implode(' ', $parts) ?: $this->device_type;
    }

    public function getPartsCostAttribute(): float
    {
        return $this->parts()->sum('total_cost');
    }

    public function getRemainingAmountAttribute(): float
    {
        $total = $this->final_cost ?? $this->estimated_cost ?? 0;
        return max(0, $total - $this->amount_paid);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->amount_paid > 0 && $this->status !== self::STATUS_PENDING_PAYMENT;
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function scopeForTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('technician_id');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('repair_number', 'like', "%{$search}%")
              ->orWhere('device_imei', 'like', "%{$search}%")
              ->orWhereHas('customer', function ($cq) use ($search) {
                  $cq->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
              });
        });
    }

    // Méthodes statiques
    public static function generateRepairNumber(): string
    {
        $prefix = 'REP';
        $date = now()->format('Ymd');
        $lastRepair = self::whereDate('created_at', today())->latest()->first();
        $sequence = $lastRepair ? intval(substr($lastRepair->repair_number, -4)) + 1 : 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT => 'En attente de paiement',
            self::STATUS_PAID_PENDING_DIAGNOSIS => 'Payé - En attente diagnostic',
            self::STATUS_IN_DIAGNOSIS => 'En cours de diagnostic',
            self::STATUS_WAITING_PARTS => 'En attente de pièces',
            self::STATUS_IN_REPAIR => 'En cours de réparation',
            self::STATUS_REPAIRED => 'Réparé',
            self::STATUS_UNREPAIRABLE => 'Non réparable',
            self::STATUS_READY_FOR_PICKUP => 'Prêt pour retrait',
            self::STATUS_DELIVERED => 'Livré',
            self::STATUS_CANCELLED => 'Annulé',
        ];
    }

    // Méthodes métier
    public function canBeEdited(): bool
    {
        return !in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function canBeDiagnosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID_PENDING_DIAGNOSIS,
            self::STATUS_IN_DIAGNOSIS,
        ]);
    }

    public function canBeRepaired(): bool
    {
        return in_array($this->status, [
            self::STATUS_IN_DIAGNOSIS,
            self::STATUS_WAITING_PARTS,
            self::STATUS_IN_REPAIR,
        ]);
    }

    /**
     * Vérifie si on peut ajouter des pièces (diagnostic + réparation)
     */
    public function canAddParts(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID_PENDING_DIAGNOSIS,
            self::STATUS_IN_DIAGNOSIS,
            self::STATUS_WAITING_PARTS,
            self::STATUS_IN_REPAIR,
        ]);
    }

    public function canBeDelivered(): bool
    {
        return in_array($this->status, [
            self::STATUS_REPAIRED,
            self::STATUS_READY_FOR_PICKUP,
        ]);
    }

    public function markAsPaid(string $paymentMethod, float $amount): void
    {
        $this->update([
            'status' => self::STATUS_PAID_PENDING_DIAGNOSIS,
            'payment_method' => $paymentMethod,
            'amount_paid' => $amount,
            'paid_at' => now(),
        ]);
    }

    public function assignTechnician(int $technicianId): void
    {
        $this->update([
            'technician_id' => $technicianId,
            'status' => self::STATUS_IN_DIAGNOSIS,
        ]);
    }

    public function updateStatus(string $status): void
    {
        $data = ['status' => $status];

        if ($status === self::STATUS_IN_DIAGNOSIS) {
            $data['diagnosis_at'] = now();
        } elseif ($status === self::STATUS_REPAIRED) {
            $data['repaired_at'] = now();
        } elseif ($status === self::STATUS_DELIVERED) {
            $data['delivered_at'] = now();
        }

        $this->update($data);
    }
}
