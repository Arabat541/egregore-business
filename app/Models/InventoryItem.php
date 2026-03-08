<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les articles d'inventaire
 */
class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'product_id',
        'theoretical_quantity',
        'physical_quantity',
        'difference',
        'difference_value',
        'notes',
        'counted',
    ];

    protected $casts = [
        'theoretical_quantity' => 'integer',
        'physical_quantity' => 'integer',
        'difference' => 'integer',
        'difference_value' => 'decimal:2',
        'counted' => 'boolean',
    ];

    // Relations
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeCounted($query)
    {
        return $query->where('counted', true);
    }

    public function scopeNotCounted($query)
    {
        return $query->where('counted', false);
    }

    public function scopeWithDifference($query)
    {
        return $query->where('difference', '!=', 0);
    }

    public function scopePositiveDifference($query)
    {
        return $query->where('difference', '>', 0);
    }

    public function scopeNegativeDifference($query)
    {
        return $query->where('difference', '<', 0);
    }

    // Méthodes
    public function updateCount($physicalQuantity, $notes = null)
    {
        $this->physical_quantity = $physicalQuantity;
        $this->difference = $physicalQuantity - $this->theoretical_quantity;
        $this->difference_value = $this->difference * $this->product->normal_price;
        $this->counted = true;
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        $this->save();
    }

    // Accesseurs
    public function getDifferenceStatusAttribute()
    {
        if (!$this->counted) {
            return 'pending';
        }
        
        if ($this->difference === 0) {
            return 'ok';
        } elseif ($this->difference > 0) {
            return 'surplus';
        } else {
            return 'shortage';
        }
    }

    public function getDifferenceColorAttribute()
    {
        return match($this->difference_status) {
            'pending' => 'secondary',
            'ok' => 'success',
            'surplus' => 'info',
            'shortage' => 'danger',
            default => 'secondary',
        };
    }

    public function getDifferenceLabelAttribute()
    {
        return match($this->difference_status) {
            'pending' => 'Non compté',
            'ok' => 'Conforme',
            'surplus' => 'Surplus (+' . $this->difference . ')',
            'shortage' => 'Manque (' . $this->difference . ')',
            default => '-',
        };
    }
}
