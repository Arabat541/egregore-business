<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur pour la gestion des notifications
 */
class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des notifications de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $query = Notification::forUser(auth()->id())
            ->orderByDesc('created_at');

        // Filtrer par statut
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->read();
            }
        }

        // Filtrer par type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        $notifications = $query->paginate(20);

        $stats = [
            'total' => Notification::forUser(auth()->id())->count(),
            'unread' => Notification::forUser(auth()->id())->unread()->count(),
            'important' => Notification::forUser(auth()->id())->important()->unread()->count(),
        ];

        return view('notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Récupérer les dernières notifications (pour dropdown AJAX)
     */
    public function latest(): JsonResponse
    {
        $notifications = $this->notificationService->getLatest(auth()->user(), 10);
        $unreadCount = $this->notificationService->unreadCount(auth()->user());

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'icon' => $notification->icon,
                    'color' => $notification->color,
                    'link' => $notification->link,
                    'time_ago' => $notification->time_ago,
                    'is_read' => $notification->is_read,
                    'is_important' => $notification->is_important,
                    'play_sound' => $notification->play_sound && !$notification->is_read,
                    'created_at' => $notification->created_at->toISOString(),
                ];
            }),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => $this->notificationService->unreadCount(auth()->user()),
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());

        return response()->json([
            'success' => true,
            'marked_count' => $count,
            'unread_count' => 0,
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'unread_count' => $this->notificationService->unreadCount(auth()->user()),
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function clearRead(): JsonResponse
    {
        $count = Notification::forUser(auth()->id())
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'deleted_count' => $count,
        ]);
    }

    /**
     * Vérifier les nouvelles notifications (polling)
     */
    public function check(): JsonResponse
    {
        $lastCheck = request('last_check');
        
        $query = Notification::forUser(auth()->id())->unread();
        
        if ($lastCheck) {
            $query->where('created_at', '>', $lastCheck);
        }

        $newNotifications = $query->orderByDesc('created_at')->get();
        $unreadCount = $this->notificationService->unreadCount(auth()->user());

        return response()->json([
            'new_notifications' => $newNotifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'icon' => $notification->icon,
                    'color' => $notification->color,
                    'link' => $notification->link,
                    'is_important' => $notification->is_important,
                    'play_sound' => $notification->play_sound,
                ];
            }),
            'unread_count' => $unreadCount,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
