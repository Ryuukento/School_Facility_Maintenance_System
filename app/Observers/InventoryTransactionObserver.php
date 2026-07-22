<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

class InventoryTransactionObserver
{
    public function creating(InventoryTransaction $tx): void
    {
        // Validate that this transaction will not create negative stock
        // lockForUpdate() ensures the row is exclusively locked for the duration of the
        // surrounding DB::transaction() (e.g. DispatchService@releaseDispatch), preventing
        // two concurrent requests from both reading the same quantity and both passing.
        $item = Item::lockForUpdate()->find($tx->item_id);
        if (!$item) {
            throw new ModelNotFoundException('Item not found for transaction');
        }

        $qty = (int)$tx->quantity;

        switch ($tx->transaction_type) {
            case 'reserve':
                // reserved cannot exceed available stock
                $available = (int)$item->quantity - (int)$item->reserved_quantity;
                if ($qty > $available) {
                    throw new \RuntimeException('Insufficient available stock to reserve');
                }
                break;
            case 'deploy':
            case 'dispose':
                // deployment/dispose reduces actual quantity
                if ($qty > (int)$item->quantity) {
                    throw new \RuntimeException('Insufficient stock to deploy/dispose');
                }
                break;
            case 'release':
                if ($qty > (int)$item->reserved_quantity) {
                    throw new \RuntimeException('Cannot release more than reserved quantity');
                }
                break;
            case 'return':
            case 'adjustment':
                // allow, adjustments can be positive or negative but in creating we expect positive; adjustments negative handled elsewhere
                break;
            default:
                break;
        }
    }

    public function created(InventoryTransaction $tx): void
    {
        // Re-acquire the lock so this write is part of the same serialised transaction.
        $item = Item::lockForUpdate()->find($tx->item_id);
        if (!$item) {
            return;
        }

        $qty = (int)$tx->quantity;

        switch ($tx->transaction_type) {
            case 'reserve':
                $item->reserved_quantity = (int)$item->reserved_quantity + $qty;
                break;
            case 'release':
                $item->reserved_quantity = max(0, (int)$item->reserved_quantity - $qty);
                break;
            case 'deploy':
                // if deployed, reduce reserved if exists, and reduce quantity
                $item->reserved_quantity = max(0, (int)$item->reserved_quantity - $qty);
                $item->quantity = max(0, (int)$item->quantity - $qty);
                break;
            case 'return':
                $item->quantity = (int)$item->quantity + $qty;
                break;
            case 'adjustment':
                // Positive qty = stock increase. reference_note prefix 'DEDUCT:' signals a reduction.
                if (str_starts_with((string)($tx->reference_note ?? ''), 'DEDUCT:')) {
                    $item->quantity = max(0, (int)$item->quantity - $qty);
                } else {
                    $item->quantity = (int)$item->quantity + $qty;
                }
                break;
            case 'dispose':
                $item->quantity = max(0, (int)$item->quantity - $qty);
                break;
            default:
                break;
        }

        $item->save();

        app(ActivityLogService::class)->log([
            'user_id' => $tx->performed_by,
            'action' => 'INVENTORY_TRANSACTION',
            'module' => 'inventory',
            'entity_type' => 'item',
            'entity_id' => $item->id,
            'details' => 'Recorded inventory transaction ' . $tx->transaction_type . ' for item ' . $item->name . ' (qty ' . $tx->quantity . ').',
            'meta' => [
                'transaction_id' => $tx->id,
                'transaction_type' => $tx->transaction_type,
                'quantity' => (int) $tx->quantity,
                'reference_note' => $tx->reference_note,
            ],
            'dedupe_window_seconds' => 1,
        ]);

        // Invalidate analytics caches after transactions that change stock
        try {
            Cache::tags(['analytics'])->flush();
        } catch (\Throwable $e) {
            Cache::forget('analytics.inventorySummary:' . md5(json_encode([])));
            Cache::forget('analytics.overview:' . md5(json_encode([])));
            Cache::forget('analytics.lowStock:' . md5(json_encode([])));
            Cache::forget('analytics.inventoryHealth:' . md5(json_encode([])));
        }
    }

    public function deleted(InventoryTransaction $tx): void
    {
        // Rollback created changes conservatively. lockForUpdate() matches the
        // locking strategy used in creating()/created() above, preventing a
        // concurrent mutation from reading a stale quantity while this rollback
        // is in progress.
        $item = Item::lockForUpdate()->find($tx->item_id);
        if (!$item) {
            return;
        }

        $qty = (int)$tx->quantity;
        switch ($tx->transaction_type) {
            case 'reserve':
                $item->reserved_quantity = max(0, (int)$item->reserved_quantity - $qty);
                break;
            case 'release':
                $item->reserved_quantity = (int)$item->reserved_quantity + $qty;
                break;
            case 'deploy':
                $item->reserved_quantity = (int)$item->reserved_quantity + $qty;
                $item->quantity = (int)$item->quantity + $qty;
                break;
            case 'return':
                $item->quantity = max(0, (int)$item->quantity - $qty);
                break;
            case 'adjustment':
                // Reverse the original adjustment: DEDUCT: transactions added stock on rollback.
                if (str_starts_with((string)($tx->reference_note ?? ''), 'DEDUCT:')) {
                    $item->quantity = (int)$item->quantity + $qty;
                } else {
                    $item->quantity = max(0, (int)$item->quantity - $qty);
                }
                break;
            case 'dispose':
                $item->quantity = (int)$item->quantity + $qty;
                break;
            default:
                break;
        }

        $item->save();

        try {
            Cache::tags(['analytics'])->flush();
        } catch (\Throwable $e) {
            Cache::forget('analytics.inventorySummary:' . md5(json_encode([])));
            Cache::forget('analytics.overview:' . md5(json_encode([])));
            Cache::forget('analytics.lowStock:' . md5(json_encode([])));
            Cache::forget('analytics.inventoryHealth:' . md5(json_encode([])));
        }
    }
}
