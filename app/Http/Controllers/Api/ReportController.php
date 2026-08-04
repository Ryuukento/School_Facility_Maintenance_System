<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Services\NeedChangeService;
use App\Services\RoleNormalizerService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly NeedChangeService $needChangeService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $authUser    = $request->session()->get('auth_user', []);
        $userId      = (int)($authUser['user_id'] ?? 0);
        $role        = $this->normalizeRole((string)($authUser['role'] ?? ''));

        $perPage     = max(1, min(200, (int)$request->query('per_page', 20)));
        $page        = max(1, (int)$request->query('page', 1));
        $statusGroup = strtolower(trim((string)$request->query('status_group', '')));
        $deptId      = (int)$request->query('department_id', 0);
        $dateFrom    = $request->query('date_from');
        $dateTo      = $request->query('date_to');

        $query = MaintenanceReport::query()
            ->select([
                'maintenance_reports.report_id',
                'maintenance_reports.title',
                'maintenance_reports.priority',
                'maintenance_reports.status',
                'maintenance_reports.location',
                'maintenance_reports.created_at',
                'maintenance_reports.updated_at',
                'maintenance_reports.created_by',
                'maintenance_reports.assigned_to',
                'maintenance_reports.department_id',
                'maintenance_reports.due_date',
                'maintenance_reports.need_change_item_id',
                'maintenance_reports.need_change_quantity',
                'maintenance_reports.need_change_status',
                'maintenance_reports.need_change_approved_by',
                'maintenance_reports.need_change_approved_at',
                'maintenance_reports.need_change_deducted_at',
                DB::raw('assignee.full_name AS assigned_name'),
                DB::raw('creator.full_name  AS creator_name'),
                DB::raw('dept.name          AS department_name'),
            ])
            ->leftJoin('users AS assignee',       'maintenance_reports.assigned_to',  '=', 'assignee.user_id')
            ->leftJoin('users AS creator',        'maintenance_reports.created_by',   '=', 'creator.user_id')
            ->leftJoin('departments AS dept',     'maintenance_reports.department_id','=', 'dept.department_id');

        // ── status_group: user-centric views override role scoping ────────
        if ($statusGroup === 'assigned_to_me') {
            $query->where('maintenance_reports.assigned_to', $userId);

        } elseif ($statusGroup === 'overdue') {
            $query->where('maintenance_reports.assigned_to', $userId)
                  ->whereNotNull('maintenance_reports.due_date')
                  ->whereDate('maintenance_reports.due_date', '<', today())
                  ->whereNotIn('maintenance_reports.status', ['completed', 'closed']);

        } elseif ($statusGroup === 'due_soon') {
            $query->where('maintenance_reports.assigned_to', $userId)
                  ->whereNotNull('maintenance_reports.due_date')
                  ->whereBetween('maintenance_reports.due_date', [today(), today()->addDays(7)])
                  ->whereNotIn('maintenance_reports.status', ['completed', 'closed']);

        } elseif ($statusGroup === 'recent_assignments') {
            $query->where('maintenance_reports.assigned_to', $userId)
                  ->where('maintenance_reports.status', 'assigned')
                  ->whereDate('maintenance_reports.updated_at', '>=', today()->subDays(7));

        } elseif (!in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true)) {
            // Non-maintenance roles: own reports only (no status_group set)
            $query->where(function ($b) use ($userId): void {
                $b->where('maintenance_reports.created_by', $userId)
                  ->orWhere('maintenance_reports.assigned_to', $userId);
            });
        }
        // super_admin / maintenance_admin / maintenance_staff with no status_group → see all

        // ── column filters ─────────────────────────────────────────────────
        if ($deptId > 0) {
            $query->where('maintenance_reports.department_id', $deptId);
        }

        if ($request->filled('status')) {
            $query->where('maintenance_reports.status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('maintenance_reports.priority', $request->string('priority')->toString());
        }

        if ($request->filled('search')) {
            $kw = '%' . $request->string('search')->toString() . '%';
            $query->where(function ($b) use ($kw): void {
                $b->where('maintenance_reports.title',        'like', $kw)
                  ->orWhere('maintenance_reports.description', 'like', $kw)
                  ->orWhere('maintenance_reports.location',   'like', $kw);
            });
        }

        if ($dateFrom) {
            $query->whereDate('maintenance_reports.created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('maintenance_reports.created_at', '<=', $dateTo);
        }

        // ── flat response — frontend reads data.reports[] ─────────────────
        $total   = (clone $query)->count();
        $reports = $query
            ->orderByDesc('maintenance_reports.created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->ok('Reports retrieved', [
            'reports'  => $reports,
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $request->session()->get('auth_user', []);
        $submitterName = (string)($authUser['full_name'] ?? 'A staff member');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'status' => ['nullable', 'string', 'in:submitted,in_progress,completed,closed,cancelled'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,user_id'],
            // TASK 19 — Target Maintenance Department: the reporter now picks
            // which department is responsible for fixing the issue, so this
            // must resolve to an active department, never a client-trusted
            // free value. Stays nullable (not required) so the asset/damage
            // path below, which still derives department_id from the
            // reporter when none is supplied, is unaffected.
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'department_id')->where('status', 'active')],
            'due_date' => ['nullable', 'date'],
        ]);

        $report = MaintenanceReport::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'location' => $validated['location'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'status' => $validated['status'] ?? 'submitted',
            'created_by' => (int)($authUser['user_id'] ?? 0),
            'assigned_to' => $validated['assigned_to'] ?? null,
            'department_id' => $validated['department_id'] ?? ($authUser['department_id'] ?? null),
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $recipientRoles = ['super_admin', 'admin', 'maintenance_admin', 'admin_maintenance', 'department_admin'];
        $recipients = DB::table('users')
            ->select('user_id')
            ->where('status', 'active')
            ->whereIn(DB::raw('LOWER(role)'), $recipientRoles)
            ->pluck('user_id')
            ->all();

        if (!empty($recipients)) {
            $notificationRows = [];
            $hasReportIdColumn = Schema::hasColumn('notifications', 'report_id');
            foreach ($recipients as $recipientId) {
                $row = [
                    'user_id' => (int)$recipientId,
                    'title' => 'New Maintenance Report Submitted',
                    'message' => $submitterName . ' submitted a new report: ' . $validated['title'] . ' (Report #' . $report->report_id . ')',
                    'is_read' => 0,
                    'created_at' => now(),
                ];

                if ($hasReportIdColumn) {
                    $row['report_id'] = $report->report_id;
                }

                $notificationRows[] = $row;
            }

            DB::table('notifications')->insert($notificationRows);
        }

        $this->sendSuperAdminEmailForNewReport($report, $validated, $submitterName);

        return $this->ok('Report created successfully', ['report_id' => $report->report_id], 201);
    }

    public function recent(Request $request): JsonResponse
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId   = (int)($authUser['user_id'] ?? 0);
        $role     = $this->normalizeRole((string)($authUser['role'] ?? ''));
        $limit    = max(1, min(100, (int)$request->query('limit', 10)));

        $query = MaintenanceReport::query()
            ->select([
                'maintenance_reports.report_id',
                'maintenance_reports.title',
                'maintenance_reports.description',
                'maintenance_reports.priority',
                'maintenance_reports.status',
                'maintenance_reports.location',
                'maintenance_reports.created_at',
                'maintenance_reports.created_by',
                'maintenance_reports.assigned_to',
                'maintenance_reports.department_id',
                DB::raw('assignee.full_name AS assigned_name'),
                DB::raw('creator.full_name  AS creator_name'),
            ])
            ->leftJoin('users AS assignee', 'maintenance_reports.assigned_to', '=', 'assignee.user_id')
            ->leftJoin('users AS creator',  'maintenance_reports.created_by',  '=', 'creator.user_id')
            ->whereDate('maintenance_reports.created_at', today());

        // Role scoping — mirrors legacy getRecentReports()
        if (in_array($role, ['super_admin', 'maintenance_staff'], true)) {
            // no scope — sees all today's reports
        } elseif ($role === 'maintenance_admin') {
            $deptId = DB::table('users')->where('user_id', $userId)->value('department_id');
            if ($deptId) {
                $query->where('maintenance_reports.department_id', $deptId);
            }
        } else {
            $query->where(function ($b) use ($userId): void {
                $b->where('maintenance_reports.created_by', $userId)
                  ->orWhere('maintenance_reports.assigned_to', $userId);
            });
        }

        $reports = $query
            ->orderByDesc('maintenance_reports.created_at')
            ->limit($limit)
            ->get();

        return $this->ok('Recent reports retrieved', ['reports' => $reports]);
    }

    public function show(Request $request, MaintenanceReport $report): JsonResponse
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId   = (int)($authUser['user_id'] ?? 0);
        $role     = $this->normalizeRole((string)($authUser['role'] ?? ''));

        // Auth check on the bound model (no extra query)
        if (!in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true)
            && (int)$report->created_by !== $userId
            && (int)$report->assigned_to !== $userId) {
            return $this->fail('Forbidden', 403);
        }

        // Flat join query — returns all fields the frontend reads directly
        $data = DB::table('maintenance_reports AS r')
            ->select([
                'r.report_id',
                'r.title',
                'r.description',
                'r.priority',
                'r.status',
                'r.location',
                'r.created_at',
                'r.updated_at',
                'r.created_by',
                'r.assigned_to',
                'r.department_id',
                'r.due_date',
                'r.completed_date',
                'r.need_change_item_id',
                'r.need_change_quantity',
                'r.need_change_status',
                'r.need_change_approved_by',
                'r.need_change_approved_at',
                'r.need_change_deducted_at',
                DB::raw('creator.full_name  AS creator_name'),
                DB::raw('creator.email      AS creator_email'),
                DB::raw('assignee.full_name AS assigned_name'),
                DB::raw('assignee.email     AS assigned_email'),
                DB::raw('dept.name          AS department_name'),
                DB::raw('nc_item.name       AS need_change_item_name'),
                DB::raw('nc_item.quantity   AS need_change_item_quantity'),
            ])
            ->leftJoin('users AS creator',    'r.created_by',          '=', 'creator.user_id')
            ->leftJoin('users AS assignee',   'r.assigned_to',         '=', 'assignee.user_id')
            ->leftJoin('departments AS dept', 'r.department_id',       '=', 'dept.department_id')
            ->leftJoin('items AS nc_item',    'r.need_change_item_id', '=', 'nc_item.id')
            ->where('r.report_id', (int)$report->report_id)
            ->first();

        if (!$data) {
            return $this->fail('Report not found', 404);
        }

        return $this->ok('Report retrieved', ['report' => $data]);
    }

    public function update(Request $request, MaintenanceReport $report): JsonResponse
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId   = (int)($authUser['user_id'] ?? 0);
        $role     = $this->normalizeRole((string)($authUser['role'] ?? ''));

        $previousStatus = (string) $report->status;
        $changes = [];

        // CASE D/E — need-change approval/rejection (super_admin only)
            if ($request->boolean('approve_need_change')) {
            if ($role !== 'super_admin') {
                return $this->fail('Only an Administrator can approve Need Change requests', 403);
            }

            try {
                $this->needChangeService->approve($report, $userId);
            } catch (ValidationException $e) {
                return $this->fail(collect($e->errors())->flatten()->first() ?: 'Unable to approve Need Change request', 422);
            }

            return $this->ok('Need Change approved and inventory deducted successfully');
        } elseif ($request->boolean('reject_need_change')) {
            if ($role !== 'super_admin') {
                return $this->fail('Only an Administrator can reject Need Change requests', 403);
            }
            $changes['need_change_status'] = 'rejected';
        }

        // CASE B — status change
        if ($request->has('status')) {
            $newStatus     = strtolower(trim((string)$request->input('status', '')));
            $validStatuses = ['submitted', 'assigned', 'in_progress', 'completed', 'closed', 'cancelled'];
            if (!in_array($newStatus, $validStatuses, true)) {
                return $this->fail('Invalid status value', 422);
            }
            $staffAllowed = ['in_progress', 'completed'];
            $adminAllowed = ['submitted', 'assigned', 'in_progress', 'completed', 'closed', 'cancelled'];
            if ($role === 'super_admin') {
                // no restriction
            } elseif (in_array($role, ['maintenance_admin', 'department_admin'], true)) {
                if (!in_array($newStatus, $adminAllowed, true)) {
                    return $this->fail('You cannot set that status', 403);
                }
            } elseif ($role === 'maintenance_staff') {
                if (!in_array($newStatus, $staffAllowed, true)) {
                    return $this->fail('Maintenance Staff can only set status to In Progress or Completed', 403);
                }
            } else {
                return $this->fail('You are not allowed to change report status', 403);
            }
            $changes['status'] = $newStatus;
            if ($newStatus === 'assigned' && $request->has('assigned_to')) {
                $at = $request->input('assigned_to');
                $changes['assigned_to'] = $at ? (int) $at : null;
            }
            if (in_array($newStatus, ['completed', 'closed'], true)) {
                $changes['completed_date'] = $request->input('completed_date') ?? now()->toDateString();
            }
            if ($request->has('due_date')) {
                $changes['due_date'] = $request->input('due_date') ?: null;
            }
        }

        // CASE C — completion proof file upload
        if ($request->hasFile('completion_proof_image')) {
            $file         = $request->file('completion_proof_image');
            $allowedMimes = [
                'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
                'image/png'  => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
            ];
            $mime = $file->getMimeType();
            if (!isset($allowedMimes[$mime])) {
                return $this->fail('Only JPG, PNG, WEBP, or GIF images are allowed', 422);
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                return $this->fail('Completion proof image must be 5MB or smaller', 422);
            }
            $uploadDir = public_path('frontend/uploads/completion-proofs');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $ext      = $allowedMimes[$mime];
            $fileName = 'report-' . (int) $report->report_id
                      . '-' . date('YmdHis')
                      . '-' . bin2hex(random_bytes(4))
                      . '.' . $ext;
            $file->move($uploadDir, $fileName);
            $changes['completion_proof_image'] = '/School_Facility_Maintenance_System/frontend/uploads/completion-proofs/' . $fileName;
        }

        // CASE A — basic field edit
        $caseAFields = ['title', 'description', 'location', 'priority',
                        'department_id', 'need_change_item_id', 'need_change_quantity'];
        $hasCaseA    = collect($caseAFields)->contains(fn ($f) => $request->has($f));
        if ($hasCaseA) {
            $isOwner    = (int) $report->created_by === $userId;
            $canEditAny = in_array($role, ['super_admin', 'maintenance_admin'], true);
            if (!$isOwner && !$canEditAny) {
                return $this->fail('You can only edit your own reports', 403);
            }
            foreach ($caseAFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    $changes[$field] = is_string($value) ? trim($value) : $value;
                }
            }
        }

        if (empty($changes)) {
            return $this->fail('No valid fields to update', 422);
        }

        $report->update($changes);

        $newStatus = $changes['status'] ?? null;
        if (in_array($newStatus, ['completed', 'closed'], true) && $newStatus !== $previousStatus) {
            $this->notifyReportOwnerOfCompletion($report, $newStatus);
        }

        return $this->ok('Report updated successfully');
    }

    public function destroy(Request $request, MaintenanceReport $report): JsonResponse
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId   = (int)($authUser['user_id'] ?? 0);
        $role     = $this->normalizeRole((string)($authUser['role'] ?? ''));

        if ($role !== 'super_admin' && (int)$report->created_by !== $userId) {
            return $this->fail('You can only delete your own reports', 403);
        }

        $report->delete();
        return $this->ok('Report deleted successfully');
    }

    private function normalizeRole(string $role): string
    {
        return RoleNormalizerService::normalizeWithStaffDefault($role);
    }

    /**
     * Closes the "communication loop" gap identified in SYSTEM_FLOW_REVIEW.md §7:
     * the report owner previously received no notification when their report
     * reached a terminal state. Reuses the same raw notifications-table insert
     * pattern already established in store() above (schema-flexible via
     * Schema::hasColumn, since the notifications migration doesn't declare a
     * report_id column but some environments have one added).
     */
    private function notifyReportOwnerOfCompletion(MaintenanceReport $report, string $finalStatus): void
    {
        $ownerId = (int) $report->created_by;
        if ($ownerId <= 0) {
            return;
        }

        $statusLabel     = $finalStatus === 'closed' ? 'Closed' : 'Completed';
        $assignedName    = optional($report->assignee)->full_name ?: 'Unassigned';
        $completionDate  = $report->completed_date
            ? $report->completed_date->toDateString()
            : now()->toDateString();

        $row = [
            'user_id' => $ownerId,
            'title' => 'Report #' . $report->report_id . ' Marked as ' . $statusLabel,
            'message' => sprintf(
                'Your maintenance report #%d (%s) at %s has been marked as %s. Assigned Staff: %s. Completion Date: %s.',
                $report->report_id,
                $report->title,
                $report->location ?: 'N/A',
                $statusLabel,
                $assignedName,
                $completionDate
            ),
            'is_read' => 0,
            'created_at' => now(),
        ];

        if (Schema::hasColumn('notifications', 'report_id')) {
            $row['report_id'] = $report->report_id;
        }

        DB::table('notifications')->insert($row);
    }

    private function sendSuperAdminEmailForNewReport(MaintenanceReport $report, array $validated, string $submitterName): void
    {
        try {
            $emailServicePath = base_path('public/backend/services/EmailService.php');
            if (!file_exists($emailServicePath)) {
                return;
            }

            require_once $emailServicePath;
            if (!class_exists('EmailService')) {
                return;
            }

            $superAdminRecipients = DB::table('users')
                ->select(['user_id', 'email', 'full_name'])
                ->where(function ($query): void {
                    $query->whereRaw("LOWER(TRIM(role)) = 'super_admin'")
                        ->orWhereRaw("LOWER(TRIM(role)) = 'super admin'");
                })
                ->where(function ($query): void {
                    $query->whereNull('status')
                        ->orWhereRaw("LOWER(TRIM(status)) = 'active'");
                })
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->get()
                ->map(static function ($row): array {
                    return [
                        'user_id' => (int)($row->user_id ?? 0),
                        'email' => strtolower(trim((string)($row->email ?? ''))),
                        'full_name' => (string)($row->full_name ?? 'Administrator'),
                    ];
                })
                ->filter(static function (array $row): bool {
                    return filter_var($row['email'], FILTER_VALIDATE_EMAIL) !== false;
                })
                ->values()
                ->all();

            $payload = [
                'report_id' => (int)$report->report_id,
                'title' => (string)($validated['title'] ?? ''),
                'description' => (string)($validated['description'] ?? ''),
                'location' => (string)($validated['location'] ?? ''),
                'priority' => (string)($validated['priority'] ?? 'medium'),
                'submitted_by' => $submitterName,
                'creator_name' => $submitterName,
            ];

            if (!empty($superAdminRecipients)) {
                EmailService::sendNewReportNotification($payload, $superAdminRecipients);
            }
        } catch (\Throwable $e) {
            Log::warning('Non-fatal Administrator email notification error', [
                'report_id' => (int)$report->report_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
