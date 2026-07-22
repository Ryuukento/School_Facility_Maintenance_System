<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\DamageReportHistory;
use App\Models\Dispatch;
use App\Models\InventoryTransaction;
use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DamageReportService
{
    private const ACTIVE_STATUSES = ['pending', 'under_review', 'repairing'];

    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function normalizeRole(string $role): string
    {
        return RoleNormalizerService::normalizeWithStaffDefault($role);
    }

    public function listReports(array $filters, array $authUser): LengthAwarePaginator
    {
        $role = $this->normalizeRole((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['user_id'] ?? 0);

        $query = DamageReport::query()->with([
            'item:id,name,brand,model',
            'room:id,name',
            'department:department_id,name',
            'reporter:user_id,full_name',
            'replacementItem:id,name,brand,model',
        ]);

        if (!in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true)) {
            $query->where('reported_by', $userId);
        }

        if (!empty($filters['status'])) {
            $query->where('status', (string)$filters['status']);
        }

        if (!empty($filters['severity_level'])) {
            $query->where('severity_level', (string)$filters['severity_level']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', (int)$filters['department_id']);
        }

        if (!empty($filters['room_id'])) {
            $query->where('room_id', (int)$filters['room_id']);
        }

        if (!empty($filters['q'])) {
            $q = strtolower(trim((string)$filters['q']));
            $query->where(function ($builder) use ($q): void {
                $builder->whereRaw('LOWER(damage_report_code) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(damage_description) LIKE ?', ["%{$q}%"])
                    ->orWhereHas('item', function ($itemQ) use ($q): void {
                        $itemQ->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"])
                            ->orWhereRaw('LOWER(COALESCE(brand,\'\')) LIKE ?', ["%{$q}%"])
                            ->orWhereRaw('LOWER(COALESCE(model,\'\')) LIKE ?', ["%{$q}%"]);
                    })
                    ->orWhereHas('room', function ($roomQ) use ($q): void {
                        $roomQ->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]);
                    })
                    ->orWhereHas('department', function ($deptQ) use ($q): void {
                        $deptQ->whereRaw('LOWER(name) LIKE ?', ["%{$q}%"]);
                    });
            });
        }

        $perPage = max(1, min(200, (int)($filters['per_page'] ?? 20)));

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function createReport(array $data, array $authUser, ?UploadedFile $imageFile = null): DamageReport
    {
        $reporterId = (int)($authUser['user_id'] ?? 0);
        if ($reporterId <= 0) {
            throw ValidationException::withMessages([
                'reported_by' => 'Authenticated user is required to create a damage report.',
            ]);
        }

        $itemId = (int)($data['item_id'] ?? 0);
        $roomId = (int)($data['room_id'] ?? 0);
        $departmentId = (int)($data['department_id'] ?? 0);
        $sourceDispatchId = isset($data['source_dispatch_id']) && $data['source_dispatch_id'] !== '' ? (int)$data['source_dispatch_id'] : null;

        if ($itemId <= 0 || $roomId <= 0 || $departmentId <= 0) {
            throw ValidationException::withMessages([
                'item_id' => 'Item, room, and department are required.',
            ]);
        }

        $this->validateDeployedItem($itemId, $roomId, $sourceDispatchId);
        $this->preventDuplicateActiveReport($itemId, $roomId, $departmentId);

        $imagePath = null;
        if ($imageFile !== null) {
            $imagePath = $this->storeImage($imageFile);
        }

        return DB::transaction(function () use ($data, $itemId, $roomId, $departmentId, $sourceDispatchId, $reporterId, $imagePath, $authUser): DamageReport {
            $code = 'DMG-' . now()->format('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $report = DamageReport::query()->create([
                'damage_report_code' => $code,
                'item_id' => $itemId,
                'room_id' => $roomId,
                'department_id' => $departmentId,
                'source_dispatch_id' => $sourceDispatchId,
                'damage_description' => trim((string)($data['damage_description'] ?? '')),
                'severity_level' => (string)($data['severity_level'] ?? 'medium'),
                'reported_by' => $reporterId,
                'status' => 'pending',
                'image_path' => $imagePath,
                'repair_notes' => isset($data['repair_notes']) ? trim((string)$data['repair_notes']) : null,
            ]);

            $this->recordHistory($report->id, 'created', null, 'pending', $report->repair_notes, [
                'has_image' => $imagePath !== null,
            ], $reporterId);

            if ($imagePath !== null) {
                $this->recordHistory($report->id, 'image_uploaded', 'pending', 'pending', 'Initial damage image uploaded.', [
                    'image_path' => $imagePath,
                ], $reporterId);
            }

            $this->activityLogService->logFromSession([
                'user_id' => $reporterId,
                'user_role' => $authUser['role'] ?? null,
                'action' => 'CREATE_DAMAGE_REPORT',
                'module' => 'damage_reporting',
                'entity_type' => 'damage_report',
                'entity_id' => $report->id,
                'details' => 'Created damage report ' . $report->damage_report_code . '.',
                'meta' => [
                    'severity_level' => $report->severity_level,
                    'source_dispatch_id' => $sourceDispatchId,
                ],
            ], $authUser);

            return $report;
        });
    }

    public function updateStatus(DamageReport $report, array $data, array $authUser): DamageReport
    {
        $userId = (int)($authUser['user_id'] ?? 0);
        if ($userId <= 0) {
            throw ValidationException::withMessages([
                'user' => 'Authenticated user is required.',
            ]);
        }

        $nextStatus = strtolower(trim((string)($data['status'] ?? $report->status)));
        $allowedStatuses = ['pending', 'under_review', 'repairing', 'repaired', 'replaced', 'closed'];
        if (!in_array($nextStatus, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status.',
            ]);
        }

        $previousNotes = (string)($report->repair_notes ?? '');
        $repairNotes = array_key_exists('repair_notes', $data)
            ? trim((string)($data['repair_notes'] ?? ''))
            : $report->repair_notes;

        return DB::transaction(function () use ($report, $nextStatus, $repairNotes, $previousNotes, $data, $userId, $authUser): DamageReport {
            $fromStatus = $report->status;
            $meta = [];

            if ($nextStatus === 'replaced') {
                $replacementItemId = (int)($data['replacement_item_id'] ?? 0);
                $replacementQty = max(1, (int)($data['replacement_quantity'] ?? 1));

                if ($replacementItemId <= 0) {
                    throw ValidationException::withMessages([
                        'replacement_item_id' => 'Replacement item is required when marking as replaced.',
                    ]);
                }

                if ($report->replacement_transaction_id === null) {
                    $replacementItem = Item::query()->find($replacementItemId);
                    if (!$replacementItem) {
                        throw ValidationException::withMessages([
                            'replacement_item_id' => 'Replacement item not found.',
                        ]);
                    }

                    $tx = InventoryTransaction::query()->create([
                        'item_id' => $replacementItemId,
                        'report_id' => null,
                        'room_id' => $report->room_id,
                        'transaction_type' => 'deploy',
                        'quantity' => $replacementQty,
                        'reference_note' => 'Damage replacement for ' . $report->damage_report_code,
                        'performed_by' => $userId,
                    ]);

                    $report->replacement_item_id = $replacementItemId;
                    $report->replacement_quantity = $replacementQty;
                    $report->replacement_transaction_id = $tx->id;
                    $report->replaced_by = $userId;
                    $report->replaced_at = now();

                    $meta['replacement_transaction_id'] = $tx->id;
                    $meta['replacement_item_id'] = $replacementItemId;
                    $meta['replacement_quantity'] = $replacementQty;
                }
            }

            if ($nextStatus === 'closed') {
                $report->closed_at = now();
            }

            $report->status = $nextStatus;
            $report->repair_notes = $repairNotes !== '' ? $repairNotes : null;
            $report->save();

            if ($fromStatus !== $nextStatus) {
                $this->recordHistory(
                    $report->id,
                    'status_changed',
                    $fromStatus,
                    $nextStatus,
                    $report->repair_notes,
                    $meta,
                    $userId
                );
            } elseif ($repairNotes !== $previousNotes) {
                $this->recordHistory(
                    $report->id,
                    'repair_note_updated',
                    $fromStatus,
                    $nextStatus,
                    $report->repair_notes,
                    $meta,
                    $userId
                );
            }

            $this->activityLogService->logFromSession([
                'user_id' => $userId,
                'user_role' => $authUser['role'] ?? null,
                'action' => $nextStatus === 'replaced' ? 'REPLACEMENT_ACTION' : 'UPDATE_DAMAGE_REPORT',
                'module' => $nextStatus === 'replaced' ? 'replacement' : 'damage_reporting',
                'entity_type' => 'damage_report',
                'entity_id' => $report->id,
                'details' => $nextStatus === 'replaced'
                    ? 'Marked damage report ' . $report->damage_report_code . ' as replaced.'
                    : 'Updated damage report ' . $report->damage_report_code . ' to status ' . $nextStatus . '.',
                'meta' => $meta + [
                    'from_status' => $fromStatus,
                    'to_status' => $nextStatus,
                ],
                'dedupe_window_seconds' => 1,
            ], $authUser);

            return $report;
        });
    }

    public function getHistories(DamageReport $report)
    {
        return $report->histories()->with('changedBy:user_id,full_name')->orderByDesc('created_at')->get();
    }

    private function validateDeployedItem(int $itemId, int $roomId, ?int $sourceDispatchId): void
    {
        $itemExists = Item::query()->where('id', $itemId)->exists();
        if (!$itemExists) {
            throw ValidationException::withMessages([
                'item_id' => 'Selected item does not exist.',
            ]);
        }

        $hasDeployment = InventoryTransaction::query()
            ->where('item_id', $itemId)
            ->where('transaction_type', 'deploy')
            ->where(function ($query) use ($roomId): void {
                $query->where('room_id', $roomId)->orWhereNull('room_id');
            })
            ->exists();

        if (!$hasDeployment) {
            throw ValidationException::withMessages([
                'item_id' => 'Only deployed items can be reported as damaged.',
            ]);
        }

        if ($sourceDispatchId !== null && $sourceDispatchId > 0) {
            $dispatch = Dispatch::query()->with('items')->find($sourceDispatchId);
            if (!$dispatch || $dispatch->status !== 'released') {
                throw ValidationException::withMessages([
                    'source_dispatch_id' => 'Source dispatch is invalid or not released.',
                ]);
            }

            $hasItem = $dispatch->items->contains(static function ($row) use ($itemId): bool {
                return (int)$row->item_id === $itemId;
            });

            if (!$hasItem) {
                throw ValidationException::withMessages([
                    'source_dispatch_id' => 'Selected dispatch does not contain the selected item.',
                ]);
            }
        }
    }

    private function preventDuplicateActiveReport(int $itemId, int $roomId, int $departmentId): void
    {
        $duplicate = DamageReport::query()
            ->where('item_id', $itemId)
            ->where('room_id', $roomId)
            ->where('department_id', $departmentId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'item_id' => 'An active damage report already exists for this deployed item in the selected room.',
            ]);
        }
    }

    private function storeImage(UploadedFile $image): string
    {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower((string)$image->getClientOriginalExtension());
        if (!in_array($ext, $allowedExt, true)) {
            throw ValidationException::withMessages([
                'damage_image' => 'Only JPG, PNG, WEBP, or GIF images are allowed.',
            ]);
        }

        $targetDir = public_path('frontend/uploads/damage-reports');
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw ValidationException::withMessages([
                'damage_image' => 'Unable to prepare upload directory.',
            ]);
        }

        $fileName = 'damage-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $image->move($targetDir, $fileName);

        return '/frontend/uploads/damage-reports/' . $fileName;
    }

    private function recordHistory(
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
