<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les alertes de sécurité
 */
class SecurityAlert extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'ip_address',
        'description',
        'details',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'details' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // Types d'alertes
    const TYPE_BRUTE_FORCE = 'brute_force';
    const TYPE_SUSPICIOUS_LOGIN = 'suspicious_login';
    const TYPE_MULTIPLE_SESSIONS = 'multiple_sessions';
    const TYPE_UNUSUAL_ACTIVITY = 'unusual_activity';
    const TYPE_ACCOUNT_LOCKED = 'account_locked';
    const TYPE_PASSWORD_CHANGED = 'password_changed';
    const TYPE_FAILED_LOGIN = 'failed_login';
    const TYPE_IP_BLOCKED = 'ip_blocked';
    const TYPE_HIGH_REFUNDS = 'high_refunds';
    const TYPE_SUSPICIOUS_SAV = 'suspicious_sav';

    // Niveaux de sévérité
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Méthodes statiques
    public static function create_alert(
        string $type,
        string $severity,
        string $description,
        ?int $userId = null,
        ?array $details = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'description' => $description,
            'details' => $details,
        ]);
    }

    public static function alertBruteForce(string $email, int $attempts): self
    {
        return self::create_alert(
            self::TYPE_BRUTE_FORCE,
            $attempts >= 10 ? self::SEVERITY_CRITICAL : self::SEVERITY_HIGH,
            "Tentative de brute force détectée pour l'email: {$email}. {$attempts} tentatives échouées.",
            null,
            ['email' => $email, 'attempts' => $attempts]
        );
    }

    public static function alertSuspiciousLogin(User $user, string $reason): self
    {
        return self::create_alert(
            self::TYPE_SUSPICIOUS_LOGIN,
            self::SEVERITY_MEDIUM,
            "Connexion suspecte pour {$user->name}: {$reason}",
            $user->id,
            ['reason' => $reason, 'ip' => request()->ip()]
        );
    }

    public static function alertAccountLocked(User $user): self
    {
        return self::create_alert(
            self::TYPE_ACCOUNT_LOCKED,
            self::SEVERITY_HIGH,
            "Compte verrouillé: {$user->name} ({$user->email}) après trop de tentatives échouées.",
            $user->id
        );
    }

    public static function alertIpBlocked(string $ip, int $attempts): self
    {
        return self::create_alert(
            self::TYPE_IP_BLOCKED,
            self::SEVERITY_HIGH,
            "IP bloquée: {$ip} après {$attempts} tentatives échouées.",
            null,
            ['ip' => $ip, 'attempts' => $attempts]
        );
    }

    // Accesseurs
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_BRUTE_FORCE => 'Attaque Brute Force',
            self::TYPE_SUSPICIOUS_LOGIN => 'Connexion Suspecte',
            self::TYPE_MULTIPLE_SESSIONS => 'Sessions Multiples',
            self::TYPE_UNUSUAL_ACTIVITY => 'Activité Inhabituelle',
            self::TYPE_ACCOUNT_LOCKED => 'Compte Verrouillé',
            self::TYPE_PASSWORD_CHANGED => 'Mot de passe Changé',
            self::TYPE_FAILED_LOGIN => 'Échec de Connexion',
            self::TYPE_IP_BLOCKED => 'IP Bloquée',
            self::TYPE_HIGH_REFUNDS => 'Remboursements Élevés',
            self::TYPE_SUSPICIOUS_SAV => 'S.A.V Suspect',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_LOW => 'Faible',
            self::SEVERITY_MEDIUM => 'Moyenne',
            self::SEVERITY_HIGH => 'Haute',
            self::SEVERITY_CRITICAL => 'Critique',
            default => $this->severity,
        };
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_LOW => 'info',
            self::SEVERITY_MEDIUM => 'warning',
            self::SEVERITY_HIGH => 'danger',
            self::SEVERITY_CRITICAL => 'dark',
            default => 'secondary',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_BRUTE_FORCE => 'bi-shield-exclamation',
            self::TYPE_SUSPICIOUS_LOGIN => 'bi-person-exclamation',
            self::TYPE_MULTIPLE_SESSIONS => 'bi-window-stack',
            self::TYPE_UNUSUAL_ACTIVITY => 'bi-activity',
            self::TYPE_ACCOUNT_LOCKED => 'bi-lock-fill',
            self::TYPE_PASSWORD_CHANGED => 'bi-key',
            self::TYPE_FAILED_LOGIN => 'bi-x-circle',
            self::TYPE_IP_BLOCKED => 'bi-ban',
            self::TYPE_HIGH_REFUNDS => 'bi-cash-coin',
            self::TYPE_SUSPICIOUS_SAV => 'bi-exclamation-triangle',
            default => 'bi-bell',
        };
    }

    // Résolution
    public function resolve(int $resolvedBy, ?string $notes = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
