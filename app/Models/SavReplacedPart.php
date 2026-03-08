<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les pièces remplacées lors d'un SAV réparation
 * Permet de suivre les pièces défectueuses et de déduire leur coût du CA du technicien
 */
class SavReplacedPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'sav_ticket_id',
        'repair_id',
        'original_repair_part_id',
        'defective_product_id',
        'replacement_product_id',
        'technician_id',
        'quantity',
        'defective_part_cost',
        'replacement_part_cost',
        'reason',
        'ca_deducted',
        'deducted_at',
        'deducted_by',
    ];

    protected $casts = [
        'defective_part_cost' => 'decimal:2',
        'replacement_part_cost' => 'decimal:2',
        'ca_deducted' => 'boolean',
        'deducted_at' => 'datetime',
    ];

    // Relations
    public function savTicket()
    {
        return $this->belongsTo(SavTicket::class);
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class);
    }

    public function originalRepairPart()
    {
        return $this->belongsTo(RepairPart::class, 'original_repair_part_id');
    }

    public function defectiveProduct()
    {
        return $this->belongsTo(Product::class, 'defective_product_id');
    }

    public function replacementProduct()
    {
        return $this->belongsTo(Product::class, 'replacement_product_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function deductedBy()
    {
        return $this->belongsTo(User::class, 'deducted_by');
    }

    /**
     * Marquer la déduction comme appliquée
     */
    public function markAsDeducted(?int $userId = null): void
    {
        $this->update([
            'ca_deducted' => true,
            'deducted_at' => now(),
            'deducted_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Scope: Déductions du jour pour un technicien
     */
    public function scopeForTechnicianToday($query, int $technicianId)
    {
        return $query->where('technician_id', $technicianId)
            ->where('ca_deducted', true)
            ->whereDate('deducted_at', today());
    }

    /**
     * Scope: Déductions du mois pour un technicien
     */
    public function scopeForTechnicianMonth($query, int $technicianId)
    {
        return $query->where('technician_id', $technicianId)
            ->where('ca_deducted', true)
            ->whereMonth('deducted_at', now()->month)
            ->whereYear('deducted_at', now()->year);
    }
}
