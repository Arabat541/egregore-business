<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modèle pour les catégories de produits
 * Les catégories peuvent être globales (shop_id = null) ou spécifiques à une boutique
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'is_active',
        'sort_order',
        'shop_id',
        'is_global',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_global' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Relations
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Catégories disponibles pour une boutique (globales + spécifiques)
     */
    public function scopeForShop($query, $shopId = null)
    {
        return $query->where(function ($q) use ($shopId) {
            $q->where('is_global', true)
              ->orWhereNull('shop_id');
            
            if ($shopId) {
                $q->orWhere('shop_id', $shopId);
            }
        });
    }

    /**
     * Catégories globales uniquement
     */
    public function scopeGlobal($query)
    {
        return $query->where(function ($q) {
            $q->where('is_global', true)->orWhereNull('shop_id');
        });
    }

    /**
     * Catégories spécifiques à une boutique
     */
    public function scopeShopSpecific($query, $shopId)
    {
        return $query->where('shop_id', $shopId)->where('is_global', false);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helpers
    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }
}
