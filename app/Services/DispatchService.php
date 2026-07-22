<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispatchService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function createDispatch(array $data, ?int $actorUserId = null): Dispatch
    {
        $dispatchCode = 'DSP-' . strtoupper(uniqid());

        return DB::transaction(function () use ($data, $dispatchCode, $actorUserId): Dispatch {
            $dispatch = Dispatch::query()->create([
                'dispatch_code'       => $dispatchCode,
                'department_id'       => $data['department_id']       ?? null,
                'room_id'             => $data['room_id']             ?? null,
                'repair_request_id'   => $data['repair_request_id']   ?? null,
                'damage_report_id'    => $data['damage_report_id']    ?? null,
                'purchase_receipt_id' => $data['purchase_receipt_id'] ?? null,
                'status' => 'pending',
                'notes'  => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $it) {
                DispatchItem::query()->create([
                    'dispatch_id' => $dispatch->id,
                    'item_id' => $it['item_id'],
                    'quantity' => $it['quantity'],
                ]);
            }

            if ($actorUserId) {
                $this->activityLogService->logFromSession([
                    'user_id' => $actorUserId,
                    'action' => 'CREATE_DISPATCH',
                    'module' => 'dispatch',
                    'entity_type' => 'dispatch',
                    'entity_id' => $dispatch->id,
                    'details' => 'Created dispatch ' . $dispatchCode . '.',
                    'meta' => [
                        'dispatch_code' => $dispatchCode,
                        'items_count' => count($data['items']),
                    ],
                ]);
            }

            return $dispatch->load('items.item');
        });
    }

    public function approveDispatch(Dispatch $dispatch, int $approvedBy, ?int $actorUserId = null): Dispatch
    {
        if ($dispatch->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending dispatches can be approved.',
            ]);
        }

        $dispatch->update([
            'approved_by' => $approvedBy,
            'status' => 'approved',
        ]);

        if ($actorUserId) {
            $this->activityLogService->logFromSession([
                'user_id' => $actorUserId,
                'action' => 'APPROVE_DISPATCH',
                'module' => 'dispatch',
                'entity_type' => 'dispatch',
                'entity_id' => $dispatch->id,
                'details' => 'Approved dispatch ' . $dispatch->dispatch_code . '.',
            ]);
        }

        return $dispatch->fresh('items.item');
    }

    public function cancelDispatch(Dispatch $dispatch, string $reason, ?int $actorUserId = null): Dispatch
    {
        if (!in_array($dispatch->status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or approved dispatches can be cancelled.',
            ]);
        }

        $dispatch->update([
            'status' => 'cancelled',
            'notes'  => $dispatch->notes
                ? $dispatch->notes . ' | CANCELLED: ' . $reason
                : 'CANCELLED: ' . $reason,
        ]);

        if ($actorUserId) {
            $this->activityLogService->logFromSession([
                'user_id'     => $actorUserId,
                'action'      => 'CANCEL_DISPATCH',
                'module'      => 'dispatch',
                'entity_type' => 'dispatch',
                'entity_id'   => $dispatch->id,
                'details'     => 'Cancelled dispatch ' . $dispatch->dispatch_code . '. Reason: ' . $reason,
                'meta'        => ['dispatch_code' => $dispatch->dispatch_code, 'reason' => $reason],
            ]);
        }

        return $dispatch->fresh('items.item');
    }

    /**
     * @return array{dispatch: Dispatch, transactions: Collection<int, InventoryTransaction>}
     */
    public function releaseDispatch(Dispatch $dispatch, int $releasedBy, ?int $receiverUserId = null, ?int $actorUserId = null): array
    {
        if ($dispatch->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Only approved dispatches can be released.',
            ]);
        }

        $items = $dispatch->items()->with('item')->get();

        foreach ($items as $di) {
            $item = $di->item;
            if (!$item) {
                throw ValidationException::withMessages([
                    'items' => 'Item not found for dispatch item.',
                ]);
            }

            $available = (int) $item->quantity - (int) $item->reserved_quantity;
            if ((int) $di->quantity > $available) {
                throw ValidationException::withMessages([
                    'items' => 'Insufficient stock for item ' . $item->name,
                ]);
            }
        }

        return DB::transaction(function () use ($dispatch, $releasedBy, $receiverUserId, $actorUserId, $items): array {
            $transactions = collect();

            foreach ($items as $di) {
                $transactions->push(InventoryTransaction::query()->create([
                    'item_id' => $di->item_id,
                    'report_id' => null,
                    'room_id' => $dispatch->room_id,
                    'transaction_type' => 'deploy',
                    'quantity' => $di->quantity,
                    'reference_note' => 'Dispatch ' . $dispatch->dispatch_code,
                    'performed_by' => $releasedBy,
                ]));
            }

            $dispatch->update([
                'released_by' => $releasedBy,
                'receiver_user_id' => $receiverUserId,
                'status' => 'released',
            ]);

            if ($actorUserId) {
                $this->activityLogService->logFromSession([
                    'user_id' => $actorUserId,
                    'action' => 'RELEASE_DISPATCH',
                    'module' => 'dispatch',
                    'entity_type' => 'dispatch',
                    'entity_id' => $dispatch->id,
                    'details' => 'Released dispatch ' . $dispatch->dispatch_code . '.',
                    'meta' => [
                        'receiver_user_id' => $receiverUserId,
                        'transaction_count' => $transactions->count(),
                    ],
                ]);
            }

            return [
                'dispatch' => $dispatch->fresh('items.item'),
                'transactions' => $transactions,
            ];
        });
    }
}
