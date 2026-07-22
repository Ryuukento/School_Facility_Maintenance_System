<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\DamageReportHistory;
use App\Models\Dispatch;
use App\Models\RepairHistory;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairService
{
    public const STATUSES = ['pending', 'assigned', 'diagnosing', 'repairing', 'waiting_parts', 'completed', 'failed', 'archived'];

    public function __construct(
        private readonly DispatchService $dispatchService,
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function normalizeRole(string $role): string
    {
        return RoleNormalizerService::normalize($role);
    }

    public function listRequests(array $filters, array $authUser): LengthAwarePaginator
    {
        $role = $this->normalizeRole((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['user_id'] ?? 0);

        $query = RepairRequest::query()->with([
            'damageReport.item:id,name,brand,model',
            'damageReport.room:id,name',
            'damageReport.department:department_id,name',
            'damageReport.reporter:user_id,full_name',
            'technician:user_id,full_name,email,role',
            'replacementItem:id,name,brand,model',
            'replacementDispatch:id,dispatch_code,status',
        ]);

        if (!in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true)) {
            $query->whereHas('damageReport', function ($builder) use ($userId): void {
                $builder->where('reported_by', $userId);
            });
        }

        if (!empty($filters['repair_status'])) {
            $query->where('repair_status', (string)$filters['repair_status']);
        }

        if (!empty($filters['damage_report_id'])) {
            $query->where('damage_report_id', (int)$filters['damage_report_id']);
        }

        if (!empty($filters['technician_user_id'])) {
            $query->where('technician_user_id', (int)$filters['technician_user_id']);
        }

        if (!empty($filters['q'])) {
            $q = strtolower(trim((string)$filters['q']));
            $query->where(function ($builder) use ($q): void {
                $builder->whereRaw('LOWER(repair_code) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(COALESCE(repair_type, \'\')) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(COALESCE(repair_description, \'\')) LIKE ?', ["%{$q}%"])
                    ->orWhereHas('damageReport', function ($damageQ) use ($q): void {
                        $damageQ->whereRaw('LOWER(damage_report_code) LIKE ?', ["%{$q}%"])
                            ->orWhereRaw('LOWER(damage_description) LIKE ?', ["%{$q}%"])
                            ->orWhereHas('item', function ($itemQ) use ($q): void {
                                $itemQ->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]);
                            });
                    })
                    ->orWhereHas('technician', function ($techQ) use ($q): void {
                        $techQ->whereRaw('LOWER(full_name) LIKE ?', ["%{$q}%"])
                            ->orWhereRaw('LOWER(email) LIKE ?', ["%{$q}%"]);
                    });
            });
        }

        $perPage = max(1, min(100, (int)($filters['per_page'] ?? 15)));
        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function createRequest(array $data, array $authUser): RepairRequest
    {
        $userId = (int)($authUser['user_id'] ?? 0);
        if ($userId <= 0) {
            throw ValidationException::withMessages([
                'user' => 'Authenticated user is required.',
            ]);
        }

        $damageReport = DamageReport::query()->with('repairRequest')->find((int)$data['damage_report_id']);
        if (!$damageReport) {
            throw ValidationException::withMessages([
                'damage_report_id' => 'Damage report not found.',
            ]);
        }

        if ($damageReport->repairRequest) {
            throw ValidationException::withMessages([
                'damage_report_id' => 'A repair request already exists for this damage report.',
            ]);
        }

        $technicianId = !empty($data['technician_user_id']) ? (int)$data['technician_user_id'] : null;
        if ($technicianId !== null) {
            $this->assertTechnician($technicianId);
        }

        return DB::transaction(function () use ($data, $userId, $damageReport, $technicianId, $authUser): RepairRequest {
            $repair = RepairRequest::query()->create([
                'repair_code' => 'RPR-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                'damage_report_id' => $damageReport->id,
                'technician_user_id' => $technicianId,
                'repair_type' => trim((string)($data['repair_type'] ?? 'corrective')),
                'repair_description' => trim((string)($data['repair_description'] ?? '')),
                'repair_cost' => $data['repair_cost'] ?? 0,
                'repair_status' => $technicianId ? 'assigned' : 'pending',
                'repair_date' => $data['repair_date'] ?? now()->toDateString(),
                'estimated_completion_date' => $data['estimated_completion_date'] ?? null,
                'completion_date' => null,
                'notes' => trim((string)($data['notes'] ?? '')) ?: null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->recordHistory($repair->id, 'created', null, $repair->repair_status, $technicianId, $repair->notes, [
                'damage_report_id' => $damageReport->id,
                'repair_type' => $repair->repair_type,
            ], $userId);

            if ($technicianId) {
                $this->recordHistory($repair->id, 'technician_assigned', 'pending', 'assigned', $technicianId, 'Technician assigned during repair request creation.', [], $userId);
            }

            $this->syncDamageReportStatus($damageReport, $repair->repair_status, $userId, $repair->notes);

            $this->activityLogService->logFromSession([
                'user_id' => $userId,
                'user_role' => $authUser['role'] ?? null,
                'action' => 'CREATE_REPAIR_REQUEST',
                'module' => 'repair',
                'entity_type' => 'repair_request',
                'entity_id' => $repair->id,
                'details' => 'Created repair request ' . $repair->repair_code . '.',
                'meta' => [
                    'damage_report_id' => $damageReport->id,
                    'technician_user_id' => $technicianId,
                ],
            ], $authUser);

            return $repair->load([
                'damageReport.item:id,name,brand,model',
                'damageReport.room:id,name',
                'damageReport.department:department_id,name',
                'technician:user_id,full_name,email,role',
            ]);
        });
    }

    public function assignTechnician(RepairRequest $repair, array $data, array $authUser): RepairRequest
    {
        $userId = (int)($authUser['user_id'] ?? 0);
        $technicianId = (int)($data['technician_user_id'] ?? 0);
        $this->assertTechnician($technicianId);

        return DB::transaction(function () use ($repair, $data, $userId, $technicianId, $authUser): RepairRequest {
            $previousTechnicianId = $repair->technician_user_id ? (int)$repair->technician_user_id : null;
            $fromStatus = $repair->repair_status;
            $nextStatus = in_array($repair->repair_status, ['pending', 'failed'], true) ? 'assigned' : $repair->repair_status;

            $repair->technician_user_id = $technicianId;
            $repair->estimated_completion_date = $data['estimated_completion_date'] ?? $repair->estimated_completion_date;
            $repair->notes = trim((string)($data['notes'] ?? '')) ?: $repair->notes;
            $repair->repair_status = $nextStatus;
            $repair->updated_by = $userId;
            $repair->save();

            $action = $previousTechnicianId && $previousTechnicianId !== $technicianId ? 'technician_reassigned' : 'technician_assigned';
            $this->recordHistory($repair->id, $action, $fromStatus, $nextStatus, $technicianId, $repair->notes, [
                'previous_technician_user_id' => $previousTechnicianId,
                'estimated_completion_date' => $repair->estimated_completion_date?->format('Y-m-d'),
            ], $userId);

            $this->syncDamageReportStatus($repair->damageReport()->firstOrFail(), $nextStatus, $userId, $repair->notes);

            $this->activityLogService->logFromSession([
                'user_id' => $userId,
                'user_role' => $authUser['role'] ?? null,
                'action' => 'ASSIGN_REPAIR_TECHNICIAN',
                'module' => 'repair',
                'entity_type' => 'repair_request',
                'entity_id' => $repair->id,
                'details' => ($action === 'technician_reassigned' ? 'Reassigned' : 'Assigned') . ' technician for repair request ' . $repair->repair_code . '.',
                'meta' => [
                    'previous_technician_user_id' => $previousTechnicianId,
                    'technician_user_id' => $technicianId,
                    'repair_status' => $nextStatus,
                ],
            ], $authUser);

            return $repair->fresh([
                'damageReport.item:id,name,brand,model',
                'damageReport.room:id,name',
                'damageReport.department:department_id,name',
                'technician:user_id,full_name,email,role',
            ]);
        });
    }

    public function updateRequest(RepairRequest $repair, array $data, array $authUser): RepairRequest
    {
        $userId = (int)($authUser['user_id'] ?? 0);
        $nextStatus = strtolower(trim((string)($data['repair_status'] ?? $repair->repair_status)));

        if (!in_array($nextStatus, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'repair_status' => 'Invalid repair status.',
            ]);
        }

        return DB::transaction(function () use ($repair, $data, $userId, $nextStatus, $authUser): RepairRequest {
            $fromStatus = $repair->repair_status;
            $repair->repair_type = trim((string)($data['repair_type'] ?? $repair->repair_type));
            $repair->repair_description = trim((string)($data['repair_description'] ?? $repair->repair_description));
            $repair->repair_cost = isset($data['repair_cost']) ? (float)$data['repair_cost'] : $repair->repair_cost;
            $repair->repair_date = $data['repair_date'] ?? $repair->repair_date;
            $repair->estimated_completion_date = $data['estimated_completion_date'] ?? $repair->estimated_completion_date;
            $repair->notes = array_key_exists('notes', $data) ? (trim((string)$data['notes']) ?: null) : $repair->notes;
            $repair->failure_reason = array_key_exists('failure_reason', $data) ? (trim((string)$data['failure_reason']) ?: null) : $repair->failure_reason;
            $repair->repair_status = $nextStatus;
            $repair->updated_by = $userId;

            if (in_array($nextStatus, ['completed', 'archived'], true)) {
                $repair->completion_date = $data['completion_date'] ?? ($repair->completion_date ?: now()->toDateString());
            } elseif (array_key_exists('completion_date', $data)) {
                $repair->completion_date = $data['completion_date'];
            }

            if ($nextStatus === 'archived') {
                $repair->archived_at = now();
            }

            $repair->save();

            $this->recordHistory($repair->id, 'status_changed', $fromStatus, $nextStatus, $repair->technician_user_id, $repair->notes, [
                'repair_cost' => $repair->repair_cost,
                'failure_reason' => $repair->failure_reason,
                'completion_date' => $repair->completion_date?->format('Y-m-d'),
            ], $userId);

            $this->syncDamageReportStatus($repair->damageReport()->firstOrFail(), $nextStatus, $userId, $repair->notes ?: $repair->failure_reason);

            $this->activityLogService->logFromSession([
                'user_id' => $userId,
                'user_role' => $authUser['role'] ?? null,
                'action' => 'UPDATE_REPAIR',
                'module' => 'repair',
                'entity_type' => 'repair_request',
                'entity_id' => $repair->id,
                'details' => 'Updated repair request ' . $repair->repair_code . ' to status ' . $nextStatus . '.',
                'meta' => [
                    'from_status' => $fromStatus,
                    'to_status' => $nextStatus,
                    'technician_user_id' => $repair->technician_user_id,
                    'repair_cost' => $repair->repair_cost,
                ],
                'dedupe_window_seconds' => 1,
            ], $authUser);

            return $repair->fresh([
                'damageReport.item:id,name,brand,model',
                'damageReport.room:id,name',
                'damageReport.department:department_id,name',
                'damageReport.reporter:user_id,full_name',
                'technician:user_id,full_name,email,role',
                'replacementItem:id,name,brand,model',
                'replacementDispatch:id,dispatch_code,status',
            ]);
        });
    }

    public function fulfillReplacement(RepairRequest $repair, array $data, array $authUser): RepairRequest
    {
        $userId = (int)($authUser['user_id'] ?? 0);
        $replacementItemId = (int)($data['replacement_item_id'] ?? 0);
        $replacementQty = max(1, (int)($data['replacement_quantity'] ?? 1));

        if ($repair->replacement_dispatch_id || $repair->replacement_transaction_id) {
            throw ValidationException::withMessages([
                'replacement' => 'Replacement has already been fulfilled for this repair request.',
            ]);
        }

        if ($repair->repair_status !== 'failed') {
            throw ValidationException::withMessages([
                'repair_status' => 'Replacement can only be fulfilled after a failed repair.',
            ]);
        }

        $damageReport = $repair->damageReport()->firstOrFail();

        return DB::transaction(function () use ($repair, $data, $authUser, $userId, $replacementItemId, $replacementQty, $damageReport): RepairRequest {
            $dispatch = $this->dispatchService->createDispatch([
                'department_id' => $damageReport->department_id,
                'room_id' => $damageReport->room_id,
                'repair_request_id' => $repair->id,
                'damage_report_id' => $damageReport->id,
                'notes' => trim((string)($data['notes'] ?? '')) ?: sprintf('Replacement dispatch for %s / %s', $repair->repair_code, $damageReport->damage_report_code),
                'items' => [[
                    'item_id' => $replacementItemId,
                    'quantity' => $replacementQty,
                ]],
            ], $userId);

            $approved = $this->dispatchService->approveDispatch($dispatch, $userId, $userId);
            $released = $this->dispatchService->releaseDispatch($approved, $userId, $damageReport->reported_by, $userId);
            $transaction = $released['transactions']->first();

            $repair->replacement_item_id = $replacementItemId;
            $repair->replacement_quantity = $replacementQty;
            $repair->replacement_dispatch_id = $released['dispatch']->id;
            $repair->replacement_transaction_id = $transaction?->id;
            $repair->repair_status = 'archived';
            $repair->completion_date = now()->toDateString();
            $repair->archived_at = now();
            $repair->notes = trim((string)($data['notes'] ?? '')) ?: $repair->notes;
            $repair->updated_by = $userId;
            $repair->save();

            $previousDamageStatus = $damageReport->status;
            $damageReport->replacement_item_id = $replacementItemId;
            $damageReport->replacement_quantity = $replacementQty;
            $damageReport->replacement_transaction_id = $transaction?->id;
            $damageReport->status = 'replaced';
            $damageReport->repair_notes = trim((string)($data['notes'] ?? '')) ?: $damageReport->repair_notes;
            $damageReport->replaced_by = $userId;
            $damageReport->replaced_at = now();
            $damageReport->save();

            $this->recordHistory($repair->id, 'replacement_fulfilled', 'failed', 'archived', $repair->technician_user_id, $repair->notes, [
                'replacement_item_id' => $replacementItemId,
                'replacement_quantity' => $replacementQty,
                'replacement_dispatch_id' => $released['dispatch']->id,
                'replacement_transaction_id' => $transaction?->id,
            ], $userId);

            $this->recordDamageHistory($damageReport->id, 'replacement_completed', $previousDamageStatus, 'replaced', $repair->notes, [
                'repair_request_id' => $repair->id,
                'replacement_dispatch_id' => $released['dispatch']->id,
                'replacement_transaction_id' => $transaction?->id,
            ], $userId);

            $this->activityLogService->logFromSession([
                'user_id' => $userId,
                'user_role' => $authUser['role'] ?? null,
                'action' => 'REPLACEMENT_ACTION',
                'module' => 'replacement',
                'entity_type' => 'repair_request',
                'entity_id' => $repair->id,
                'details' => 'Fulfilled replacement for repair request ' . $repair->repair_code . '.',
                'meta' => [
                    'replacement_item_id' => $replacementItemId,
                    'replacement_quantity' => $replacementQty,
                    'replacement_dispatch_id' => $released['dispatch']->id,
                    'replacement_transaction_id' => $transaction?->id,
                    'damage_report_id' => $damageReport->id,
                ],
            ], $authUser);

            return $repair->fresh([
                'damageReport.item:id,name,brand,model',
                'damageReport.room:id,name',
                'damageReport.department:department_id,name',
                'damageReport.reporter:user_id,full_name',
                'technician:user_id,full_name,email,role',
                'replacementItem:id,name,brand,model',
                'replacementDispatch:id,dispatch_code,status',
            ]);
        });
    }

    public function getHistories(RepairRequest $repair)
    {
        return $repair->histories()->with([
            'technician:user_id,full_name',
            'changedBy:user_id,full_name',
        ])->orderByDesc('created_at')->get();
    }

    public function searchEligibleDamageReports(string $query = '', int $limit = 20)
    {
        $builder = DamageReport::query()
            ->with(['item:id,name', 'room:id,name'])
            ->whereDoesntHave('repairRequest')
            ->whereIn('status', ['pending', 'under_review', 'repairing'])
            ->orderByDesc('created_at');

        if ($query !== '') {
            $q = strtolower(trim($query));
            $builder->where(function ($inner) use ($q): void {
                $inner->whereRaw('LOWER(damage_report_code) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(damage_description) LIKE ?', ["%{$q}%"])
                    ->orWhereHas('item', function ($itemQ) use ($q): void {
                        $itemQ->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]);
                    });
            });
        }

        return $builder->limit(max(1, min(50, $limit)))->get()
            ->map(static function (DamageReport $report): array {
                return [
                    'id' => $report->id,
                    'damage_report_code' => $report->damage_report_code,
                    'item_name' => $report->item?->name,
                    'room_name' => $report->room?->name,
                    'label' => trim($report->damage_report_code . ' - ' . ($report->item?->name ?? 'Unknown item')),
                ];
            })
            ->values();
    }

    public function searchTechnicians(string $query = '', int $limit = 20)
    {
        $allowedRoles = RoleNormalizerService::rawValuesFor(['maintenance_admin', 'maintenance_staff']);

        $builder = User::query()
            ->select(['user_id', 'full_name', 'email', 'role'])
            ->where('status', 'active')
            ->whereIn('role', $allowedRoles)
            ->orderBy('full_name');

        if ($query !== '') {
            $q = strtolower(trim($query));
            $builder->where(function ($inner) use ($q): void {
                $inner->whereRaw('LOWER(full_name) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$q}%"]);
            });
        }

        return $builder->limit(max(1, min(50, $limit)))->get();
    }

    private function assertTechnician(int $technicianUserId): void
    {
        $technician = User::query()->find($technicianUserId);
        $role = $this->normalizeRole((string)($technician->role ?? ''));

        if (!$technician || $technician->status !== 'active' || !in_array($role, ['maintenance_admin', 'maintenance_staff'], true)) {
            throw ValidationException::withMessages([
                'technician_user_id' => 'Selected technician is invalid.',
            ]);
        }
    }

    private function syncDamageReportStatus(DamageReport $damageReport, string $repairStatus, int $changedBy, ?string $notes = null): void
    {
        $mappedStatus = match ($repairStatus) {
            'pending', 'assigned', 'diagnosing', 'failed' => 'under_review',
            'repairing', 'waiting_parts' => 'repairing',
            'completed' => 'repaired',
            'archived' => $damageReport->status === 'replaced' ? 'replaced' : 'closed',
            default => $damageReport->status,
        };

        if ($mappedStatus === $damageReport->status && !$notes) {
            return;
        }

        $fromStatus = $damageReport->status;
        $damageReport->status = $mappedStatus;
        if ($notes) {
            $damageReport->repair_notes = $notes;
        }
        if ($mappedStatus === 'closed' && $damageReport->closed_at === null) {
            $damageReport->closed_at = now();
        }
        $damageReport->save();

        if ($fromStatus !== $mappedStatus || $notes) {
            $this->recordDamageHistory($damageReport->id, 'repair_workflow_sync', $fromStatus, $mappedStatus, $notes, [
                'repair_status' => $repairStatus,
            ], $changedBy);
        }
    }

    private function recordHistory(
        int $repairRequestId,
        string $actionType,
        ?string $fromStatus,
        ?string $toStatus,
        ?int $technicianUserId,
        ?string $notes,
        array $meta,
        int $changedBy
    ): void {
        RepairHistory::query()->create([
            'repair_request_id' => $repairRequestId,
            'action_type' => $actionType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'technician_user_id' => $technicianUserId,
            'notes' => $notes,
            'meta_json' => !empty($meta) ? json_encode($meta) : null,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);
    }

    private function recordDamageHistory(
        int $damageReportId,
        string $actionType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $notes,
        array $meta,
        int $changedBy
    ): void {
        DamageReportHistory::query()->create([
            'damage_report_id' => $damageReportId,
            'action_type' => $actionType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
            'meta_json' => !empty($meta) ? json_encode($meta) : null,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);
    }
}
