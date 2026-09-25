<?php

namespace App\Services;

use App\Models\PreventiveMaintenanceHistory;
use App\Models\PreventiveMaintenanceTask;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreventiveMaintenanceService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly NotificationService $notificationService
    ) {
    }

    /**
     * RBAC per the confirmed plan: Maintenance Staff may VIEW every task
     * (index is never scoped), but may only EDIT or COMPLETE a task whose
     * assigned_user_id is their own user_id. super_admin / maintenance_admin
     * may manage any task. Mirrors DispatchAuthorizationService's role of
     * being the single place this identity check lives.
     */
    public function canManageTask(array $authUser, PreventiveMaintenanceTask $task): bool
    {
        $role = (string) ($authUser['role'] ?? '');
        if (in_array($role, ['super_admin', 'maintenance_admin'], true)) {
            return true;
        }

        if ($role === 'maintenance_staff') {
            return (int) $task->assigned_user_id === (int) ($authUser['user_id'] ?? 0);
        }

        return false;
    }

    /**
     * Computes the next due date from a completion date + frequency.
     * Uses addMonthsNoOverflow so a Jan 31 completion on a monthly cadence
     * lands on Feb 28/29 instead of overflowing into March — the safest
     * generic rule for all four frequencies, including the annually case
     * (12 months), which also correctly keeps Feb 29 -> Feb 28 on non-leap
     * years instead of throwing/overflowing.
     */
    public function calculateNextDueDate(string $frequency, Carbon|string $from): Carbon
    {
        $base = $from instanceof Carbon ? $from->copy() : Carbon::parse($from);

        $months = match ($frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
            default => throw ValidationException::withMessages([
                'frequency' => 'Unknown frequency: ' . $frequency,
            ]),
        };

        return $base->addMonthsNoOverflow($months);
    }

    public function summary(): array
    {
        $active = PreventiveMaintenanceTask::query()->where('is_active', true);

        $tasks = (clone $active)->get(['id', 'next_due_date']);
        $counts = ['upcoming' => 0, 'due_soon' => 0, 'due' => 0, 'overdue' => 0, 'unscheduled' => 0];
        foreach ($tasks as $task) {
            $counts[$task->status] = ($counts[$task->status] ?? 0) + 1;
        }

        $today = Carbon::today();
        $dueThisMonth = (clone $active)
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->count();
        $dueThisWeek = (clone $active)
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()])
            ->count();

        $completedThisMonth = PreventiveMaintenanceHistory::query()
            ->whereBetween('completed_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();

        return [
            'total_items' => (clone $active)->count(),
            'active_plans' => (clone $active)->count(),
            'due_this_month' => $dueThisMonth,
            'due_this_week' => $dueThisWeek,
            'due_soon' => $counts['due_soon'],
            'due' => $counts['due'],
            'overdue' => $counts['overdue'],
            'upcoming' => $counts['upcoming'],
            'unscheduled' => $counts['unscheduled'],
            'completed_this_month' => $completedThisMonth,
        ];
    }

    /**
     * Finds the next calendar month (from the manual-derived scheduled_months
     * list) on/after $from, wrapping into next year if every marked month
     * this year has already passed. $inclusive=true allows $from's own month
     * to count (used when a task has never been completed yet — this month's
     * mark is still upcoming); $inclusive=false requires strictly a later
     * month (used right after logging a completion, so the same month isn't
     * immediately re-selected as its own next occurrence).
     *
     * Day is fixed at the 1st of the resulting month — the manual's table
     * only carries month-level resolution, never a specific day.
     */
    public function calculateNextDueDateFromMonths(array $months, Carbon $from, bool $inclusive = true): Carbon
    {
        $sorted = collect($months)->map(fn ($m) => (int) $m)->filter(fn ($m) => $m >= 1 && $m <= 12)->unique()->sort()->values();
        if ($sorted->isEmpty()) {
            throw ValidationException::withMessages([
                'scheduled_months' => 'At least one valid month (1-12) is required.',
            ]);
        }

        $currentMonth = (int) $from->month;
        $next = $sorted->first(fn ($m) => $inclusive ? $m >= $currentMonth : $m > $currentMonth);
        $year = (int) $from->year;
        if ($next === null) {
            $next = $sorted->first();
            $year++;
        }

        return Carbon::create($year, $next, 1)->startOfDay();
    }

    public function createTask(array $data, ?int $actorUserId = null): PreventiveMaintenanceTask
    {
        return DB::transaction(function () use ($data, $actorUserId): PreventiveMaintenanceTask {
            $lastCompleted = $data['last_completed_date'] ?? null;
            $nextDue = $data['next_due_date'] ?? null;
            $scheduledMonths = !empty($data['scheduled_months']) ? array_values($data['scheduled_months']) : null;

            // If a last-completed date is supplied but no explicit next-due
            // date, derive it from the manual's fixed scheduled_months when
            // present (the school's actual X-mark pattern), otherwise from
            // the generic frequency interval — so the record starts
            // consistent rather than requiring the caller to compute it
            // client-side.
            if ($nextDue === null && $scheduledMonths !== null) {
                $base = $lastCompleted !== null ? Carbon::parse($lastCompleted) : Carbon::today();
                $nextDue = $this->calculateNextDueDateFromMonths($scheduledMonths, $base, $lastCompleted === null)->toDateString();
            } elseif ($nextDue === null && $lastCompleted !== null) {
                $nextDue = $this->calculateNextDueDate($data['frequency'], $lastCompleted)->toDateString();
            }

            $task = PreventiveMaintenanceTask::query()->create([
                'category' => $data['category'],
                'title' => $data['title'],
                'item_id' => $data['item_id'] ?? null,
                'building_id' => $data['building_id'] ?? null,
                'floor_id' => $data['floor_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'location_name' => $data['location_name'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'assigned_user_id' => $data['assigned_user_id'] ?? null,
                'frequency' => $data['frequency'],
                'scheduled_months' => $scheduledMonths,
                'last_completed_date' => $lastCompleted,
                'next_due_date' => $nextDue,
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorUserId,
                'updated_by' => $actorUserId,
            ]);

            if ($actorUserId) {
                $this->activityLogService->logFromSession([
                    'user_id' => $actorUserId,
                    'action' => 'CREATE_PREVENTIVE_MAINTENANCE_TASK',
                    'module' => 'preventive_maintenance',
                    'entity_type' => 'preventive_maintenance_task',
                    'entity_id' => $task->id,
                    'details' => 'Created preventive maintenance task "' . $task->title . '".',
                    'meta' => ['category' => $task->category, 'frequency' => $task->frequency],
                ]);
            }

            $this->notifyAssignment($task, null, $actorUserId);

            return $task->fresh();
        });
    }

    public function updateTask(PreventiveMaintenanceTask $task, array $data, ?int $actorUserId = null): PreventiveMaintenanceTask
    {
        return DB::transaction(function () use ($task, $data, $actorUserId): PreventiveMaintenanceTask {
            $locked = PreventiveMaintenanceTask::query()->whereKey($task->id)->lockForUpdate()->first();
            if (!$locked) {
                throw ValidationException::withMessages(['id' => 'Preventive maintenance task not found.']);
            }

            $previousAssignee = $locked->assigned_user_id !== null ? (int) $locked->assigned_user_id : null;

            // 'category'/'title'/'frequency' are simple overwrite-if-present
            // fields. The location/assignment fields below are nullable by
            // design (e.g. "unassign this task"), so they use
            // array_key_exists rather than isset/?? — a caller explicitly
            // sending null must be able to clear the field, not have it
            // silently ignored.
            foreach (['category', 'title', 'frequency'] as $simple) {
                if (array_key_exists($simple, $data) && $data[$simple] !== null) {
                    $locked->{$simple} = $data[$simple];
                }
            }

            foreach (['item_id', 'building_id', 'floor_id', 'room_id', 'location_name', 'department_id', 'assigned_user_id', 'next_due_date', 'notes'] as $nullable) {
                if (array_key_exists($nullable, $data)) {
                    $locked->{$nullable} = $data[$nullable];
                }
            }

            if (array_key_exists('scheduled_months', $data)) {
                $locked->scheduled_months = !empty($data['scheduled_months']) ? array_values($data['scheduled_months']) : null;

                // Only auto-recompute next_due_date from the new month list
                // when the caller didn't also send an explicit next_due_date
                // in this same request — an explicit value always wins.
                if (!array_key_exists('next_due_date', $data) && $locked->scheduled_months !== null) {
                    $base = $locked->last_completed_date ?? Carbon::today();
                    $locked->next_due_date = $this->calculateNextDueDateFromMonths(
                        $locked->scheduled_months,
                        $base instanceof Carbon ? $base : Carbon::parse($base),
                        $locked->last_completed_date === null
                    )->toDateString();
                }
            }

            $locked->updated_by = $actorUserId;
            $locked->save();

            if ($actorUserId) {
                $this->activityLogService->logFromSession([
                    'user_id' => $actorUserId,
                    'action' => 'UPDATE_PREVENTIVE_MAINTENANCE_TASK',
                    'module' => 'preventive_maintenance',
                    'entity_type' => 'preventive_maintenance_task',
                    'entity_id' => $locked->id,
                    'details' => 'Updated preventive maintenance task "' . $locked->title . '".',
                    'meta' => ['fields' => array_keys($data)],
                ]);
            }

            $newAssignee = $locked->assigned_user_id !== null ? (int) $locked->assigned_user_id : null;
            if ($newAssignee !== $previousAssignee) {
                $this->notifyAssignment($locked, $previousAssignee, $actorUserId);
            }

            return $locked->fresh();
        });
    }

    /**
     * Archive (soft-hide) rather than delete — no hard-delete path exists
     * anywhere in this service, per the task's explicit "no destructive
     * delete" instruction. Toggling is_active back to true un-archives it;
     * history rows are never touched by this method.
     */
    public function setActive(PreventiveMaintenanceTask $task, bool $active, ?int $actorUserId = null): PreventiveMaintenanceTask
    {
        $task->update([
            'is_active' => $active,
            'updated_by' => $actorUserId,
        ]);

        if ($actorUserId) {
            $this->activityLogService->logFromSession([
                'user_id' => $actorUserId,
                'action' => $active ? 'REACTIVATE_PREVENTIVE_MAINTENANCE_TASK' : 'ARCHIVE_PREVENTIVE_MAINTENANCE_TASK',
                'module' => 'preventive_maintenance',
                'entity_type' => 'preventive_maintenance_task',
                'entity_id' => $task->id,
                'details' => ($active ? 'Reactivated' : 'Archived') . ' preventive maintenance task "' . $task->title . '".',
            ]);
        }

        return $task->fresh();
    }

    /**
     * Marks a task's current cycle complete: writes an append-only history
     * row, then rolls the task forward (last_completed_date + next_due_date)
     * from the frequency. Never overwrites or deletes a prior history row.
     *
     * @return array{task: PreventiveMaintenanceTask, history: PreventiveMaintenanceHistory}
     */
    public function completeTask(
        PreventiveMaintenanceTask $task,
        array $data,
        ?UploadedFile $proof = null,
        ?int $actorUserId = null
    ): array {
        return DB::transaction(function () use ($task, $data, $proof, $actorUserId): array {
            $locked = PreventiveMaintenanceTask::query()->whereKey($task->id)->lockForUpdate()->first();
            if (!$locked) {
                throw ValidationException::withMessages(['id' => 'Preventive maintenance task not found.']);
            }

            $completedDate = Carbon::parse($data['completed_date'] ?? Carbon::today()->toDateString());
            $nextDue = !empty($locked->scheduled_months)
                ? $this->calculateNextDueDateFromMonths($locked->scheduled_months, $completedDate, false)
                : $this->calculateNextDueDate($locked->frequency, $completedDate);

            $proofPath = $proof !== null ? $this->storeCompletionProof($proof) : null;

            $history = PreventiveMaintenanceHistory::query()->create([
                'preventive_maintenance_task_id' => $locked->id,
                'completed_date' => $completedDate->toDateString(),
                'performed_by' => $data['performed_by'] ?? $locked->assigned_user_id,
                'recorded_by' => $actorUserId,
                'notes' => $data['notes'] ?? null,
                'findings' => $data['findings'] ?? null,
                'action_taken' => $data['action_taken'] ?? null,
                'completion_proof_path' => $proofPath,
                'next_due_date_snapshot' => $nextDue->toDateString(),
            ]);

            $locked->update([
                'last_completed_date' => $completedDate->toDateString(),
                'next_due_date' => $nextDue->toDateString(),
                'updated_by' => $actorUserId,
            ]);

            if ($actorUserId) {
                $this->activityLogService->logFromSession([
                    'user_id' => $actorUserId,
                    'action' => 'COMPLETE_PREVENTIVE_MAINTENANCE_TASK',
                    'module' => 'preventive_maintenance',
                    'entity_type' => 'preventive_maintenance_task',
                    'entity_id' => $locked->id,
                    'details' => 'Recorded completion for "' . $locked->title . '". Next due ' . $nextDue->toDateString() . '.',
                    'meta' => ['history_id' => $history->id, 'next_due_date' => $nextDue->toDateString()],
                ]);
            }

            // Notify whoever created/manages the task (if different from the
            // person completing it) that the cycle was completed and rolled
            // forward — mirrors Dispatch's "notify the party who isn't the
            // actor" pattern.
            $notifyTarget = (int) ($locked->created_by ?? 0);
            if ($notifyTarget > 0 && $notifyTarget !== $actorUserId) {
                $this->notificationService->notify(
                    $notifyTarget,
                    'Preventive Maintenance Completed',
                    '"' . $locked->title . '" was marked completed. Next due ' . $nextDue->toDateString() . '.',
                    'preventive_maintenance_task',
                    $locked->id
                );
            }

            return [
                'task' => $locked->fresh(),
                'history' => $history,
            ];
        });
    }

    private function notifyAssignment(PreventiveMaintenanceTask $task, ?int $previousAssignee, ?int $actorUserId): void
    {
        $assignee = $task->assigned_user_id !== null ? (int) $task->assigned_user_id : null;
        if ($assignee === null || $assignee === $previousAssignee || $assignee === $actorUserId) {
            return;
        }

        $this->notificationService->notify(
            $assignee,
            'Preventive Maintenance Task Assigned',
            'You have been assigned to "' . $task->title . '".',
            'preventive_maintenance_task',
            $task->id
        );
    }

    /**
     * Same validation rules and directory-per-feature convention as
     * DamageReportService::storeImage(), just scoped to its own uploads
     * subfolder so the two features never collide on filenames.
     */
    private function storeCompletionProof(UploadedFile $file): string
    {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, $allowedExt, true)) {
            throw ValidationException::withMessages([
                'completion_proof' => 'Only JPG, PNG, WEBP, or GIF images are allowed.',
            ]);
        }

        $targetDir = public_path('frontend/uploads/pm-completion-proofs');
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw ValidationException::withMessages([
                'completion_proof' => 'Unable to prepare upload directory.',
            ]);
        }

        $fileName = 'pm-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->move($targetDir, $fileName);

        return '/frontend/uploads/pm-completion-proofs/' . $fileName;
    }
}
