<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Repair;
use App\Models\RepairPart;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RepairService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function create(array $validated, User $user, ?CashRegister $cashRegister): Repair
    {
        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);

        return DB::transaction(function () use ($validated, $user, $cashRegister, $paymentMethod): Repair {
            $partsCost = 0.0;
            if (!empty($validated['parts'])) {
                foreach ($validated['parts'] as $partData) {
                    $partsCost += (float) $partData['unit_price'] * (int) $partData['quantity'];
                }
            }

            $laborCost = (float) ($validated['labor_cost'] ?? 0);
            $finalCost = (!empty($validated['final_cost']) && $validated['final_cost'] > 0)
                ? (float) $validated['final_cost']
                : ($laborCost + $partsCost);

            $repair = Repair::create([
                'shop_id'                   => $user->shop_id,
                'customer_id'               => $validated['customer_id'],
                'created_by'                => $user->id,
                'technician_id'             => $validated['technician_id'],
                'device_type'               => $validated['device_type'],
                'device_brand'              => $validated['device_brand'],
                'device_model'              => $validated['device_model'],
                'device_imei'               => $validated['device_imei'] ?? null,
                'device_password'           => $validated['device_password'] ?? null,
                'device_condition'          => $validated['device_condition'] ?? null,
                'accessories_received'      => !empty($validated['accessories_received'])
                    ? (is_array($validated['accessories_received']) ? $validated['accessories_received'] : [$validated['accessories_received']])
                    : null,
                'reported_issue'            => $validated['reported_issue'],
                'diagnosis'                 => $validated['diagnosis'],
                'repair_notes'              => $validated['repair_notes'] ?? null,
                'status'                    => 'in_repair',
                'estimated_cost'            => $finalCost,
                'final_cost'                => $finalCost,
                'labor_cost'                => $laborCost,
                'parts_cost'                => $partsCost,
                'amount_paid'               => $validated['amount_paid'],
                'deposit_amount'            => (float) $validated['amount_paid'],
                'payment_method'            => $paymentMethod->type ?? 'cash',
                'estimated_completion_date' => $validated['estimated_completion_date'],
                'paid_at'                   => $validated['amount_paid'] > 0 ? now() : null,
                'diagnosis_at'              => now(),
                'repaired_at'               => null,
                'delivered_at'              => null,
            ]);

            if (!empty($validated['parts'])) {
                foreach ($validated['parts'] as $partData) {
                    $product = Product::findOrFail($partData['product_id']);

                    if ($product->quantity_in_stock < $partData['quantity']) {
                        throw new \DomainException("Stock insuffisant pour {$product->name}");
                    }

                    RepairPart::create([
                        'repair_id'  => $repair->id,
                        'product_id' => $product->id,
                        'quantity'   => (int) $partData['quantity'],
                        'unit_cost'  => (float) $partData['unit_price'],
                        'total_cost' => (float) $partData['unit_price'] * (int) $partData['quantity'],
                    ]);

                    $before = $product->quantity_in_stock;
                    $product->decrement('quantity_in_stock', $partData['quantity']);

                    StockMovement::create([
                        'shop_id'         => $repair->shop_id,
                        'product_id'      => $product->id,
                        'user_id'         => $user->id,
                        'type'            => 'repair_usage',
                        'quantity'        => -(int) $partData['quantity'],
                        'quantity_before' => $before,
                        'quantity_after'  => $before - (int) $partData['quantity'],
                        'reason'          => "Pièce utilisée pour réparation #{$repair->repair_number}",
                        'moveable_type'   => Repair::class,
                        'moveable_id'     => $repair->id,
                    ]);
                }
            }

            if ($validated['amount_paid'] > 0 && $cashRegister) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_REPAIR,
                    (float) $validated['amount_paid'],
                    $paymentMethod->type ?? 'cash',
                    $repair,
                    "Réparation #{$repair->repair_number} - {$repair->device_brand} {$repair->device_model}"
                );
            }

            ActivityLog::log('create', $repair, null, $repair->toArray(), "Réparation #{$repair->repair_number} - Diagnostic + Paiement");

            $this->notifications->repairCreated($repair);

            return $repair;
        });
    }

    public function recordDeposit(Repair $repair, float $depositAmount, PaymentMethod $paymentMethod, CashRegister $cashRegister): void
    {
        DB::transaction(function () use ($repair, $depositAmount, $paymentMethod, $cashRegister): void {
            $repair->update([
                'status'         => Repair::STATUS_PAID_PENDING_DIAGNOSIS,
                'deposit_amount' => $depositAmount,
                'amount_paid'    => $depositAmount,
                'payment_method' => $paymentMethod->type ?? 'cash',
                'paid_at'        => now(),
            ]);

            if ($depositAmount > 0) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_REPAIR,
                    $depositAmount,
                    $paymentMethod->type ?? 'cash',
                    $repair,
                    "Acompte réparation #{$repair->repair_number}"
                );
            }

            ActivityLog::log('payment', $repair, null, [
                'deposit_amount' => $depositAmount,
                'payment_method' => $paymentMethod->name,
            ], "Acompte réparation #{$repair->repair_number}");
        });
    }

    public function processPayment(Repair $repair, float $amount, string $paymentMethod, CashRegister $cashRegister): void
    {
        DB::transaction(function () use ($repair, $amount, $paymentMethod, $cashRegister): void {
            $repair->markAsPaid($paymentMethod, $amount);

            $cashRegister->addTransaction(
                CashTransaction::TYPE_INCOME,
                CashTransaction::CATEGORY_REPAIR,
                $amount,
                $paymentMethod,
                $repair,
                "Paiement réparation #{$repair->repair_number}"
            );

            ActivityLog::log('payment', $repair, null, [
                'amount' => $amount,
                'method' => $paymentMethod,
            ], "Paiement réparation #{$repair->repair_number}");
        });
    }

    public function deliver(Repair $repair, float $paidAmount, ?PaymentMethod $paymentMethod, ?CashRegister $cashRegister): void
    {
        DB::transaction(function () use ($repair, $paidAmount, $paymentMethod, $cashRegister): void {
            if ($paidAmount > 0 && $cashRegister) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_INCOME,
                    CashTransaction::CATEGORY_REPAIR,
                    $paidAmount,
                    $paymentMethod?->type ?? 'cash',
                    $repair,
                    "Solde réparation #{$repair->repair_number} - Livraison"
                );
            }

            $partsSales = Sale::where('repair_id', $repair->id)
                ->where('is_repair_parts', true)
                ->where('payment_status', 'credit')
                ->get();

            foreach ($partsSales as $partSale) {
                $partSale->update([
                    'amount_paid'    => $partSale->total_amount,
                    'amount_due'     => 0,
                    'payment_status' => 'paid',
                    'payment_method' => $paymentMethod?->type ?? 'cash',
                    'completed_at'   => now(),
                ]);
            }

            $repair->update([
                'amount_paid'  => ($repair->amount_paid ?? 0) + $paidAmount,
                'status'       => Repair::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            ActivityLog::log('update', $repair, null, [
                'status'           => 'delivered',
                'paid_amount'      => $paidAmount,
                'parts_sales_paid' => $partsSales->count(),
            ], "Livraison réparation #{$repair->repair_number}");
        });
    }

    /**
     * @return array{parts_count: int, refund_done: bool, amount_refunded: float}
     */
    public function cancel(Repair $repair, string $reason, User $user): array
    {
        return DB::transaction(function () use ($repair, $reason, $user): array {
            $repair->load('parts.product');

            foreach ($repair->parts as $part) {
                if (!$part->product_id) {
                    continue;
                }
                $product = Product::find($part->product_id);
                if (!$product) {
                    continue;
                }
                $before = $product->quantity_in_stock;
                $product->increment('quantity_in_stock', $part->quantity);

                StockMovement::create([
                    'shop_id'         => $repair->shop_id,
                    'product_id'      => $product->id,
                    'user_id'         => $user->id,
                    'type'            => StockMovement::TYPE_REPAIR_CANCEL,
                    'quantity'        => $part->quantity,
                    'quantity_before' => $before,
                    'quantity_after'  => $before + $part->quantity,
                    'reason'          => "Annulation réparation #{$repair->repair_number} : {$reason}",
                    'moveable_type'   => Repair::class,
                    'moveable_id'     => $repair->id,
                ]);

                if ($part->sale_id) {
                    Sale::where('id', $part->sale_id)
                        ->whereIn('payment_status', ['credit', 'partial'])
                        ->update(['payment_status' => 'cancelled']);
                }
            }

            Sale::where('repair_id', $repair->id)
                ->where('is_repair_parts', true)
                ->whereIn('payment_status', ['credit', 'partial'])
                ->update(['payment_status' => 'cancelled']);

            $amountRefunded = (float) $repair->amount_paid;
            $cashRegister   = CashRegister::getOpenRegisterForUser($user->id);
            $refundDone     = false;

            if ($amountRefunded > 0 && $cashRegister) {
                $cashRegister->addTransaction(
                    CashTransaction::TYPE_EXPENSE,
                    CashTransaction::CATEGORY_REPAIR_REFUND,
                    $amountRefunded,
                    $repair->payment_method ?? 'cash',
                    $repair,
                    "Remboursement annulation réparation #{$repair->repair_number} - {$repair->device_brand} {$repair->device_model}"
                );
                $refundDone = true;
            }

            $cancelNote = "[ANNULÉE le " . now()->format('d/m/Y H:i') . " par {$user->name}] " . $reason;
            $repair->update([
                'status'       => Repair::STATUS_CANCELLED,
                'repair_notes' => trim(($repair->repair_notes ? $repair->repair_notes . "\n\n" : '') . $cancelNote),
            ]);

            ActivityLog::log('cancel', $repair, null, [
                'status'          => 'cancelled',
                'reason'          => $reason,
                'parts_returned'  => $repair->parts->count(),
                'amount_refunded' => $refundDone ? $amountRefunded : 0,
            ], "Annulation réparation #{$repair->repair_number}");

            return [
                'parts_count'     => $repair->parts->count(),
                'refund_done'     => $refundDone,
                'amount_refunded' => $amountRefunded,
            ];
        });
    }

    public function addPart(Repair $repair, array $validated, int $userId): RepairPart
    {
        return DB::transaction(function () use ($repair, $validated, $userId): RepairPart {
            $partData = [
                'repair_id'  => $repair->id,
                'quantity'   => (int) $validated['quantity'],
                'unit_cost'  => (float) $validated['unit_price'],
                'total_cost' => (float) $validated['unit_price'] * (int) $validated['quantity'],
            ];

            $product  = null;
            $partName = '';

            if (!empty($validated['product_id'])) {
                $product = Product::findOrFail($validated['product_id']);

                if (!$product->hasStock($validated['quantity'])) {
                    throw new \DomainException("Stock insuffisant pour {$product->name}.");
                }

                $partData['product_id']  = $product->id;
                $partData['description'] = $product->name;
                $partName = $product->name;
            } else {
                $partData['description'] = $validated['description'];
                $partName = $validated['description'];
            }

            $repairPart  = RepairPart::create($partData);
            $totalAmount = (float) $validated['unit_price'] * (int) $validated['quantity'];

            $sale = Sale::create([
                'shop_id'         => $repair->shop_id,
                'user_id'         => $userId,
                'customer_id'     => $repair->customer_id,
                'repair_id'       => $repair->id,
                'is_repair_parts' => true,
                'client_type'     => 'customer',
                'subtotal'        => $totalAmount,
                'discount_amount' => 0,
                'tax_amount'      => 0,
                'total_amount'    => $totalAmount,
                'amount_paid'     => 0,
                'amount_due'      => $totalAmount,
                'payment_status'  => 'credit',
                'notes'           => "Pièce pour réparation #{$repair->repair_number}: {$partName}",
            ]);

            if ($product) {
                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $product->id,
                    'quantity'    => (int) $validated['quantity'],
                    'unit_price'  => (float) $validated['unit_price'],
                    'total_price' => $totalAmount,
                ]);

                $before = $product->quantity_in_stock;
                StockMovement::create([
                    'shop_id'         => $repair->shop_id,
                    'product_id'      => $product->id,
                    'type'            => 'sale',
                    'quantity'        => -(int) $validated['quantity'],
                    'quantity_before' => $before,
                    'quantity_after'  => $before - (int) $validated['quantity'],
                    'unit_cost'       => $product->purchase_price,
                    'reason'          => "Vente pièce réparation #{$repair->repair_number}",
                    'moveable_type'   => Sale::class,
                    'moveable_id'     => $sale->id,
                    'user_id'         => $userId,
                ]);

                $product->decrement('quantity_in_stock', $validated['quantity']);
            }

            $repairPart->update(['sale_id' => $sale->id]);

            $totalPartsCost = $repair->fresh()->calculatePartsCost();
            if ($totalPartsCost > ($repair->estimated_cost ?? 0)) {
                $repair->update(['estimated_cost' => $totalPartsCost]);
            }

            ActivityLog::log('update', $repair, null, [
                'part_added' => $partName,
                'quantity'   => $validated['quantity'],
                'sale_id'    => $sale->id,
            ], "Ajout pièce réparation #{$repair->repair_number}");

            return $repairPart;
        });
    }

    public function removePart(Repair $repair, RepairPart $part): void
    {
        DB::transaction(function () use ($repair, $part): void {
            if ($part->sale_id) {
                $sale = Sale::find($part->sale_id);
                if ($sale) {
                    $sale->stockMovements()->delete();
                    $sale->items()->delete();
                    $sale->delete();
                }
            }

            if ($part->product) {
                $part->product->incrementStock($part->quantity);
            }

            $productName = $part->description ?? $part->product?->name ?? 'Pièce';
            $part->delete();

            ActivityLog::log('update', $repair, null, ['part_removed' => $productName], "Retrait pièce réparation #{$repair->repair_number}");
        });
    }
}
