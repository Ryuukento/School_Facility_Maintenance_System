<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\MaintenanceReport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NeedChangeService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        // TASK 18 — "Replacement Approved" notification.
        private readonly NotificationService $notificationService
    ) {
    }

    /**
     * Approves a report's Need Change request: deducts the requested item
     * through the standard ledger (InventoryTransaction -> Observer) path
     * and stamps need_change_deducted_at. Idempotent — re-approving an
     * already-deducted report is a no-op, not a second deduction.
     */
    public function approve(MaintenanceReport $report, int $approvedBy): MaintenanceReport
    {
        return DB::transaction(function () use ($report, $approvedBy): MaintenanceReport {
            $locked = MaintenanceReport::query()
                ->where('report_id', $report->report_id)
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                throw new ModelNotFoundException('Maintenance report not found');
            }

            // Duplicate-approval guard: need_change_deducted_at is only ever set
            // once, inside this same locked transaction, so a second concurrent
            // or repeated approval call observes it already set and no-ops here
            // instead of creating a second InventoryTransaction.
            if ($locked->need_change_deducted_at !== null) {
                return $locked;
            }

            if (empty($locked->need_change_item_id)) {
                throw ValidationException::withMessages([
                    'need_change_item_id' => 'This report has no Need Change request to process.',
                ]);
            }

            $quantity = max(1, (int) $locked->need_change_quantity);

            $item = Item::query()->lockForUpdate()->find($locked->need_change_item_id);
            if (!$item) {
                throw ValidationException::withMessages([
                    'need_change_item_id' => 'Need Change item was not found in inventory.',
                ]);
            }

            if ($quantity > (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'need_change_quantity' => 'Insufficient stock for approval. Available: '
                        . $item->quantity . ', required: ' . $quantity,
                ]);
            }

            // 'deploy' — the requested item physically leaves inventory to be
            // used as the replacement part for this report, mirroring the
            // Dispatch release path. The Observer performs and audits the
            // actual quantity mutation; this Service never touches Item
            // directly (see ARCHITECTURE.md Section 5 - single writer principle).
            InventoryTransaction::query()->create([
                'item_id' => $item->id,
                'report_id' => $locked->report_id,
                'room_id' => null,
                'transaction_type' => 'deploy',
                'quantity' => $quantity,
                'reference_note' => 'Need Change approval for report #' . $locked->report_id,
                'performed_by' => $approvedBy,
            ]);

            $now = now();
            $locked->update([
                'need_change_status' => 'deducted',
                'need_change_approved_by' => $approvedBy,
                'need_change_approved_at' => $now,
                'need_change_deducted_at' => $now,
            ]);

            $this->activityLogService->logFromSession([
                'user_id' => $approvedBy,
                'action' => 'APPROVE_NEED_CHANGE',
                'module' => 'report',
                'entity_type' => 'report',
                'entity_id' => $locked->report_id,
                'details' => 'Approved Need Change and deducted ' . $quantity
                    . ' item(s) of "' . $item->name . '" for report #' . $locked->report_id . '.',
                'meta' => [
                    'need_change_item_id' => $item->id,
                    'quantity' => $quantity,
                ],
            ]);

            // TASK 18 — "Replacement Approved" notification.
            $this->notifyReplacementApproved($locked, $item->name, $quantity, $approvedBy);

            return $locked;
        });
    }

    /**
     * TASK 18 — notifies the report's creator and assignee (if any), plus all
     * active Super Admins, that the Need Change replacement was approved and
     * inventory released. Reuses entity_type 'report' (Task 17 architecture)
     * so the notification deep-links to the same report page. Excludes the
     * approving user from the recipient list to avoid self-notification.
     */
    private function notifyReplacementApproved(MaintenanceReport $locked, string $itemName, int $quantity, int $approvedBy): void
    {
        $title = 'Replacement Approved — Report #' . $locked->report_id;
        $message = 'Your Need Change request for "' . $itemName . '" (qty: ' . $quantity
            . ') on Report #' . $locked->report_id . ' has been approved and released from inventory.';

        $recipientIds = collect([$locked->created_by, $locked->assigned_to])
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id);

        $superAdminRoles = RoleNormalizerService::rawValuesFor(['super_admin']);
        $superAdminIds = User::query()
            ->where('status', 'active')
            ->whereIn('role', $superAdminRoles)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id);

        $recipientIds = $recipientIds->merge($superAdminIds)
            ->unique()
            ->reject(fn (int $id) => $id === (int) $approvedBy);

        foreach ($recipientIds as $recipientId) {
            $this->notificationService->notify($recipientId, $title, $message, 'report', $locked->report_id);
        }
    }
}
