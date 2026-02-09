<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les tentatives de connexion
 */
class LoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'user_id',
        'failure_reason',
    ];

    protected $casts = [
        'successful' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }

    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function scopeForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // Méthodes statiques
    public static function recordAttempt(string $email, bool $successful, ?int $userId = null, ?string $failureReason = null): self
    {
        return self::create([
            'email' => $email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'successful' => $successful,
            'user_id' => $userId,
            'failure_reason' => $failureReason,
        ]);
    }

    public static function countRecentFailedAttempts(string $email, int $minutes = 30): int
    {
        return self::forEmail($email)
            ->failed()
            ->recent($minutes)
            ->count();
    }

    public static function countRecentFailedAttemptsFromIp(string $ip, int $minutes = 30): int
    {
        return self::forIp($ip)
            ->failed()
            ->recent($minutes)
            ->count();
    }

    public static function isIpBlocked(string $ip, int $maxAttempts = 20, int $minutes = 30): bool
    {
        return self::countRecentFailedAttemptsFromIp($ip, $minutes) >= $maxAttempts;
    }
}
