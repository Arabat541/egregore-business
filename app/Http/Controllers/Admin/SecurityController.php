<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Models\UserSession;
use App\Services\SecurityService;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Dashboard de sécurité
     */
    public function index()
    {
        $stats = $this->securityService->getSecurityStats();

        // Alertes récentes
        $alerts = SecurityAlert::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Tentatives de connexion récentes échouées
        $failedAttempts = LoginAttempt::failed()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Comptes verrouillés
        $lockedAccounts = User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->get();

        // Sessions actives par utilisateur
        $activeSessions = UserSession::active()
            ->with('user')
            ->orderBy('last_activity_at', 'desc')
            ->get();

        // IPs suspectes (plus de 5 échecs en 24h)
        $suspiciousIps = LoginAttempt::failed()
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('ip_address, COUNT(*) as attempt_count')
            ->groupBy('ip_address')
            ->having('attempt_count', '>=', 5)
            ->orderBy('attempt_count', 'desc')
            ->get();

        return view('admin.security.index', compact(
            'stats',
            'alerts',
            'failedAttempts',
            'lockedAccounts',
            'activeSessions',
            'suspiciousIps'
        ));
    }

    /**
     * Liste des alertes de sécurité
     */
    public function alerts(Request $request)
    {
        $query = SecurityAlert::with('user')->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            if ($request->status === 'resolved') {
                $query->whereNotNull('resolved_at');
            } else {
                $query->whereNull('resolved_at');
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->paginate(25);

        $types = [
            SecurityAlert::TYPE_BRUTE_FORCE => 'Force brute',
            SecurityAlert::TYPE_SUSPICIOUS_LOGIN => 'Connexion suspecte',
            SecurityAlert::TYPE_ACCOUNT_LOCKED => 'Compte verrouillé',
            SecurityAlert::TYPE_IP_BLOCKED => 'IP bloquée',
            SecurityAlert::TYPE_HIGH_REFUNDS => 'Remboursements élevés',
            SecurityAlert::TYPE_SUSPICIOUS_SAV => 'SAV suspect',
            SecurityAlert::TYPE_PASSWORD_CHANGED => 'Mot de passe changé',
            SecurityAlert::TYPE_MULTIPLE_SESSIONS => 'Sessions multiples',
        ];

        return view('admin.security.alerts', compact('alerts', 'types'));
    }

    /**
     * Résoudre une alerte
     */
    public function resolveAlert(SecurityAlert $alert, Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $alert->resolve(auth()->id(), $request->notes);

        return back()->with('success', 'Alerte marquée comme résolue.');
    }

    /**
     * Liste des sessions actives
     */
    public function sessions()
    {
        $sessions = UserSession::with('user')
            ->orderBy('last_activity_at', 'desc')
            ->paginate(25);

        return view('admin.security.sessions', compact('sessions'));
    }

    /**
     * Terminer une session
     */
    public function terminateSession(UserSession $session)
    {
        UserSession::terminateSession($session->session_id);

        return back()->with('success', 'Session terminée.');
    }

    /**
     * Terminer toutes les sessions d'un utilisateur
     */
    public function terminateUserSessions(User $user)
    {
        UserSession::terminateAllUserSessions($user->id);

        return back()->with('success', "Toutes les sessions de {$user->name} ont été terminées.");
    }

    /**
     * Historique des connexions
     */
    public function loginHistory(Request $request)
    {
        $query = LoginAttempt::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'success') {
                $query->successful();
            } else {
                $query->failed();
            }
        }

        if ($request->filled('email')) {
            $query->forEmail($request->email);
        }

        if ($request->filled('ip')) {
            $query->forIp($request->ip);
        }

        $attempts = $query->paginate(50);

        return view('admin.security.login-history', compact('attempts'));
    }

    /**
     * Débloquer un compte
     */
    public function unlockAccount(User $user)
    {
        $this->securityService->unlockAccount($user);

        return back()->with('success', "Le compte de {$user->name} a été débloqué.");
    }

    /**
     * Forcer la déconnexion d'un utilisateur
     */
    public function forceLogout(User $user)
    {
        $this->securityService->forceLogout($user);

        return back()->with('success', "{$user->name} a été déconnecté de force.");
    }

    /**
     * Forcer le changement de mot de passe
     */
    public function forcePasswordChange(User $user)
    {
        $user->update(['force_password_change' => true]);

        return back()->with('success', "{$user->name} devra changer son mot de passe à la prochaine connexion.");
    }

    /**
     * Export CSV des alertes
     */
    public function exportAlerts(Request $request)
    {
        $query = SecurityAlert::with('user', 'resolvedBy');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $alerts = $query->orderBy('created_at', 'desc')->get();

        $filename = 'alertes_securite_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($alerts) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($file, [
                'ID',
                'Date',
                'Type',
                'Sévérité',
                'Message',
                'Utilisateur',
                'Adresse IP',
                'Statut',
                'Résolu par',
                'Date résolution',
                'Notes',
            ], ';');

            foreach ($alerts as $alert) {
                fputcsv($file, [
                    $alert->id,
                    $alert->created_at->format('d/m/Y H:i:s'),
                    $alert->type,
                    $alert->severity,
                    $alert->message,
                    $alert->user?->name ?? '-',
                    $alert->ip_address ?? '-',
                    $alert->resolved_at ? 'Résolu' : 'Non résolu',
                    $alert->resolvedBy?->name ?? '-',
                    $alert->resolved_at?->format('d/m/Y H:i:s') ?? '-',
                    $alert->resolution_notes ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export CSV historique connexions
     */
    public function exportLoginHistory(Request $request)
    {
        $query = LoginAttempt::with('user');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $attempts = $query->orderBy('created_at', 'desc')->get();

        $filename = 'historique_connexions_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($attempts) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Date',
                'Email',
                'Utilisateur',
                'Adresse IP',
                'Navigateur',
                'Statut',
                'Raison échec',
            ], ';');

            foreach ($attempts as $attempt) {
                fputcsv($file, [
                    $attempt->created_at->format('d/m/Y H:i:s'),
                    $attempt->email,
                    $attempt->user?->name ?? 'Non trouvé',
                    $attempt->ip_address,
                    $attempt->user_agent ?? '-',
                    $attempt->successful ? 'Succès' : 'Échec',
                    $attempt->failure_reason ?? '-',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
