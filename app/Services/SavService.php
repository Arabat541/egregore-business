<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SavReplacedPart;
use App\Models\SavTicket;
use App\Models\SavTicketComment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SavService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function createTicket(array $validated, int $userId): SavTicket
    {
        $validated['ticket_number'] = SavTicket::generateTicketNumber(
            User::find($userId)?->shop_id
        );
        $validated['created_by'] = $userId;
        $validated['status']     = 'open';

        // Auto-fill customer_id depuis la vente ou réparation liée
        if (empty($validated['customer_id'])) {
            if (!empty($validated['sale_id'])) {
                $sale = Sale::find($validated['sale_id']);
                if ($sale) {
                    $validated['customer_id'] = $sale->customer_id;
                }
            } elseif (!empty($validated['repair_id'])) {
                $repair = Repair::find($validated['repair_id']);
                if ($repair) {
                    $validated['customer_id'] = $repair->customer_id;
                }
            }
        }

        // Auto-fill champs garantie réparation
        if ($validated['type'] === 'repair_warranty' && !empty($validated['repair_id'])) {
            $repair = Repair::find($validated['repair_id']);
            if ($repair) {
                $validated['customer_id']    ??= $repair->customer_id;
                $validated['product_name']   ??= "{$repair->device_brand} {$repair->device_model}";
                $validated['product_serial'] ??= $repair->device_imei;
                $validated['purchase_date']  ??= $repair->delivered_at?->format('Y-m-d');
            }
        }

        $ticket = SavTicket::create($validated);

        ActivityLog::log('create', $ticket, null, $ticket->toArray(), "Création ticket SAV: {$ticket->ticket_number}");

        if ($validated['priority'] === 'urgent') {
            $this->notifications->savUrgent($ticket);
        } else {
            $this->notifications->savCreated($ticket);
        }

        return $ticket;
    }

    /**
     * @return array{total_returned: int, total_refund: float, details: string[]}
     */
    public function processStockReturn(SavTicket $ticket, array $validated, int $userId): array
    {
        if ($ticket->stock_returned) {
            throw new \LogicException('Le retour en stock a déjà été effectué pour ce ticket.');
        }

        return DB::transaction(function () use ($ticket, $validated, $userId): array {
            $totalReturned    = 0;
            $totalRefund      = 0.0;
            $returnDetails    = [];

            foreach ($validated['products'] as $productData) {
                $product   = Product::findOrFail($productData['product_id']);
                $quantity  = (int) $productData['quantity'];
                $condition = $productData['condition'];
                $refund    = $this->calculateRefundAmount($ticket, $product, $quantity);

                if (in_array($condition, ['new', 'good'], true)) {
                    $before = $product->quantity_in_stock;

                    StockMovement::create([
                        'shop_id'         => $ticket->shop_id,
                        'product_id'      => $product->id,
                        'user_id'         => $userId,
                        'type'            => StockMovement::TYPE_RETURN,
                        'quantity'        => $quantity,
                        'quantity_before' => $before,
                        'quantity_after'  => $before + $quantity,
                        'moveable_type'   => SavTicket::class,
                        'moveable_id'     => $ticket->id,
                        'reason'          => "Retour SAV #{$ticket->ticket_number} - État: {$condition}",
                    ]);

                    $product->increment('quantity_in_stock', $quantity);
                    $totalReturned += $quantity;
                    $totalRefund   += $refund;
                    $returnDetails[] = "{$product->name} x{$quantity} ({$condition}) - "
                        . number_format($refund, 0, ',', ' ') . " F";
                } else {
                    if ($validated['refund_damaged'] ?? false) {
                        $totalRefund   += $refund;
                        $returnDetails[] = "{$product->name} x{$quantity} ({$condition}) - Remboursé: "
                            . number_format($refund, 0, ',', ' ') . " F - NON remis en stock";
                    } else {
                        $returnDetails[] = "{$product->name} x{$quantity} ({$condition}) - NON remis en stock, NON remboursé";
                    }
                }
            }

            if ($totalRefund > 0) {
                $cashRegister = CashRegister::getOpenRegisterForUser($userId);
                if ($cashRegister) {
                    $cashRegister->addTransaction(
                        CashTransaction::TYPE_EXPENSE,
                        CashTransaction::CATEGORY_SAV_REFUND,
                        $totalRefund,
                        $validated['refund_method'] ?? 'cash',
                        $ticket,
                        "Remboursement SAV #{$ticket->ticket_number} - {$totalReturned} article(s)"
                    );
                }

                if ($ticket->sale) {
                    $ticket->sale->update([
                        'refund_amount' => ($ticket->sale->refund_amount ?? 0) + $totalRefund,
                        'notes' => ($ticket->sale->notes ? $ticket->sale->notes . "\n" : '')
                            . "[" . now()->format('d/m/Y H:i') . "] Remboursement SAV: "
                            . number_format($totalRefund, 0, ',', ' ') . " F",
                    ]);
                }
            }

            $ticket->update([
                'stock_returned'    => true,
                'stock_returned_at' => now(),
                'stock_returned_by' => $userId,
                'quantity_returned' => $totalReturned,
                'refund_amount'     => $totalRefund,
                'return_notes'      => $validated['return_notes'] ?? implode(', ', $returnDetails),
            ]);

            $commentText = "🔄 Retour en stock effectué:\n" . implode("\n", $returnDetails);
            if ($totalRefund > 0) {
                $commentText .= "\n\n💰 Montant remboursé: " . number_format($totalRefund, 0, ',', ' ') . " F";
            }

            SavTicketComment::create([
                'sav_ticket_id' => $ticket->id,
                'user_id'       => $userId,
                'comment'       => $commentText,
                'is_internal'   => true,
            ]);

            ActivityLog::log('stock_return', $ticket, null, [
                'products'       => $returnDetails,
                'total_returned' => $totalReturned,
                'refund_amount'  => $totalRefund,
            ], "Retour en stock SAV #{$ticket->ticket_number}");

            return [
                'total_returned' => $totalReturned,
                'total_refund'   => $totalRefund,
                'details'        => $returnDetails,
            ];
        });
    }

    public function cancelStockReturn(SavTicket $ticket, int $userId): void
    {
        if (!$ticket->stock_returned) {
            throw new \LogicException('Aucun retour en stock à annuler.');
        }

        DB::transaction(function () use ($ticket, $userId): void {
            $movements = StockMovement::where('moveable_type', SavTicket::class)
                ->where('moveable_id', $ticket->id)
                ->where('type', StockMovement::TYPE_RETURN)
                ->get();

            foreach ($movements as $movement) {
                $product = Product::find($movement->product_id);
                $before  = $product ? $product->quantity_in_stock : 0;

                StockMovement::create([
                    'shop_id'         => $ticket->shop_id,
                    'product_id'      => $movement->product_id,
                    'user_id'         => $userId,
                    'type'            => StockMovement::TYPE_EXIT,
                    'quantity'        => -$movement->quantity,
                    'quantity_before' => $before,
                    'quantity_after'  => $before - $movement->quantity,
                    'moveable_type'   => SavTicket::class,
                    'moveable_id'     => $ticket->id,
                    'reason'          => "Annulation retour SAV #{$ticket->ticket_number}",
                ]);

                $product?->decrement('quantity_in_stock', $movement->quantity);
            }

            $refundAmount = (float) ($ticket->refund_amount ?? 0);

            if ($refundAmount > 0) {
                $refundTransaction = CashTransaction::where('transactionable_type', SavTicket::class)
                    ->where('transactionable_id', $ticket->id)
                    ->where('category', 'sav_refund')
                    ->first();
                $refundTransaction?->delete();

                if ($ticket->sale) {
                    $ticket->sale->update([
                        'refund_amount' => max(0, ($ticket->sale->refund_amount ?? 0) - $refundAmount),
                        'notes' => ($ticket->sale->notes ? $ticket->sale->notes . "\n" : '')
                            . "[" . now()->format('d/m/Y H:i') . "] Annulation remboursement SAV: "
                            . number_format($refundAmount, 0, ',', ' ') . " F",
                    ]);
                }
            }

            $ticket->update([
                'stock_returned'    => false,
                'stock_returned_at' => null,
                'stock_returned_by' => null,
                'quantity_returned' => 0,
                'refund_amount'     => 0,
                'return_notes'      => null,
            ]);

            $commentText = "❌ Retour en stock annulé";
            if ($refundAmount > 0) {
                $commentText .= "\n💰 Remboursement annulé: " . number_format($refundAmount, 0, ',', ' ') . " F";
            }

            SavTicketComment::create([
                'sav_ticket_id' => $ticket->id,
                'user_id'       => $userId,
                'comment'       => $commentText,
                'is_internal'   => true,
            ]);

            ActivityLog::log(
                'stock_return_cancelled',
                $ticket,
                null,
                ['refund_cancelled' => $refundAmount],
                "Annulation retour stock SAV #{$ticket->ticket_number}"
                    . ($refundAmount > 0 ? " - Remboursement annulé: " . number_format($refundAmount, 0, ',', ' ') . " F" : "")
            );
        });
    }

    public function processReplacePart(SavTicket $ticket, array $validated, int $userId): SavReplacedPart
    {
        return DB::transaction(function () use ($ticket, $validated, $userId): SavReplacedPart {
            $originalPart       = $ticket->repair->parts()->findOrFail($validated['original_repair_part_id']);
            $replacementProduct = Product::findOrFail($validated['replacement_product_id']);
            $quantity           = (int) $validated['quantity'];

            if ($replacementProduct->quantity_in_stock < $quantity) {
                throw new \DomainException('Stock insuffisant pour la pièce de remplacement.');
            }

            $defectivePartCost = (float) $originalPart->unit_cost * $quantity;

            $replacedPart = SavReplacedPart::create([
                'sav_ticket_id'           => $ticket->id,
                'repair_id'               => $ticket->repair_id,
                'original_repair_part_id' => $originalPart->id,
                'defective_product_id'    => $originalPart->product_id,
                'replacement_product_id'  => $replacementProduct->id,
                'technician_id'           => $ticket->repair->technician_id,
                'quantity'                => $quantity,
                'defective_part_cost'     => $defectivePartCost,
                'replacement_part_cost'   => (float) $replacementProduct->normal_price * $quantity,
                'reason'                  => $validated['reason'] ?? null,
                'ca_deducted'             => true,
                'deducted_at'             => now(),
                'deducted_by'             => $userId,
            ]);

            $before = $replacementProduct->quantity_in_stock;
            $replacementProduct->decrement('quantity_in_stock', $quantity);

            StockMovement::create([
                'shop_id'         => $ticket->shop_id,
                'product_id'      => $replacementProduct->id,
                'type'            => StockMovement::TYPE_EXIT,
                'quantity'        => $quantity,
                'quantity_before' => $before,
                'quantity_after'  => $before - $quantity,
                'reason'          => 'sav_replacement',
                'reference_type'  => SavTicket::class,
                'reference_id'    => $ticket->id,
                'user_id'         => $userId,
                'notes'           => "Remplacement SAV #{$ticket->ticket_number}",
            ]);

            SavTicketComment::create([
                'sav_ticket_id' => $ticket->id,
                'user_id'       => $userId,
                'comment'       => "🔧 Pièce remplacée:\n"
                    . "- Pièce défectueuse: {$originalPart->product->name} (x{$quantity})\n"
                    . "- Nouvelle pièce: {$replacementProduct->name} (x{$quantity})\n"
                    . "- Coût déduit du CA technicien: " . number_format($defectivePartCost, 0, ',', ' ') . " F\n"
                    . (!empty($validated['reason']) ? "- Raison: {$validated['reason']}" : ''),
                'is_internal' => true,
            ]);

            ActivityLog::log('sav_part_replaced', $ticket, null, [
                'defective_part'   => $originalPart->product->name,
                'replacement_part' => $replacementProduct->name,
                'quantity'         => $quantity,
                'cost_deducted'    => $defectivePartCost,
                'technician_id'    => $ticket->repair->technician_id,
            ], "SAV #{$ticket->ticket_number} - Pièce remplacée, "
                . number_format($defectivePartCost, 0, ',', ' ') . " F déduits du CA technicien");

            return $replacedPart;
        });
    }

    private function calculateRefundAmount(SavTicket $ticket, Product $product, int $quantity): float
    {
        if ($ticket->sale) {
            $saleItem = SaleItem::where('sale_id', $ticket->sale_id)
                ->where('product_id', $product->id)
                ->first();

            return $saleItem
                ? (float) $saleItem->unit_price * $quantity
                : (float) $product->normal_price * $quantity;
        }

        if ($ticket->repair) {
            return (float) ($product->purchase_price ?? 0) * $quantity;
        }

        return 0.0;
    }
}
