<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RepairStatusChanged;
use App\Models\Repair;
use App\Services\NotificationService;

/**
 * Réagit aux changements de statut d'une réparation :
 * - Réparation prête → notifie les caissières
 * - Nouvelle assignation → notifie le technicien (géré via repairAssigned)
 */
class NotifyRepairStatusChange
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(RepairStatusChanged $event): void
    {
        $repair = $event->repair;
        $new    = $event->newStatus;

        // Réparation prête pour livraison → notifier les caissières
        if (in_array($new, [Repair::STATUS_REPAIRED, Repair::STATUS_READY_FOR_PICKUP])) {
            $this->notifications->repairReady($repair);
        }

        // Appareil non réparable → notifier les caissières pour retour
        if ($new === Repair::STATUS_UNREPAIRABLE) {
            $this->notifications->repairUnrepairable($repair);
        }

        // Réparation assignée à un technicien → notifier le technicien
        if ($new === Repair::STATUS_IN_REPAIR && $repair->technician_id) {
            $this->notifications->repairAssigned($repair);
        }
    }
}
