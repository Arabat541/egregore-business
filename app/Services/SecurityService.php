<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\LoginAttempt;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Hash;

/**
 * Service centralisé pour la gestion de la sécurité
 */
class SecurityService
{
    // Configuration
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION_MINUTES = 30;
    const MAX_IP_ATTEMPTS = 20;
    const PASSWORD_MIN_LENGTH = 8;
    const SESSION_TIMEOUT_MINUTES = 120;
    const MAX_CONCURRENT_SESSIONS = 3;

    /**
     * Vérifier si un utilisateur peut tenter de se connecter
     */
    public function canAttemptLogin(string $email): array
    {
        $ip = request()->ip();

        // Vérifier si l'IP est bloquée
        if (LoginAttempt::isIpBlocked($ip, self::MAX_IP_ATTEMPTS)) {
            SecurityAlert::alertIpBlocked($ip, self::MAX_IP_ATTEMPTS);
            return [
                'allowed' => false,
                'reason' => 'ip_blocked',
                'message' => 'Trop de tentatives depuis cette adresse IP. Réessayez plus tard.',
                'wait_minutes' => 30,
            ];
        }

        // Vérifier le nombre de tentatives pour cet email
        $failedAttempts = LoginAttempt::countRecentFailedAttempts($email);
        if ($failedAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            SecurityAlert::alertBruteForce($email, $failedAttempts);
            return [
                'allowed' => false,
                'reason' => 'too_many_attempts',
                'message' => 'Trop de tentatives échouées. Réessayez dans ' . self::LOCKOUT_DURATION_MINUTES . ' minutes.',
                'wait_minutes' => self::LOCKOUT_DURATION_MINUTES,
            ];
        }

        // Vérifier si le compte existe et est verrouillé
        $user = User::where('email', $email)->first();
        if ($user && $user->locked_until && $user->locked_until > now()) {
            $waitMinutes = now()->diffInMinutes($user->locked_until);
            return [
                'allowed' => false,
                'reason' => 'account_locked',
                'message' => 'Compte verrouillé. Réessayez dans ' . $waitMinutes . ' minutes.',
                'wait_minutes' => $waitMinutes,
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Enregistrer une tentative de connexion réussie
     */
    public function recordSuccessfulLogin(User $user): void
    {
        // Enregistrer la tentative
        LoginAttempt::recordAttempt($user->email, true, $user->id);

        // Réinitialiser les compteurs
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        // Créer/mettre à jour la session
        UserSession::createOrUpdate($user->id, session()->getId());

        // Vérifier si c'est une connexion suspecte
        $this->checkSuspiciousLogin($user);

        // Logger l'activité
        ActivityLog::logLogin();

        // Vérifier le nombre de sessions actives
        $this->enforceMaxSessions($user);
    }

    /**
     * Enregistrer une tentative de connexion échouée
     */
    public function recordFailedLogin(string $email, string $reason = 'invalid_password'): void
    {
        $user = User::where('email', $email)->first();

        LoginAttempt::recordAttempt($email, false, $user?->id, $reason);

        if ($user) {
            $user->increment('failed_login_attempts');

            // Verrouiller le compte si trop de tentatives
            if ($user->failed_login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $user->update([
                    'locked_until' => now()->addMinutes(self::LOCKOUT_DURATION_MINUTES),
                ]);
                SecurityAlert::alertAccountLocked($user);
            }
        }
    }

    /**
     * Déconnecter un utilisateur
     */
    public function logout(User $user): void
    {
        // Supprimer la session
        UserSession::terminateSession(session()->getId());

        // Logger
        ActivityLog::logLogout();
    }

    /**
     * Vérifier si c'est une connexion suspecte
     */
    protected function checkSuspiciousLogin(User $user): void
    {
        $currentIp = request()->ip();
        $lastIp = $user->last_login_ip;

        // Nouvelle IP
        if ($lastIp && $lastIp !== $currentIp) {
            // Vérifier si cette IP a été utilisée récemment
            $recentLoginFromIp = LoginAttempt::where('user_id', $user->id)
                ->where('ip_address', $currentIp)
                ->where('successful', true)
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();

            if (!$recentLoginFromIp) {
                SecurityAlert::alertSuspiciousLogin(
                    $user,
                    "Connexion depuis une nouvelle adresse IP: {$currentIp} (ancienne: {$lastIp})"
                );
            }
        }

        // Connexion en dehors des heures habituelles (avant 6h ou après 22h)
        $hour = now()->hour;
        if ($hour < 6 || $hour > 22) {
            SecurityAlert::alertSuspiciousLogin(
                $user,
                "Connexion à une heure inhabituelle: " . now()->format('H:i')
            );
        }
    }

    /**
     * Limiter le nombre de sessions actives
     */
    protected function enforceMaxSessions(User $user): void
    {
        $activeSessions = UserSession::forUser($user->id)->active()->count();

        if ($activeSessions > self::MAX_CONCURRENT_SESSIONS) {
            // Supprimer les sessions les plus anciennes
            UserSession::forUser($user->id)
                ->orderBy('last_activity_at', 'asc')
                ->limit($activeSessions - self::MAX_CONCURRENT_SESSIONS)
                ->delete();

            SecurityAlert::create_alert(
                SecurityAlert::TYPE_MULTIPLE_SESSIONS,
                SecurityAlert::SEVERITY_LOW,
                "Sessions multiples détectées pour {$user->name}. Anciennes sessions terminées.",
                $user->id
            );
        }
    }

    /**
     * Vérifier la force du mot de passe
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = 'Le mot de passe doit contenir au moins ' . self::PASSWORD_MIN_LENGTH . ' caractères.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password),
        ];
    }

    /**
     * Calculer la force du mot de passe (0-100)
     */
    protected function calculatePasswordStrength(string $password): int
    {
        $strength = 0;

        // Longueur
        $strength += min(strlen($password) * 4, 40);

        // Variété de caractères
        if (preg_match('/[A-Z]/', $password)) $strength += 15;
        if (preg_match('/[a-z]/', $password)) $strength += 15;
        if (preg_match('/[0-9]/', $password)) $strength += 15;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $strength += 15;

        return min($strength, 100);
    }

    /**
     * Changer le mot de passe d'un utilisateur
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        SecurityAlert::create_alert(
            SecurityAlert::TYPE_PASSWORD_CHANGED,
            SecurityAlert::SEVERITY_LOW,
            "Mot de passe changé pour {$user->name}",
            $user->id
        );

        // Terminer toutes les autres sessions
        UserSession::terminateAllUserSessions($user->id, session()->getId());

        ActivityLog::log('password_change', $user, null, null, 'Changement de mot de passe');
    }

    /**
     * Débloquer un compte
     */
    public function unlockAccount(User $user): void
    {
        $user->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);

        ActivityLog::log('account_unlock', $user, null, null, 'Compte débloqué par admin');
    }

    /**
     * Forcer la déconnexion d'un utilisateur
     */
    public function forceLogout(User $user): void
    {
        UserSession::terminateAllUserSessions($user->id);
        
        ActivityLog::log('force_logout', $user, null, null, 'Déconnexion forcée par admin');
    }

    /**
     * Obtenir les statistiques de sécurité
     */
    public function getSecurityStats(): array
    {
        return [
            'failed_logins_today' => LoginAttempt::failed()->whereDate('created_at', today())->count(),
            'failed_logins_week' => LoginAttempt::failed()->where('created_at', '>=', now()->subDays(7))->count(),
            'locked_accounts' => User::whereNotNull('locked_until')->where('locked_until', '>', now())->count(),
            'active_sessions' => UserSession::active()->count(),
            'unresolved_alerts' => SecurityAlert::unresolved()->count(),
            'critical_alerts' => SecurityAlert::unresolved()->critical()->count(),
            'unique_ips_today' => LoginAttempt::whereDate('created_at', today())->distinct('ip_address')->count(),
        ];
    }
}
