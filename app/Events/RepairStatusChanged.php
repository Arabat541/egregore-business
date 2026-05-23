<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Repair;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Déclenché à chaque changement de statut d'une réparation.
 * Permet aux listeners de réagir (notifications, logs, SMS…) sans
 * coupler la logique de changement d'état aux effets de bord.
 */
class RepairStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Repair $repair,
        public readonly string $previousStatus,
        public readonly string $newStatus
    ) {}
}
