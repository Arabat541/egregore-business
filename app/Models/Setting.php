<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modèle pour les paramètres système
 * Supporte les paramètres globaux et par boutique
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'is_global',
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    // Relations
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Obtenir une valeur de paramètre
     * Priorité: paramètre boutique > paramètre global
     */
    public static function get(string $key, $default = null, ?int $shopId = null)
    {
        // Si pas de shopId fourni, essayer de récupérer celui de l'utilisateur connecté
        if ($shopId === null && auth()->check()) {
            $shopId = auth()->user()->shop_id;
        }

        $cacheKey = $shopId ? "setting.{$key}.shop.{$shopId}" : "setting.{$key}.global";

        $setting = Cache::remember($cacheKey, 3600, function () use ($key, $shopId) {
            // D'abord chercher un paramètre spécifique à la boutique
            if ($shopId) {
                $shopSetting = self::where('key', $key)->where('shop_id', $shopId)->first();
                if ($shopSetting) {
                    return $shopSetting;
                }
            }
            
            // Sinon, chercher le paramètre global
            return self::where('key', $key)->where(function($q) {
                $q->whereNull('shop_id')->orWhere('is_global', true);
            })->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Définir une valeur de paramètre
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null, ?int $shopId = null, bool $isGlobal = true): void
    {
        $conditions = ['key' => $key];
        
        if ($shopId) {
            $conditions['shop_id'] = $shopId;
        } else {
            $conditions['shop_id'] = null;
        }

        self::updateOrCreate(
            $conditions,
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'is_global' => $isGlobal,
            ]
        );

        // Invalider le cache
        if ($shopId) {
            Cache::forget("setting.{$key}.shop.{$shopId}");
        } else {
            Cache::forget("setting.{$key}.global");
        }
    }

    /**
     * Obtenir les paramètres d'un groupe pour une boutique
     */
    public static function getGroup(string $group, ?int $shopId = null): array
    {
        $query = self::where('group', $group);
        
        if ($shopId) {
            // Récupérer les paramètres de la boutique OU les globaux
            $query->where(function($q) use ($shopId) {
                $q->where('shop_id', $shopId)
                  ->orWhereNull('shop_id')
                  ->orWhere('is_global', true);
            });
        } else {
            $query->where(function($q) {
                $q->whereNull('shop_id')->orWhere('is_global', true);
            });
        }
        
        return $query->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => self::castValue($setting->value, $setting->type)];
            })
            ->toArray();
    }

    /**
     * Obtenir tous les paramètres pour une boutique (avec fallback sur global)
     */
    public static function getAllForShop(?int $shopId = null)
    {
        // Paramètres globaux
        $global = self::where(function($q) {
            $q->whereNull('shop_id')->orWhere('is_global', true);
        })->get()->keyBy('key');
        
        // Si boutique spécifiée, récupérer et fusionner
        if ($shopId) {
            $shopSettings = self::where('shop_id', $shopId)->get()->keyBy('key');
            // Les paramètres boutique écrasent les globaux
            return $global->merge($shopSettings);
        }
        
        return $global;
    }

    protected static function castValue($value, string $type)
    {
        return match($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    // Paramètres par défaut (seront créés comme globaux par défaut)
    public static function getDefaults(): array
    {
        return [
            // Paramètres globaux (partagés entre toutes les boutiques)
            'currency' => ['value' => 'XOF', 'type' => 'string', 'group' => 'general', 'is_global' => true],
            'currency_symbol' => ['value' => 'FCFA', 'type' => 'string', 'group' => 'general', 'is_global' => true],
            'tax_rate' => ['value' => '0', 'type' => 'float', 'group' => 'sales', 'is_global' => true],
            'default_credit_limit' => ['value' => '100000', 'type' => 'float', 'group' => 'resellers', 'is_global' => true],
            'low_stock_threshold' => ['value' => '5', 'type' => 'integer', 'group' => 'stock', 'is_global' => true],
            
            // Paramètres par boutique (peuvent être personnalisés)
            'shop_name' => ['value' => 'EGREGORE BUSINESS', 'type' => 'string', 'group' => 'shop', 'is_global' => false],
            'shop_address' => ['value' => '', 'type' => 'string', 'group' => 'shop', 'is_global' => false],
            'shop_phone' => ['value' => '', 'type' => 'string', 'group' => 'shop', 'is_global' => false],
            'shop_email' => ['value' => '', 'type' => 'string', 'group' => 'shop', 'is_global' => false],
            'receipt_header' => ['value' => '', 'type' => 'string', 'group' => 'printing', 'is_global' => false],
            'receipt_footer' => ['value' => 'Merci de votre visite !', 'type' => 'string', 'group' => 'printing', 'is_global' => false],
        ];
    }
}
