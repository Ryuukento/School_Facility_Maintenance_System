<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Item;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReceiptPostingService
{
    public function postReceipt(int $receiptId, ?int $performedBy = null, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($receiptId, $performedBy, $ipAddress): void {
            $receipt = DB::table('purchase_receipts')
                ->where('id', $receiptId)
                ->lockForUpdate()
                ->first();

            if (!$receipt) {
                throw new ModelNotFoundException('Purchase receipt not found');
            }

            if ($receipt->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Receipt is already posted',
                ]);
            }

            $lineItems = DB::table('purchase_receipt_items')
                ->where('purchase_receipt_id', $receiptId)
                ->orderBy('id')
                ->get();

            if ($lineItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Cannot post a receipt with no items',
                ]);
            }

            foreach ($lineItems as $line) {
                $item = $this->resolveInventoryItem($line);

                if ((int) ($line->item_id ?? 0) !== (int) $item->id) {
                    DB::table('purchase_receipt_items')
                        ->where('id', $line->id)
                        ->update([
                            'item_id' => $item->id,
                            'updated_at' => now(),
                        ]);
                }

                InventoryTransaction::query()->create([
                    'item_id' => $item->id,
                    'report_id' => null,
                    'room_id' => null,
                    'transaction_type' => 'adjustment',
                    'quantity' => (int) $line->quantity_received,
                    'reference_note' => 'Purchase receipt OR#' . $receipt->or_number,
                    'performed_by' => $performedBy ?: null,
                ]);

                $item->refresh();
                $item->status = InventoryStatusService::deriveStatus(
                    (int) $item->quantity,
                    (int) ($item->reorder_level ?? 0)
                );
                $item->save();
            }

            DB::table('purchase_receipts')
                ->where('id', $receiptId)
                ->update([
                    'status' => 'posted',
                    'updated_at' => now(),
                ]);

            DB::table('activity_logs')->insert([
                'user_id' => $performedBy ?: null,
                'action' => 'purchase_posted',
                'entity_type' => 'purchase_receipt',
                'entity_id' => $receiptId,
                'details' => 'Posted purchase receipt OR#' . $receipt->or_number
                    . ' - ' . $lineItems->count() . ' line item(s)',
                'ip_address' => $ipAddress,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function resolveInventoryItem(object $line): Item
    {
        $lineItemId = $line->item_id !== null ? (int) $line->item_id : null;

        if ($lineItemId !== null) {
            $existing = Item::query()->lockForUpdate()->find($lineItemId);
            if ($existing) {
                return $existing;
            }
        }

        $matched = Item::query()
            ->lockForUpdate()
            ->where('item_type', 'inventory_stock')
            ->where('inventory_room_id', (int) $line->inventory_room_id)
            ->whereRaw('LOWER(name) = LOWER(?)', [(string) $line->item_name])
            ->first();

        if ($matched) {
            return $matched;
        }

        return Item::query()->create([
            'room_id' => null,
            'inventory_room_id' => (int) $line->inventory_room_id,
            'item_type' => 'inventory_stock',
            'name' => (string) $line->item_name,
            'status' => InventoryStatusService::deriveStatus(0, 0),
            'quantity' => 0,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
            'category_id' => $line->category_id !== null ? (int) $line->category_id : null,
            'unit_type' => (string) ($line->unit ?? 'pc'),
        ]);
    }
}
