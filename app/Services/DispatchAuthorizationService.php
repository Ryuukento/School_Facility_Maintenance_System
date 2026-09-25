<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * TASK 13 — Dispatch Release Assignment Workflow.
 *
 * Single source of truth for "who may do what to a dispatch" under the
 * revised business process:
 *
 *   Head Maintenance (maintenance_admin)
 *     creates the dispatch AND picks the Release Personnel, may reassign
 *     while the dispatch is not yet released, may NOT approve, may NOT release.
 *
 *   Administrator (super_admin)
 *     approves or rejects Head-created dispatches. May NOT release.
 *
 *     TASK 41 — may ALSO create dispatches, which follow a second workflow:
 *     an Administrator-created dispatch requires NO approval and is created
 *     directly in the existing 'approved' (releasable) state. It is NOT
 *     self-approved — approved_by/approved_at stay NULL so the record never
 *     claims an approval that did not happen. Administrator may still not
 *     REASSIGN personnel after creation (canAssignReleasePersonnel is
 *     unchanged); they choose the personnel once, at creation.
 *
 *   Maintenance Staff (maintenance_staff)
 *     releases — but ONLY the dispatch that names them in
 *     release_assigned_to. Role membership alone is explicitly not enough.
 *
 * Department authorization (TASK 9) is preserved: a Head may only assign
 * personnel from their own department. The comparison itself is delegated to
 * ReportAuthorizationService::departmentsMatch() so the null-department rule
 * stays identical across the whole application rather than being re-derived
 * here.
 *
 * Every method takes the *session* auth user array (the legacy session bridge
 * shape used everywhere else in this app), never a trusted client-supplied id.
 */
class DispatchAuthorizationService
{
    public function __construct(
        private readonly ReportAuthorizationService $reportAuthorizationService
    ) {
    }

    /**
     * TASK 41 — Administrator Create Dispatch Without Approval.
     *
     * Head Maintenance still creates dispatches. Administrator may now ALSO
     * create them, under a different workflow (see creationRequiresApproval()
     * below): an Administrator-created dispatch skips the approval step
     * entirely rather than being self-approved.
     *
     * Maintenance Staff remain excluded — this task did not widen creation to
     * them, and the route middleware names both allowed roles explicitly so a
     * crafted request cannot reach the service with any other role.
     */
    public function canCreateDispatch(array $authUser): bool
    {
        return in_array($this->role($authUser), ['maintenance_admin', 'super_admin'], true);
    }

    /**
     * TASK 41 — THE approval-bypass rule, in exactly one place.
     *
     * Returns whether a dispatch created by this user must go through the
     * Administrator approval step before it becomes releasable.
     *
     *   super_admin        -> false : no approval step exists for their
     *                                 dispatches. They are created directly in
     *                                 'approved' (the existing enum value that
     *                                 means "releasable"), with approved_by and
     *                                 approved_at left NULL so the audit trail
     *                                 never claims an approval happened.
     *   everyone else      -> true  : unchanged Head Maintenance workflow —
     *                                 created 'pending', requires an
     *                                 Administrator to approve.
     *
     * Deliberately expressed as "is the role super_admin" rather than "is the
     * role NOT maintenance_admin", so that any role added to
     * canCreateDispatch() in future defaults to REQUIRING approval instead of
     * silently inheriting the bypass.
     */
    public function creationRequiresApproval(array $authUser): bool
    {
        return $this->role($authUser) !== 'super_admin';
    }

    /**
     * Approve/reject is Administrator-only. Head Maintenance approving its own
     * dispatch would defeat the entire point of the approval step.
     */
    public function canApproveDispatch(array $authUser): bool
    {
        return $this->role($authUser) === 'super_admin';
    }

    /**
     * Reassignment is allowed while the dispatch still has a release ahead of
     * it — i.e. pending OR approved — and is refused once the inventory has
     * actually moved (released) or the dispatch is dead (cancelled).
     */
    public function canAssignReleasePersonnel(array $authUser, Dispatch $dispatch): bool
    {
        if ($this->role($authUser) !== 'maintenance_admin') {
            return false;
        }

        if (!in_array($dispatch->status, ['pending', 'approved'], true)) {
            return false;
        }

        return $this->reportAuthorizationService->departmentsMatch(
            $this->userDepartmentId($authUser),
            $dispatch->department_id !== null ? (int) $dispatch->department_id : null
        );
    }

