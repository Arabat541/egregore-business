<?php

namespace App\Traits;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait pour ajouter le scope multi-boutique aux modèles
 * Les admins voient toutes les boutiques, les autres voient seulement leur boutique
 */
trait BelongsToShop
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToShop()
    {
        // Appliquer automatiquement le scope boutique sur les requêtes
        static::addGlobalScope('shop', function (Builder $builder) {
            if (auth()->check() && !auth()->user()->hasRole('admin')) {
                $shopId = auth()->user()->shop_id;
                if ($shopId) {
                    $builder->where(static::getTableName() . '.shop_id', $shopId);
                }
            }
        });

        // Assigner automatiquement la boutique lors de la création
        static::creating(function ($model) {
            if (auth()->check() && !$model->shop_id) {
                $model->shop_id = auth()->user()->shop_id;
            }
        });
    }

    /**
     * Obtenir le nom de la table pour le scope
     */
    protected static function getTableName(): string
    {
        return (new static)->getTable();
    }

    /**
     * Relation avec la boutique
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Scope pour filtrer par boutique spécifique
     */
    public function scopeForShop(Builder $query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope pour ignorer le filtre boutique (admin)
     */
    public function scopeWithoutShopScope(Builder $query)
    {
        return $query->withoutGlobalScope('shop');
    }

    /**
     * Scope pour toutes les boutiques (admin)
     */
    public function scopeAllShops(Builder $query)
    {
        return $query->withoutGlobalScope('shop');
    }
}
