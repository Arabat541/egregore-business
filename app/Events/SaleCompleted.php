<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Sale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Déclenché après qu'une vente a été entièrement persistée (dans la transaction).
 * Les listeners peuvent s'y abonner sans toucher SaleController.
 */
class SaleCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Sale $sale) {}
}
