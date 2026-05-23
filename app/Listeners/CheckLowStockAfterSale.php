<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SaleCompleted;

/**
 * Stock alerts after sale are handled by ProductObserver::updated()
 * to avoid duplicate notifications with the scheduled CheckLowStockCommand.
 */
class CheckLowStockAfterSale
{
    public function handle(SaleCompleted $event): void
    {
        // Intentionally empty — ProductObserver handles stock alerts
    }
}
