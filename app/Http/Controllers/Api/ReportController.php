<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId = (int)($authUser['user_id'] ?? 0);
        $role = $this->normalizeRole((string)($authUser['role'] ?? ''));

        $query = MaintenanceReport::query()->with(['creator:user_id,full_name', 'assignee:user_id,full_name']);

        if (!in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true)) {
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
        $submitterName = (string)($authUser['full_name'] ?? 'A staff member');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,critical'],
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

    public function show(Request $request, MaintenanceReport $report)
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId = (int)($authUser['user_id'] ?? 0);
        $role = $this->normalizeRole((string)($authUser['role'] ?? ''));

        if (!in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true)
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
            'priority' => ['sometimes', 'string', 'in:low,medium,high,critical'],
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
                        'full_name' => (string)($row->full_name ?? 'Super Admin'),
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
            Log::warning('Non-fatal super admin email notification error', [
                'report_id' => (int)$report->report_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
