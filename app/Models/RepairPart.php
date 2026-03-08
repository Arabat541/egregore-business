<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les pièces utilisées dans les réparations
 */
class RepairPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_id',
        'product_id',
        'sale_id',
        'description',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($part) {
            if (empty($part->total_cost)) {
                $part->total_cost = $part->unit_cost * $part->quantity;
            }
        });
    }

    // Relations
    public function repair()
    {
        return $this->belongsTo(Repair::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Vente associée à cette pièce (pour comptabilité CA)
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
