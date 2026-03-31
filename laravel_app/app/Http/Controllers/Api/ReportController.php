<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Support\ApiResponder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId = (int)($authUser['user_id'] ?? 0);
        $role = $this->normalizeRole((string)($authUser['role'] ?? ''));

        $query = MaintenanceReport::query()->with(['creator:user_id,full_name', 'assignee:user_id,full_name']);

        if (!in_array($role, ['super_admin', 'maintenance_admin'], true)) {
            $query->where(function ($builder) use ($userId): void {
                $builder->where('created_by', $userId)->orWhere('assigned_to', $userId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        $reports = $query->orderByDesc('created_at')->paginate((int)$request->integer('per_page', 20));

        return $this->ok('Reports retrieved', $reports);
    }

    public function store(Request $request)
    {
        $authUser = $request->session()->get('auth_user', []);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent,critical'],
            'status' => ['nullable', 'string', 'in:submitted,in_progress,completed,closed,cancelled'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,user_id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
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

        return $this->ok('Report created successfully', ['report_id' => $report->report_id], 201);
    }

    public function show(Request $request, MaintenanceReport $report)
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId = (int)($authUser['user_id'] ?? 0);
        $role = $this->normalizeRole((string)($authUser['role'] ?? ''));

        if (!in_array($role, ['super_admin', 'maintenance_admin'], true)
            && (int)$report->created_by !== $userId
            && (int)$report->assigned_to !== $userId) {
            return $this->fail('Forbidden', 403);
        }

        $report->load(['creator:user_id,full_name,email', 'assignee:user_id,full_name,email', 'department:department_id,name']);

        return $this->ok('Report retrieved', ['report' => $report]);
    }

    public function update(Request $request, MaintenanceReport $report)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent,critical'],
            'status' => ['sometimes', 'string', 'in:submitted,in_progress,completed,closed,cancelled'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,user_id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,department_id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if (isset($validated['status']) && in_array($validated['status'], ['completed', 'closed'], true) && !isset($validated['completed_date'])) {
            $validated['completed_date'] = now()->toDateString();
        }

        $report->update($validated);

        return $this->ok('Report updated successfully');
    }

    private function normalizeRole(string $role): string
    {
        $normalized = strtolower(trim($role));

        if ($normalized === 'admin_maintenance') {
            return 'maintenance_admin';
        }

        if ($normalized === 'eelab_staff' || $normalized === 'maintenance_personnel' || $normalized === '') {
            return 'maintenance_staff';
        }

        return $normalized;
    }
}
