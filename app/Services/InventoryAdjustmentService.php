<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Item;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        // TASK 18 — notify once on a genuine NORMAL -> LOW/OUT_OF_STOCK transition.
        private readonly InventoryLowStockNotifier $inventoryLowStockNotifier
    ) {
    }

    /**
     * Manually increases or decreases an item's on-hand quantity through the
     * standard ledger (InventoryTransaction -> Observer) path, reusing the
     * 'adjustment' transaction type and its existing 'DEDUCT:' reference-note
     * convention for decreases (see ItemController::store(),
     * InventoryTransactionObserver::created()/deleted()). This Service never
     * writes Item.quantity/reserved_quantity directly (ARCHITECTURE.md Section 5).
     */
    public function adjust(Item $item, string $direction, int $quantity, string $reason, int $performedBy): Item
    {
        return DB::transaction(function () use ($item, $direction, $quantity, $reason, $performedBy): Item {
            $locked = Item::query()->lockForUpdate()->find($item->id);
            if (!$locked) {
                throw new ModelNotFoundException('Item not found');
            }

            if ($direction === 'decrease') {
                // The Observer's creating() hook does not validate 'adjustment'
                // transactions for insufficient stock (negative adjustments are
                // "handled elsewhere" per its own comment) — this Service is the
                // one place that enforces it, against the locked row.
                $available = (int) $locked->quantity - (int) $locked->reserved_quantity;
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Insufficient stock for adjustment. Available: '
                            . $available . ', requested: ' . $quantity,
                    ]);
                }
            }

            $referenceNote = ($direction === 'decrease' ? 'DEDUCT: ' : '')
                . 'Manual stock adjustment - ' . $reason;

            InventoryTransaction::query()->create([
                'item_id' => $locked->id,
                'report_id' => null,
                'room_id' => null,
                'transaction_type' => 'adjustment',
                'quantity' => $quantity,
                'reference_note' => $referenceNote,
                'performed_by' => $performedBy,
            ]);

            // Mirrors ItemController::store()'s post-transaction status
            // re-derivation for the same 'adjustment' transaction type.
            $locked->refresh();
            $previousStatus = $locked->status;
            $locked->status = InventoryStatusService::deriveStatus((int) $locked->quantity, (int) ($locked->reorder_level ?? 0));
            $locked->save();

            // TASK 18 — fires only on a genuine NORMAL -> LOW/OUT_OF_STOCK transition.
            $this->inventoryLowStockNotifier->handleStatusChange(
                $locked->id,
                $locked->name,
                $previousStatus,
                $locked->status
            );

            $this->activityLogService->logFromSession([
                'user_id' => $performedBy,
                'action' => 'ADJUST_STOCK',
                'module' => 'inventory',
                'entity_type' => 'item',
                'entity_id' => $locked->id,
                'details' => ucfirst($direction) . 'd stock of "' . $locked->name . '" by ' . $quantity . '. Reason: ' . $reason,
                'meta' => [
                    'direction' => $direction,
                    'quantity' => $quantity,
                    'reason' => $reason,
                ],
            ]);

            return $locked;
        });
    }
}
