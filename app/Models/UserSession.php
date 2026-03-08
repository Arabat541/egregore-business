<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les sessions utilisateur actives
 */
class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location',
        'last_activity_at',
        'is_current',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        // Sessions actives dans les dernières 30 minutes
        return $query->where('last_activity_at', '>=', now()->subMinutes(30));
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Méthodes statiques
    public static function createOrUpdate(int $userId, string $sessionId): self
    {
        $userAgent = request()->userAgent();
        $deviceInfo = self::parseUserAgent($userAgent);

        return self::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'user_agent' => $userAgent,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'last_activity_at' => now(),
            ]
        );
    }

    public static function updateActivity(string $sessionId): void
    {
        self::where('session_id', $sessionId)
            ->update(['last_activity_at' => now()]);
    }

    public static function terminateSession(string $sessionId): void
    {
        self::where('session_id', $sessionId)->delete();
    }

    public static function terminateAllUserSessions(int $userId, ?string $exceptSessionId = null): void
    {
        $query = self::where('user_id', $userId);
        
        if ($exceptSessionId) {
            $query->where('session_id', '!=', $exceptSessionId);
        }
        
        $query->delete();
    }

    public static function parseUserAgent(?string $userAgent): array
    {
        $result = [
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'platform' => 'Unknown',
        ];

        if (!$userAgent) {
            return $result;
        }

        // Détection du type d'appareil
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
            $result['device_type'] = preg_match('/iPad|Tablet/i', $userAgent) ? 'tablet' : 'mobile';
        }

        // Détection du navigateur
        if (preg_match('/Firefox/i', $userAgent)) {
            $result['browser'] = 'Firefox';
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $result['browser'] = 'Edge';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $result['browser'] = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $result['browser'] = 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $result['browser'] = 'Opera';
        }

        // Détection de la plateforme
        if (preg_match('/Windows/i', $userAgent)) {
            $result['platform'] = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $result['platform'] = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $result['platform'] = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $result['platform'] = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $result['platform'] = 'iOS';
        }

        return $result;
    }

    // Accesseurs
    public function getIsActiveAttribute(): bool
    {
        return $this->last_activity_at >= now()->subMinutes(30);
    }

    public function getDeviceIconAttribute(): string
    {
        return match($this->device_type) {
            'mobile' => 'bi-phone',
            'tablet' => 'bi-tablet',
            default => 'bi-laptop',
        };
    }
}
