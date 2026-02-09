<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour l'historique des actions utilisateurs
 */
class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    // Accessors
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'create' => 'Création',
            'update' => 'Modification',
            'delete' => 'Suppression',
            'login' => 'Connexion',
            'logout' => 'Déconnexion',
            'sale' => 'Vente',
            'repair' => 'Réparation',
            'payment' => 'Paiement',
            'stock_entry' => 'Entrée stock',
            'stock_exit' => 'Sortie stock',
            default => ucfirst($this->action),
        };
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query->where('model_type', $modelType);
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        return $query;
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->latest()->limit($limit);
    }

    // Méthodes statiques
    public static function log(string $action, ?Model $model = null, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }

    public static function logLogin(): self
    {
        return self::log('login', auth()->user(), null, null, 'Connexion utilisateur');
    }

    public static function logLogout(): self
    {
        return self::log('logout', auth()->user(), null, null, 'Déconnexion utilisateur');
    }
}
