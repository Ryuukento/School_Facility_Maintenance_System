<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreventiveMaintenanceHistory;
use App\Models\PreventiveMaintenanceTask;
use App\Services\PersonnelDirectoryService;
use App\Services\PreventiveMaintenanceService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PreventiveMaintenanceController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly PreventiveMaintenanceService $preventiveMaintenanceService,
        // TASK 11 — personnel for the two PM selectors. This is the SAME
        // mechanism Task 65 established for Dispatch: the owning module's own
        // controller reads the shared neutral directory service directly,
        // rather than borrowing another module's endpoint.
        private readonly PersonnelDirectoryService $personnelDirectoryService
    ) {
    }

    /**
     * Same legacy session-bridge helper every other controller in this app
     * uses — role/department/user_id are read from the session, never from
     * the request body.
     */
    private function authUser(Request $request): array
    {
        return (array) $request->session()->get('auth_user', $request->session()->get('user', []));
    }

    private const EAGER_LOADS = [
        'item', 'building', 'floor', 'room', 'department',
        'assignedUser', 'createdByUser', 'updatedByUser',
    ];

    /**
     * GET /api/preventive-maintenance
     *
     * Deliberately NOT scoped by role — the confirmed plan is "Maintenance
     * Staff can view every task, but only edit/complete their own" (view-all
     * was chosen over view-only-assigned). Per-action authorization for the
     * edit/complete restriction lives in update()/complete() below via
     * PreventiveMaintenanceService::canManageTask().
     */
    public function index(Request $request)
    {
        $query = PreventiveMaintenanceTask::query()->with(self::EAGER_LOADS);

        // Defaults to active-only so archived tasks don't clutter the main
        // list; explicit is_active=0 or is_active=all opts into seeing them.
        $activeFilter = $request->query('is_active', '1');
        if ($activeFilter !== 'all') {
            $query->where('is_active', filter_var($activeFilter, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->string('frequency')->toString());
        }

        if ($request->filled('building_id')) {
            $query->where('building_id', (int) $request->query('building_id'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->query('department_id'));
        }

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', (int) $request->query('assigned_user_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('location')) {
            $loc = $request->string('location')->toString();
            $query->where(function ($sub) use ($loc) {
                $sub->where('location_name', 'like', "%{$loc}%")
                    ->orWhereHas('building', fn ($b) => $b->where('name', 'like', "%{$loc}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$loc}%"));
            });
        }

        if ($request->filled('month')) {
            self::scopeByMonth($query, (int) $request->query('month'));
        }

        if ($request->filled('search')) {
            $q = $request->string('search')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('location_name', 'like', "%{$q}%");
            });
        }

        // 'status' (upcoming/due_soon/due/overdue/unscheduled) is computed,
        // not a column, so it cannot be pushed into the SQL WHERE clause the
        // way the filters above are. Filtering happens after the query runs,
        // against the current page only — acceptable for this MVP's expected
        // row counts (a school's PM task list), and avoids duplicating the
        // status-window math in raw SQL just to keep it query-able.
        $statusFilter = $request->filled('status') ? $request->string('status')->toString() : null;

        $query->orderBy('next_due_date');

        if ($statusFilter !== null) {
            // Pull the full filtered-but-unpaginated set, filter by computed
            // status, then paginate manually — status filtering is the one
            // filter that cannot be expressed in SQL (see note above).
            $all = $query->get()->filter(fn (PreventiveMaintenanceTask $t) => $t->status === $statusFilter)->values();
            $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));
            $page = max(1, (int) $request->integer('page', 1));
            $slice = $all->slice(($page - 1) * $perPage, $perPage)->values();

            return $this->ok('Preventive maintenance tasks retrieved', [
                'data' => $slice,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $all->count(),
                'last_page' => (int) max(1, ceil($all->count() / $perPage)),
            ]);
        }

        $tasks = $query->paginate((int) $request->integer('per_page', 20));

        return $this->ok('Preventive maintenance tasks retrieved', $tasks);
    }

    public function summary(Request $request)
    {
        return $this->ok('Summary retrieved', $this->preventiveMaintenanceService->summary());
    }

    /**
     * GET /api/preventive-maintenance/schedule-grid
     *
     * Data source for the annual January-December schedule table that
     * mirrors the school's physical Preventive Maintenance Management Plan
     * manual: one row per equipment/location combination, with a boolean
     * flag per calendar month. Accepts the same search/frequency/category/
     * building/department/assigned_user/location filters as index(), minus
     * pagination/status (the grid always shows every matching active row).
     */
    public function scheduleGrid(Request $request)
    {
        $query = PreventiveMaintenanceTask::query()->with(self::EAGER_LOADS);

        $activeFilter = $request->query('is_active', '1');
        if ($activeFilter !== 'all') {
            $query->where('is_active', filter_var($activeFilter, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->string('frequency')->toString());
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
        if ($request->filled('building_id')) {
            $query->where('building_id', (int) $request->query('building_id'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->query('department_id'));
        }
        if ($request->filled('location')) {
            $loc = $request->string('location')->toString();
            $query->where(function ($sub) use ($loc) {
                $sub->where('location_name', 'like', "%{$loc}%")
                    ->orWhereHas('building', fn ($b) => $b->where('name', 'like', "%{$loc}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$loc}%"));
            });
        }
        if ($request->filled('search')) {
            $q = $request->string('search')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('location_name', 'like', "%{$q}%");
            });
        }

        $tasks = $query->get();

        // Ordered to mirror the manual: by the equipment's position in the
        // fixed category list (config), then alphabetically by location, so
        // "COMPUTERS" rows for Offices/Hallways/INTERNET/etc. stay grouped
        // the same way the physical table groups them.
        $categoryOrder = array_flip(config('preventive_maintenance.categories', []));
        $tasks = $tasks->sort(function (PreventiveMaintenanceTask $a, PreventiveMaintenanceTask $b) use ($categoryOrder) {
            $posA = $categoryOrder[$a->category] ?? PHP_INT_MAX;
            $posB = $categoryOrder[$b->category] ?? PHP_INT_MAX;
            if ($posA !== $posB) {
                return $posA <=> $posB;
            }
            return strcmp((string) $a->location_label, (string) $b->location_label);
        })->values();

        $rows = $tasks->map(function (PreventiveMaintenanceTask $task) {
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[$m] = $task->isScheduledInMonth($m);
            }

            return [
                'id' => $task->id,
                'category' => $task->category,
                'title' => $task->title,
                'frequency' => $task->frequency,
                'frequency_label' => $task->frequency_label,
                'location_label' => $task->location_label,
                'location_name' => $task->location_name,
                'building_id' => $task->building_id,
                'floor_id' => $task->floor_id,
                'room_id' => $task->room_id,
                'item_id' => $task->item_id,
                'item_name' => $task->item_name,
                'department_id' => $task->department_id,
                'department_name' => $task->department_name,
                'assigned_user_id' => $task->assigned_user_id,
                'assigned_user_name' => $task->assigned_user_name,
                'is_active' => (bool) $task->is_active,
                'notes' => $task->notes,
                'last_completed_date' => $task->last_completed_date,
                'status' => $task->status,
                'status_label' => $task->status_label,
                'next_due_date' => $task->next_due_date,
                'next_due_label' => $task->next_due_label,
                'scheduled_months' => $task->scheduled_months,
                'scheduled_months_label' => $task->scheduled_months_label,
                'months' => $months,
            ];
        })->values();

        return $this->ok('Schedule grid retrieved', ['rows' => $rows]);
    }

    /**
     * GET /api/preventive-maintenance/checklist?year=&month=
     *
     * Operational month view: every active task scoped to the requested
     * month (see scopeByMonth()), each annotated with whether it was already
     * completed in that specific year/month (via a matching history row) or
     * is still pending/due/overdue for it.
     */
    public function checklist(Request $request)
    {
        $year = (int) $request->query('year', Carbon::today()->year);
        $month = max(1, min(12, (int) $request->query('month', Carbon::today()->month)));

        $query = PreventiveMaintenanceTask::query()->with(self::EAGER_LOADS)->where('is_active', true);
        self::scopeByMonth($query, $month);

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->string('frequency')->toString());
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', (int) $request->query('assigned_user_id'));
        }
        if ($request->filled('location')) {
            $loc = $request->string('location')->toString();
            $query->where(function ($sub) use ($loc) {
                $sub->where('location_name', 'like', "%{$loc}%")
                    ->orWhereHas('building', fn ($b) => $b->where('name', 'like', "%{$loc}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('name', 'like', "%{$loc}%"));
            });
        }
        if ($request->filled('search')) {
            $q = $request->string('search')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('location_name', 'like', "%{$q}%");
            });
        }

        $tasks = $query->orderBy('category')->get();
        $taskIds = $tasks->pluck('id');

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();
        $today = Carbon::today();
        $isPastPeriod = $periodEnd->lt($today);
        $isCurrentPeriod = $today->between($periodStart, $periodEnd);

        $completions = PreventiveMaintenanceHistory::query()
            ->whereIn('preventive_maintenance_task_id', $taskIds)
            ->whereBetween('completed_date', [$periodStart, $periodEnd])
            ->orderByDesc('completed_date')
            ->with(['performedByUser'])
            ->get()
            ->groupBy('preventive_maintenance_task_id');

        $statusFilter = $request->filled('status') ? $request->string('status')->toString() : null;

        $items = $tasks->map(function (PreventiveMaintenanceTask $task) use ($completions, $isPastPeriod, $isCurrentPeriod) {
            $completion = $completions->get($task->id)?->first();

            if ($completion) {
                $itemStatus = 'completed';
            } elseif ($isPastPeriod) {
                $itemStatus = 'overdue';
            } elseif ($isCurrentPeriod) {
                $itemStatus = $task->status === 'unscheduled' ? 'due' : $task->status;
            } else {
                $itemStatus = 'pending';
            }

            return [
                'id' => $task->id,
                'category' => $task->category,
                'title' => $task->title,
                'frequency' => $task->frequency,
                'frequency_label' => $task->frequency_label,
                'location_label' => $task->location_label,
                'department_name' => $task->department_name,
                'assigned_user_name' => $task->assigned_user_name,
                'assigned_user_id' => $task->assigned_user_id,
                'item_status' => $itemStatus,
                'next_due_label' => $task->next_due_label,
                'completed_date' => $completion?->completed_date,
                'performed_by_name' => $completion?->performedByUser?->full_name,
                'notes' => $completion?->notes,
                'findings' => $completion?->findings,
                'action_taken' => $completion?->action_taken,
            ];
        });

        if ($statusFilter !== null) {
            $items = $items->filter(fn ($i) => $i['item_status'] === $statusFilter)->values();
        }

        return $this->ok('Checklist retrieved', [
            'year' => $year,
            'month' => $month,
            'items' => $items->values(),
        ]);
    }

    /**
     * GET /api/preventive-maintenance/support/options
     *
     * Fixed category/frequency lists for the Create/Edit form dropdowns —
     * served from config so the frontend never hardcodes them either.
     */
    public function options(Request $request)
    {
        return $this->ok('Options retrieved', [
            'categories' => config('preventive_maintenance.categories', []),
            'frequencies' => config('preventive_maintenance.frequencies', []),
            'due_soon_days' => (int) config('preventive_maintenance.due_soon_days', 14),
        ]);
    }

    /**
     * GET /api/preventive-maintenance/support/personnel
     *
     * TASK 11 — source for BOTH Preventive Maintenance personnel selectors:
     * "Assignee" on the create/edit form and "Performed by" on the complete
     * modal.
     *
     * Until now the page filled those two dropdowns from
     * GET /api/repairs/support/technicians — a Repair Request endpoint. That
     * is the same cross-module coupling Task 42 recorded as a Hard Stop and
     * Task 65 removed for Dispatch: nothing about "which active maintenance
     * user can be assigned work" belongs to the Repair domain, and the Repair
     * module is scheduled for retirement, so deleting it would have silently
     * emptied two PM dropdowns with no failing test.
     *
     * The replacement reuses the established mechanism rather than inventing
     * a second one. PersonnelDirectoryService stays the single source of
     * truth for the personnel query; no role normalization, department
     * filtering, or "active user" logic is re-implemented here.
     *
     * Dispatch's GET support/release-personnel was evaluated first and is NOT
     * reusable as-is: it is role-gated to maintenance_admin/super_admin (PM's
     * Maintenance Staff complete their own tasks and would get a 403), it
     * narrows the role set to maintenance_staff only, and it scopes results to
     * the caller's own department. Borrowing it would have changed both the
     * access boundary and the returned list.
     *
     * BEHAVIOUR IS DELIBERATELY UNCHANGED from the endpoint it replaces:
     * same default role audience (maintenance_admin + maintenance_staff via
     * PersonnelDirectoryService's default), no department scoping, same `q` /
     * `per_page` inputs, and the same `data.users` JSON shape the page's
     * SearchableSelect already parses. Like every other
     * preventive-maintenance/support/* route it carries no EnsureRole, which
     * matches the (also un-gated) Repair endpoint it replaces — this task
     * repoints a data source, it does not re-open the RBAC question.
     */
    public function supportPersonnel(Request $request)
    {
        return $this->ok('Personnel retrieved', [
            'users' => $this->personnelDirectoryService->searchAssignablePersonnel(
                trim((string) $request->query('q', '')),
                (int) $request->query('per_page', 20)
            ),
        ]);
    }

    private function validationRules(bool $isUpdate = false): array
    {
        $req = $isUpdate ? 'sometimes' : 'required';

        return [
            'category' => [$req, 'string', 'max:100'],
            'title' => [$req, 'string', 'max:255'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'frequency' => [$req, 'in:monthly,quarterly,semi_annually,annually'],
            'scheduled_months' => ['nullable', 'array'],
            'scheduled_months.*' => ['integer', 'between:1,12'],
            'last_completed_date' => ['nullable', 'date'],
            'next_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Restricts a PreventiveMaintenanceTask query to tasks that apply to the
     * given calendar month (1-12): either the manual-derived scheduled_months
     * array contains it, or (for tasks not modeled on the manual's fixed
     * months) next_due_date falls in that month. Shared by index()'s month
     * filter, scheduleGrid(), and checklist() so the "does this task belong
     * to month X" rule is defined in exactly one place.
     */
    private static function scopeByMonth($query, int $month): void
    {
        $query->where(function ($sub) use ($month) {
            $sub->whereJsonContains('scheduled_months', $month)
                ->orWhere(function ($sub2) use ($month) {
                    $sub2->where(function ($sub3) {
                        $sub3->whereNull('scheduled_months')->orWhereJsonLength('scheduled_months', 0);
                    })->whereNotNull('next_due_date')->whereMonth('next_due_date', $month);
                });
        });
    }

    /**
     * POST /api/preventive-maintenance
     *
     * Create is Administrator/Head Maintenance only (route middleware) — a
     * Maintenance Staff member never creates a PM plan under the confirmed
     * RBAC, only completes/edits ones assigned to them.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());

        try {
            $task = $this->preventiveMaintenanceService->createTask(
                $validated,
                (int) $request->session()->get('user_id')
            );

            return $this->ok('Preventive maintenance task created', ['task' => $task->load(self::EAGER_LOADS)], 201);
        } catch (ValidationException $e) {
            return $this->fail(collect($e->errors())->flatten()->first() ?: 'Invalid data.', 400);
        } catch (\Throwable $e) {
            return $this->fail('Failed to create preventive maintenance task: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, PreventiveMaintenanceTask $preventiveMaintenanceTask)
    {
        return $this->ok('Preventive maintenance task retrieved', [
            'task' => $preventiveMaintenanceTask->load(self::EAGER_LOADS),
        ]);
    }

    public function update(Request $request, PreventiveMaintenanceTask $preventiveMaintenanceTask)
    {
        $authUser = $this->authUser($request);
        if (!$this->preventiveMaintenanceService->canManageTask($authUser, $preventiveMaintenanceTask)) {
            return $this->fail('You are not allowed to edit this task.', 403);
        }

        $validated = $request->validate($this->validationRules(true));

        try {
            $task = $this->preventiveMaintenanceService->updateTask(
                $preventiveMaintenanceTask,
                $validated,
                (int) $request->session()->get('user_id')
            );

            return $this->ok('Preventive maintenance task updated', ['task' => $task->load(self::EAGER_LOADS)]);
        } catch (ValidationException $e) {
            return $this->fail(collect($e->errors())->flatten()->first() ?: 'Invalid data.', 400);
        } catch (\Throwable $e) {
            return $this->fail('Failed to update preventive maintenance task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/preventive-maintenance/{task}/archive — soft-hide, not
     * delete. Route middleware already restricts this to
     * super_admin/maintenance_admin (Maintenance Staff cannot archive).
     */
    public function archive(Request $request, PreventiveMaintenanceTask $preventiveMaintenanceTask)
    {
        $task = $this->preventiveMaintenanceService->setActive(
            $preventiveMaintenanceTask,
            false,
            (int) $request->session()->get('user_id')
        );

        return $this->ok('Preventive maintenance task archived', ['task' => $task->load(self::EAGER_LOADS)]);
    }

    public function activate(Request $request, PreventiveMaintenanceTask $preventiveMaintenanceTask)
    {
        $task = $this->preventiveMaintenanceService->setActive(
            $preventiveMaintenanceTask,
            true,
            (int) $request->session()->get('user_id')
        );

        return $this->ok('Preventive maintenance task reactivated', ['task' => $task->load(self::EAGER_LOADS)]);
    }

    /**
     * POST /api/preventive-maintenance/{task}/complete
     */
    public function complete(Request $request, PreventiveMaintenanceTask $preventiveMaintenanceTask)
    {
        $authUser = $this->authUser($request);
        if (!$this->preventiveMaintenanceService->canManageTask($authUser, $preventiveMaintenanceTask)) {
            return $this->fail('You are not allowed to complete this task.', 403);
        }

        $validated = $request->validate([
            'completed_date' => ['required', 'date'],
            'performed_by' => ['nullable', 'integer', 'exists:users,user_id'],
            'notes' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'action_taken' => ['nullable', 'string'],
            'completion_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        try {
            $result = $this->preventiveMaintenanceService->completeTask(
                $preventiveMaintenanceTask,
                $validated,
                $request->file('completion_proof'),
                (int) $request->session()->get('user_id')
            );

            return $this->ok('Preventive maintenance task marked completed', [
                'task' => $result['task']->load(self::EAGER_LOADS),
                'history' => $result['history'],
            ]);
        } catch (ValidationException $e) {
            return $this->fail(collect($e->errors())->flatten()->first() ?: 'Invalid data.', 400);
        } catch (\Throwable $e) {
            return $this->fail('Failed to complete preventive maintenance task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/preventive-maintenance/{task}/history — append-only, newest
     * first (see PreventiveMaintenanceTask::history()).
     */
    public function history(Request $request, PreventiveMaintenanceTask $preventiveMaintenanceTask)
    {
        return $this->ok('History retrieved', [
            'history' => $preventiveMaintenanceTask->history()
                ->with(['performedByUser', 'recordedByUser'])
                ->paginate((int) $request->integer('per_page', 20)),
        ]);
    }
}
