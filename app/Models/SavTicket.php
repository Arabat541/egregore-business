<?php

namespace App\Models;

use App\Traits\BelongsToShop;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavTicket extends Model
{
    use HasFactory, SoftDeletes, BelongsToShop;

    protected $fillable = [
        'shop_id',
        'ticket_number',
        'customer_id',
        'sale_id',
        'repair_id',
        'product_id',
        'created_by',
        'assigned_to',
        'type',
        'product_name',
        'product_serial',
        'purchase_date',
        'issue_description',
        'customer_request',
        'status',
        'priority',
        'resolution_notes',
        'resolution_type',
        'refund_amount',
        'exchange_difference',
        'resolved_at',
        'closed_at',
        // Champs de retour en stock
        'stock_returned',
        'stock_returned_at',
        'stock_returned_by',
        'quantity_returned',
        'return_notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'exchange_difference' => 'decimal:2',
        'stock_returned' => 'boolean',
        'stock_returned_at' => 'datetime',
    ];

    // Générer un numéro de ticket unique
    public static function generateTicketNumber(): string
    {
        $prefix = 'SAV';
        $date = now()->format('ymd');
        $lastTicket = self::whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();
        
        $sequence = $lastTicket ? (intval(substr($lastTicket->ticket_number, -4)) + 1) : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Relations
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stockReturnedByUser()
    {
        return $this->belongsTo(User::class, 'stock_returned_by');
    }

    public function comments()
    {
        return $this->hasMany(SavTicketComment::class)->orderBy('created_at', 'desc');
    }

    // Accesseurs
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'return' => 'Retour',
            'exchange' => 'Échange',
            'warranty' => 'Garantie Produit',
            'repair_warranty' => 'Garantie Réparation',
            'complaint' => 'Réclamation',
            'refund' => 'Remboursement',
            'other' => 'Autre',
            default => $this->type,
        };
    }

    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            'open' => 'Ouvert',
            'in_progress' => 'En cours',
            'waiting_customer' => 'Attente client',
            'waiting_parts' => 'Attente pièces',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            'rejected' => 'Rejeté',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'primary',
            'in_progress' => 'info',
            'waiting_customer' => 'warning',
            'waiting_parts' => 'secondary',
            'resolved' => 'success',
            'closed' => 'dark',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getPriorityNameAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
            default => $this->priority,
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'secondary',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'waiting_parts']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed', 'rejected']);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }
}