    /**
     * The core security rule of this task: identity, not role.
     *
     * A dispatch may only be released by the exact user recorded in
     * release_assigned_to. An unassigned dispatch is releasable by nobody —
     * that is intentional, and it is what makes every pre-TASK-13 dispatch
     * (which has no assignment) safe by default.
     */
    public function canReleaseDispatch(array $authUser, Dispatch $dispatch): bool
    {
        if ($this->role($authUser) !== 'maintenance_staff') {
            return false;
        }

        $assignedTo = $dispatch->release_assigned_to !== null ? (int) $dispatch->release_assigned_to : null;
        $userId = (int) ($authUser['user_id'] ?? 0);

        return $assignedTo !== null && $userId > 0 && $assignedTo === $userId;
    }

    /**
     * Maintenance Staff see only what is assigned to them. Everyone else keeps
     * the pre-existing "see everything" behaviour, so no Administrator or Head
     * Maintenance view narrows as a side effect of this task.
     */
    public function shouldScopeIndexToAssignments(array $authUser): bool
    {
        return $this->role($authUser) === 'maintenance_staff';
    }

    /**
     * Validates a proposed Release Personnel selection SERVER-SIDE.
     *
     * Client-side filtering of the searchable selector is a convenience only;
     * this is the check that actually prevents a Computer Department Head from
     * assigning Electrical/Maritime staff by posting a different user_id.
     *
     * Throws rather than returning a bool so every call site surfaces the same
     * 400-level message through the controllers' existing ValidationException
     * handling instead of inventing its own error text.
     */
    public function assertAssignableReleasePersonnel(
        array $authUser,
        int $releaseAssignedTo,
        ?int $destinationDepartmentId = null
    ): User {
        $user = User::query()->find($releaseAssignedTo);

        if (!$user || $user->status !== 'active') {
            throw ValidationException::withMessages([
                'release_assigned_to' => 'Selected release personnel is invalid or inactive.',
            ]);
        }

        if (RoleNormalizerService::normalize((string) ($user->role ?? '')) !== 'maintenance_staff') {
            throw ValidationException::withMessages([
                'release_assigned_to' => 'Release personnel must be a Maintenance Staff member.',
            ]);
        }

        $assigneeDepartmentId = $user->department_id !== null ? (int) $user->department_id : null;

        // TASK 41 — Administrator has no department of its own, so the
        // "same department as the acting user" rule would match nobody and the
        // selector would always be empty (proven against live data in the Task
        // 41 audit: the only Administrator has department NULL, the only
        // Maintenance Staff has department 1).
        //
        // The fix is NOT to drop department security. It is to scope the
        // Administrator against the DISPATCH'S DESTINATION department instead
        // of their own — the department the items are actually going to, which
        // is the meaningful relationship for "who should hand these over".
        //
        // The role check and the active check above still apply to everyone,
        // so an Administrator still cannot assign an inactive user, a Head, or
        // another Administrator.
        if ($this->role($authUser) === 'super_admin') {
            // No destination department chosen means there is nothing to scope
            // against; the role + active constraints above remain the control.
            if ($destinationDepartmentId === null) {
                return $user;
            }

            if (!$this->reportAuthorizationService->departmentsMatch($destinationDepartmentId, $assigneeDepartmentId)) {
                throw ValidationException::withMessages([
                    'release_assigned_to' => "Release personnel must belong to the dispatch's destination department.",
                ]);
            }

            return $user;
        }

        // Unchanged for Head Maintenance: their own department must match.
        $sameDepartment = $this->reportAuthorizationService->departmentsMatch(
            $this->userDepartmentId($authUser),
            $assigneeDepartmentId
        );

        if (!$sameDepartment) {
            throw ValidationException::withMessages([
                'release_assigned_to' => 'Release personnel must belong to your own department.',
            ]);
        }

        return $user;
    }

    private function role(array $authUser): string
    {
        // normalize() rather than normalizeWithStaffDefault(): the latter turns
        // an EMPTY role into 'maintenance_staff', which for this service would
        // mean a session with no role at all could satisfy the staff branch.
        // Here an unknown/empty role must grant nothing, so it is left as ''
        // and matches none of the three checks below.
        return RoleNormalizerService::normalize((string) ($authUser['role'] ?? ''));
    }

    private function userDepartmentId(array $authUser): ?int
    {
        $departmentId = $authUser['department_id'] ?? null;

        return $departmentId !== null ? (int) $departmentId : null;
    }
}
