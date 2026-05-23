<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Repair;
use App\Models\Product;
use App\Models\SavTicket;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Service pour gérer les notifications internes
 * Centralise la création et l'envoi des notifications
 */
class NotificationService
{
    /**
     * Créer une notification pour un utilisateur
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?Model $notifiable = null
    ): Notification {
        $config = Notification::getTypeConfig($type);

        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $config['icon'],
            'color' => $config['color'],
            'link' => $link,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable?->id,
            'is_important' => $config['is_important'] ?? false,
            'play_sound' => $config['play_sound'] ?? false,
        ]);
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function createForUsers(
        Collection|array $users,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?Model $notifiable = null
    ): Collection {
        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push(
                $this->create($user, $type, $title, $message, $link, $notifiable)
            );
        }

        return $notifications;
    }

    /**
     * Envoyer une notification à tous les utilisateurs d'un rôle
     */
    public function notifyRole(
        string $role,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?Model $notifiable = null
    ): Collection {
        $users = User::role($role)->get();
        return $this->createForUsers($users, $type, $title, $message, $link, $notifiable);
    }

    // ==================== NOTIFICATIONS RÉPARATIONS ====================

    /**
     * Nouvelle réparation créée → notifier les techniciens
     */
    public function repairCreated(Repair $repair): void
    {
        $this->notifyRole(
            'technicien',
            Notification::TYPE_REPAIR_NEW,
            'Nouvelle réparation',
            "Ticket #{$repair->repair_number} - {$repair->device_brand} {$repair->device_model}",
            route('technician.repairs.show', $repair),
            $repair
        );
    }

    /**
     * Réparation assignée → notifier le technicien
     */
    public function repairAssigned(Repair $repair): void
    {
        if ($repair->technician) {
            $this->create(
                $repair->technician,
                Notification::TYPE_REPAIR_ASSIGNED,
                'Réparation assignée',
                "Le ticket #{$repair->repair_number} vous a été assigné",
                route('technician.repairs.show', $repair),
                $repair
            );
        }
    }

    /**
     * Réparation terminée → notifier les caissières
     */
    public function repairReady(Repair $repair): void
    {
        $this->notifyRole(
            'caissiere',
            Notification::TYPE_REPAIR_READY,
            'Réparation terminée',
            "#{$repair->repair_number} - {$repair->device_brand} {$repair->device_model} est prêt pour livraison",
            route('cashier.repairs.show', $repair),
            $repair
        );
    }

    /**
     * Réparation non réparable → notifier les caissières pour retour appareil
     */
    public function repairUnrepairable(Repair $repair): void
    {
        $this->notifyRole(
            'caissiere',
            Notification::TYPE_REPAIR_READY,
            'Appareil non réparable',
            "#{$repair->repair_number} - {$repair->device_brand} {$repair->device_model} à retourner au client",
            route('cashier.repairs.show', $repair),
            $repair
        );
    }

    // ==================== NOTIFICATIONS STOCK ====================

    /**
     * Stock bas → notifier uniquement les admins de la boutique du produit
     */
    public function stockLow(Product $product): void
    {
        $admins = User::role('admin')
            ->where('shop_id', $product->shop_id)
            ->get();

        $this->createForUsers(
            $admins,
            Notification::TYPE_STOCK_LOW,
            'Stock bas',
            "{$product->name} - Stock: {$product->quantity_in_stock} (Seuil: {$product->stock_alert_threshold})",
            route('admin.products.edit', $product),
            $product
        );
    }

    /**
     * Stock critique (0 ou négatif) → notifier uniquement les admins de la boutique du produit
     */
    public function stockCritical(Product $product): void
    {
        $admins = User::role('admin')
            ->where('shop_id', $product->shop_id)
            ->get();

        $this->createForUsers(
            $admins,
            Notification::TYPE_STOCK_CRITICAL,
            '⚠️ Stock critique !',
            "{$product->name} - Stock épuisé ou critique: {$product->quantity_in_stock}",
            route('admin.products.edit', $product),
            $product
        );
    }

    // ==================== NOTIFICATIONS S.A.V. ====================

    /**
     * Nouveau ticket SAV → notifier admins et caissières de la boutique concernée
     */
    public function savCreated(SavTicket $ticket): void
    {
        $users = User::role(['admin', 'caissiere'])
            ->where(function ($q) use ($ticket) {
                $q->where('shop_id', $ticket->shop_id)
                  ->orWhereNull('shop_id'); // super-admins sans boutique assignée
            })
            ->get();

        $this->createForUsers(
            $users,
            Notification::TYPE_SAV_NEW,
            'Nouveau ticket SAV',
            "#{$ticket->ticket_number} - {$ticket->type_name}",
            route('sav.show', $ticket),
            $ticket
        );
    }

    /**
     * Ticket SAV urgent → notifier avec alerte (boutique scopée)
     */
    public function savUrgent(SavTicket $ticket): void
    {
        $users = User::role(['admin', 'caissiere'])
            ->where(function ($q) use ($ticket) {
                $q->where('shop_id', $ticket->shop_id)
                  ->orWhereNull('shop_id');
            })
            ->get();
        
        $this->createForUsers(
            $users,
            Notification::TYPE_SAV_URGENT,
            '🔴 SAV URGENT',
            "#{$ticket->ticket_number} - {$ticket->type_name}: {$ticket->issue_description}",
            route('sav.show', $ticket),
            $ticket
        );
    }

    // ==================== NOTIFICATIONS VENTES ====================

    /**
     * Vente importante → notifier admin
     */
    public function saleCompleted(Sale $sale, float $threshold = 100000): void
    {
        if ($sale->total_amount >= $threshold) {
            $this->notifyRole(
                'admin',
                Notification::TYPE_SALE_COMPLETED,
                'Vente importante',
                "Facture #{$sale->invoice_number} - " . number_format((float) $sale->total_amount, 0, ',', ' ') . " F",
                route('cashier.sales.show', $sale),
                $sale
            );
        }
    }

    /**
     * Paiement revendeur reçu → notifier admin
     */
    public function resellerPaymentReceived(Model $payment): void
    {
        $this->notifyRole(
            'admin',
            Notification::TYPE_RESELLER_PAYMENT,
            'Paiement revendeur',
            "Paiement de " . number_format($payment->amount, 0, ',', ' ') . " F reçu",
            route('admin.resellers.show', $payment->reseller_id),
            $payment
        );
    }

    // ==================== UTILITAIRES ====================

    /**
     * Notification système générique
     */
    public function system(User $user, string $title, string $message, ?string $link = null): Notification
    {
        return $this->create($user, Notification::TYPE_SYSTEM, $title, $message, $link);
    }

    /**
     * Notification à tous les utilisateurs
     */
    public function broadcast(string $title, string $message, ?string $link = null): Collection
    {
        $users = User::where('is_active', true)->get();
        return $this->createForUsers($users, Notification::TYPE_SYSTEM, $title, $message, $link);
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Supprimer les anciennes notifications (nettoyage)
     */
    public function cleanup(int $daysOld = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))
            ->whereNotNull('read_at')
            ->delete();
    }

    /**
     * Compter les notifications non lues d'un utilisateur
     */
    public function unreadCount(User $user): int
    {
        return Notification::forUser($user->id)->unread()->count();
    }

    /**
     * Récupérer les dernières notifications d'un utilisateur
     */
    public function getLatest(User $user, int $limit = 10): Collection
    {
        return Notification::forUser($user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
