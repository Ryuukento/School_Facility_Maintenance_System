<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Services\RoleNormalizerService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponder;

    // -------------------------------------------------------------------------
    // General dashboard stats (used by main dashboard page)
    // -------------------------------------------------------------------------

    public function stats(Request $request): JsonResponse
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        $userId   = (int) ($authUser['user_id'] ?? 0);
        $role     = $this->normalizeRole((string) ($authUser['role'] ?? ''));

        $query = MaintenanceReport::query();
        if (!in_array($role, ['super_admin', 'maintenance_admin'], true)) {
            $query->where(function ($builder) use ($userId): void {
                $builder->where('created_by', $userId)->orWhere('assigned_to', $userId);
            });
        }

        $stats = (clone $query)
            ->selectRaw('COUNT(*) as total_reports')
            ->selectRaw("SUM(CASE WHEN status IN ('submitted', 'assigned') THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as reports_today")
            ->first();

        $lowStock = DB::table('items')
            ->whereIn('status', ['low_stock', 'out_of_stock'])
            ->count();

        return $this->ok('Dashboard stats retrieved', [
            'total_reports' => (int) ($stats->total_reports ?? 0),
            'reports_today' => (int) ($stats->reports_today ?? 0),
            'pending'       => (int) ($stats->pending       ?? 0),
            'in_progress'   => (int) ($stats->in_progress   ?? 0),
            'completed'     => (int) ($stats->completed     ?? 0),
            'low_stock'     => (int) $lowStock,
            'links'         => [
                'total_reports' => '/api/reports',
                'reports_today' => '/api/reports?date=today',
                'pending'       => '/api/reports?status=submitted',
                'in_progress'   => '/api/reports?status=in_progress',
                'completed'     => '/api/reports?status=completed',
                'low_stock'     => '/api/items?status=low_stock',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Maintenance dashboard — stats (GET /api/dashboard/maintenance/stats)
    // -------------------------------------------------------------------------

    /**
     * Role-aware monthly stats for the maintenance dashboard.
     * All three maintenance roles (staff, admin, super_admin) see system-wide
     * data; any other role gets a per-user filtered view.
     *
     * Query params: ?year=, ?month=
     * Response (wrapped): { total_reports, pending, in_progress,
     *   completed_this_month, overdue, avg_completion_days, buildings_overview }
     */
    public function maintenanceStats(Request $request): JsonResponse
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        $userId   = (int) ($authUser['user_id'] ?? 0);
        $role     = $this->normalizeRole((string) ($authUser['role'] ?? ''));
        $isAdmin  = in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true);

        $year     = max(2000, (int) $request->query('year',  date('Y')));
        $month    = max(1, min(12, (int) $request->query('month', date('n'))));
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo   = date('Y-m-d', strtotime("$dateFrom +1 month -1 day"));

        if ($isAdmin) {
            $sql = "SELECT
                        COUNT(*) as total_reports,
                        COUNT(CASE WHEN status = 'submitted'  AND created_at   >= ? AND created_at   <= ? THEN report_id END) as pending,
                        COUNT(CASE WHEN status = 'in_progress' AND created_at  >= ? AND created_at   <= ? THEN report_id END) as in_progress,
                        COUNT(CASE WHEN status = 'completed'  AND completed_date >= ? AND completed_date <= ? THEN report_id END) as completed_this_month,
                        COUNT(CASE WHEN due_date < ? AND status NOT IN ('completed','closed') AND created_at >= ? AND created_at <= ? THEN report_id END) as overdue,
                        AVG(CASE WHEN status IN ('completed','closed') AND created_at >= ? AND created_at <= ?
                            THEN TIMESTAMPDIFF(DAY, created_at, COALESCE(completed_date, updated_at)) END) as avg_completion_days,
                        (SELECT COUNT(*) FROM buildings) as buildings_overview
                    FROM maintenance_reports
                    WHERE created_at >= ? AND created_at <= ?";

            $params = [
                $dateFrom, $dateTo,         // pending
                $dateFrom, $dateTo,         // in_progress
                $dateFrom, $dateTo,         // completed_this_month
                $dateTo, $dateFrom, $dateTo, // overdue
                $dateFrom, $dateTo,         // avg_completion_days
                $dateFrom, $dateTo,         // WHERE
            ];
        } else {
            $sql = "SELECT
                        COUNT(DISTINCT CASE WHEN created_by = ? AND created_at >= ? AND created_at <= ? THEN report_id END) as total_reports,
                        COUNT(DISTINCT CASE WHEN status = 'submitted'   AND assigned_to = ? AND created_at >= ? AND created_at <= ? THEN report_id END) as pending,
                        COUNT(DISTINCT CASE WHEN status = 'in_progress' AND assigned_to = ? AND created_at >= ? AND created_at <= ? THEN report_id END) as in_progress,
                        COUNT(DISTINCT CASE WHEN status = 'completed'   AND completed_date >= ? AND completed_date <= ? AND assigned_to = ? THEN report_id END) as completed_this_month,
                        COUNT(DISTINCT CASE WHEN due_date < ? AND status NOT IN ('completed','closed') AND assigned_to = ? AND created_at >= ? AND created_at <= ? THEN report_id END) as overdue,
                        AVG(CASE WHEN status IN ('completed','closed') AND created_at >= ? AND created_at <= ?
                            THEN TIMESTAMPDIFF(DAY, created_at, COALESCE(completed_date, updated_at)) END) as avg_completion_days,
                        (SELECT COUNT(*) FROM buildings) as buildings_overview
                    FROM maintenance_reports
                    WHERE (created_by = ? OR assigned_to = ?) AND created_at >= ? AND created_at <= ?";

            $params = [
                $userId, $dateFrom, $dateTo,       // total_reports
                $userId, $dateFrom, $dateTo,       // pending
                $userId, $dateFrom, $dateTo,       // in_progress
                $dateFrom, $dateTo, $userId,       // completed_this_month
                $dateTo, $userId, $dateFrom, $dateTo, // overdue
                $dateFrom, $dateTo,                // avg_completion_days
                $userId, $userId, $dateFrom, $dateTo, // WHERE
            ];
        }

        $row = DB::selectOne($sql, $params);

        return $this->ok('Dashboard stats retrieved', [
            'total_reports'       => (int)   ($row->total_reports        ?? 0),
            'pending'             => (int)   ($row->pending              ?? 0),
            'in_progress'         => (int)   ($row->in_progress          ?? 0),
            'completed_this_month'=> (int)   ($row->completed_this_month ?? 0),
            'overdue'             => (int)   ($row->overdue              ?? 0),
            'avg_completion_days' => round((float) ($row->avg_completion_days ?? 0), 1),
            'buildings_overview'  => (int)   ($row->buildings_overview   ?? 0),
        ]);
    }

    // -------------------------------------------------------------------------
    // Maintenance dashboard — charts (GET /api/dashboard/maintenance/charts)
    // Also used by the staff dashboard.
    // -------------------------------------------------------------------------

    /**
     * Status distribution, priority distribution, and 6-month trend charts.
     * Query params: ?year=, ?month=
     * Response (wrapped): { status_data: {labels, values},
     *   priority_data: {labels, values}, trend_data: {labels, created, completed} }
     */
    public function maintenanceCharts(Request $request): JsonResponse
    {
        $authUser = (array) $request->session()->get('auth_user', $request->session()->get('user', []));
        $userId   = (int) ($authUser['user_id'] ?? 0);
        $role     = $this->normalizeRole((string) ($authUser['role'] ?? ''));
        $isAdmin  = in_array($role, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true);

        $year     = max(2000, (int) $request->query('year',  date('Y')));
        $month    = max(1, min(12, (int) $request->query('month', date('n'))));
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo   = date('Y-m-d', strtotime("$dateFrom +1 month -1 day"));

        // -- Status distribution --
        $statusSql    = "SELECT
                            CASE WHEN status IS NULL OR TRIM(status) = '' THEN 'submitted' ELSE status END AS normalized_status,
                            COUNT(*) as count
                         FROM maintenance_reports
                         WHERE created_at >= ? AND created_at <= ?";
        $statusParams = [$dateFrom, $dateTo];
        if (!$isAdmin) {
            $statusSql    .= ' AND (created_by = ? OR assigned_to = ?)';
            $statusParams[] = $userId;
            $statusParams[] = $userId;
        }
        $statusSql .= ' GROUP BY normalized_status ORDER BY count DESC';

        $statusRows    = DB::select($statusSql, $statusParams);
        $statusLabels  = [];
        $statusValues  = [];
        foreach ($statusRows as $row) {
            $statusLabels[] = ucfirst(str_replace('_', ' ', $row->normalized_status));
            $statusValues[] = (int) $row->count;
        }

        // -- Priority distribution --
        $prioritySql    = "SELECT priority, COUNT(*) as count
                           FROM maintenance_reports
                           WHERE created_at >= ? AND created_at <= ?";
        $priorityParams = [$dateFrom, $dateTo];
        if (!$isAdmin) {
            $prioritySql    .= ' AND (created_by = ? OR assigned_to = ?)';
            $priorityParams[] = $userId;
            $priorityParams[] = $userId;
        }
        $prioritySql .= " GROUP BY priority ORDER BY FIELD(priority,'low','medium','high','urgent','critical')";

        $priorityRows   = DB::select($prioritySql, $priorityParams);
        $priorityLabels = [];
        $priorityValues = [];
        foreach ($priorityRows as $row) {
            $priorityLabels[] = ucfirst((string) ($row->priority ?? ''));
            $priorityValues[] = (int) $row->count;
        }

        // -- 6-month trend (single grouped query) --
        // GROUP BY both the sort key and the display label so MySQL ONLY_FULL_GROUP_BY
        // mode doesn't reject a SELECT expression that differs from the GROUP BY expression.
        $trendSql    = "SELECT
                            DATE_FORMAT(created_at, '%b %y') as month,
                            COUNT(*) as created,
                            SUM(CASE WHEN status IN ('completed','closed') THEN 1 ELSE 0 END) as completed
                        FROM maintenance_reports
                        WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)";
        $trendParams = [];
        if (!$isAdmin) {
            $trendSql    .= ' AND (created_by = ? OR assigned_to = ?)';
            $trendParams[] = $userId;
            $trendParams[] = $userId;
        }
        $trendSql .= " GROUP BY DATE_FORMAT(created_at,'%Y-%m'), DATE_FORMAT(created_at, '%b %y') ORDER BY MIN(created_at)";

        $trendRows      = DB::select($trendSql, $trendParams);
        $trendLabels    = [];
        $trendCreated   = [];
        $trendCompleted = [];
        foreach ($trendRows as $row) {
            $trendLabels[]    = $row->month;
            $trendCreated[]   = (int) $row->created;
            $trendCompleted[] = (int) $row->completed;
        }

        return $this->ok('Chart data retrieved', [
            'status_data'   => ['labels' => $statusLabels,  'values' => $statusValues],
            'priority_data' => ['labels' => $priorityLabels, 'values' => $priorityValues],
            'trend_data'    => ['labels' => $trendLabels, 'created' => $trendCreated, 'completed' => $trendCompleted],
        ]);
    }

    // -------------------------------------------------------------------------
    // Super-admin dashboard (flat response — frontend reads data.key directly)
    // -------------------------------------------------------------------------

    /**
     * GET /api/dashboard/super-admin/stats
     * System-wide counts + role/status breakdowns for selected month.
     * Query params: ?year=, ?month=
     * Response FLAT (no .data wrapper): { success, totalUsers, totalReports, ... }
     */
    public function superAdminStats(Request $request): JsonResponse
    {
        $year  = max(2000, (int) $request->query('year',  date('Y')));
        $month = max(1, min(12, (int) $request->query('month', date('n'))));

        // Total active users
        $totalUsers = DB::table('users')->where('status', 'active')->count();

        // Role breakdown (active users)
        $roleRows     = DB::table('users')
            ->selectRaw('role, COUNT(*) as count')
            ->where('status', 'active')
            ->groupBy('role')
            ->get();
        $roleBreakdown = [];
        foreach ($roleRows as $row) {
            $roleBreakdown[$row->role] = (int) $row->count;
        }

        // Monthly-scoped report counts
        $totalReports = DB::table('maintenance_reports')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $statusRows = DB::table('maintenance_reports')
            ->selectRaw('status, COUNT(*) as count')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('status')
            ->get();
        $statusBreakdown = [];
        foreach ($statusRows as $row) {
            $statusBreakdown[$row->status] = (int) $row->count;
        }

        $completedThisMonth = DB::table('maintenance_reports')
            ->where('status', 'completed')
            ->whereMonth('completed_date', $month)
            ->whereYear('completed_date', $year)
            ->count();

        $pendingReports = DB::table('maintenance_reports')
            ->whereIn('status', ['submitted', 'assigned'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $overdueReports = DB::table('maintenance_reports')
            ->where('due_date', '<', date('Y-m-d'))
            ->whereNotIn('status', ['completed', 'closed', 'cancelled'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $inProgressReports = DB::table('maintenance_reports')
            ->where('status', 'in_progress')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $totalDepartments = DB::table('departments')->count();
        $totalBuildings = DB::table('buildings')->count();

        return response()->json([
            'success'            => true,
            'totalUsers'         => (int) $totalUsers,
            'totalReports'       => (int) $totalReports,
            'totalDepartments'   => (int) $totalDepartments,
            'buildingsOverview'  => (int) $totalBuildings,
            'completedThisMonth' => (int) $completedThisMonth,
            'pendingReports'     => (int) $pendingReports,
            'overdueReports'     => (int) $overdueReports,
            'inProgressReports'  => (int) $inProgressReports,
            'roleBreakdown'      => $roleBreakdown,
            'statusBreakdown'    => $statusBreakdown,
        ]);
    }

    /**
     * GET /api/dashboard/super-admin/charts
     * Status, priority, department, and trend charts for selected month.
     * Query params: ?year=, ?month=
     * Response FLAT: { success, statusChart, priorityChart, departmentChart, trendChart }
     */
    public function superAdminCharts(Request $request): JsonResponse
    {
        $year  = max(2000, (int) $request->query('year',  date('Y')));
        $month = max(1, min(12, (int) $request->query('month', date('n'))));

        $statusColors   = [
            'submitted'   => '#3b82f6',
            'assigned'    => '#f59e0b',
            'in_progress' => '#8b5cf6',
            'completed'   => '#10b981',
            'cancelled'   => '#ef4444',
        ];
        $priorityColors = [
            'urgent' => '#ef4444',
            'high'   => '#f97316',
            'medium' => '#f59e0b',
            'low'    => '#10b981',
        ];

        // Status chart
        $statusRows    = DB::table('maintenance_reports')
            ->selectRaw('status, COUNT(*) as count')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('status')
            ->get();
        $statusLabels  = [];
        $statusCounts  = [];
        foreach ($statusRows as $row) {
            $statusLabels[] = ucfirst((string) ($row->status ?? ''));
            $statusCounts[] = (int) $row->count;
        }

        // Priority chart
        $priorityRows   = DB::select(
            "SELECT priority, COUNT(*) as count
             FROM maintenance_reports
             WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
             GROUP BY priority
             ORDER BY FIELD(priority,'urgent','high','medium','low')",
            [$month, $year]
        );
        $priorityLabels = [];
        $priorityCounts = [];
        foreach ($priorityRows as $row) {
            $priorityLabels[] = ucfirst((string) ($row->priority ?? ''));
            $priorityCounts[] = (int) $row->count;
        }

        // Department chart (all-time, top 6)
        $deptRows   = DB::table('departments as d')
            ->selectRaw('d.name, COUNT(mr.report_id) as count')
            ->leftJoin('maintenance_reports as mr', 'd.department_id', '=', 'mr.department_id')
            ->groupBy('d.department_id', 'd.name')
            ->orderByDesc('count')
            ->limit(6)
            ->get();
        $deptLabels = [];
        $deptCounts = [];
        foreach ($deptRows as $row) {
            $deptLabels[] = $row->name;
            $deptCounts[] = (int) $row->count;
        }

        // Trend chart (last 6 months)
        // GROUP BY both the sort key and the display label so MySQL strict-mode
        // (ONLY_FULL_GROUP_BY) doesn't reject a SELECT expression that differs
        // from the GROUP BY expression.
        $trendRows   = DB::select(
            "SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count
             FROM maintenance_reports
             WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b %Y')
             ORDER BY MIN(created_at)"
        );
        $trendLabels = [];
        $trendCounts = [];
        foreach ($trendRows as $row) {
            $trendLabels[] = $row->month;
            $trendCounts[] = (int) $row->count;
        }

        return response()->json([
            'success'         => true,
            'statusChart'     => [
                'labels' => $statusLabels,
                'data'   => $statusCounts,
                'colors' => array_map(static function (string $label) use ($statusColors): string {
                    $key = strtolower(str_replace(' ', '_', $label));
                    return $statusColors[$key] ?? '#6b7280';
                }, $statusLabels),
            ],
            'priorityChart'   => [
                'labels' => $priorityLabels,
                'data'   => $priorityCounts,
                'colors' => array_map(static function (string $label) use ($priorityColors): string {
                    $key = strtolower($label);
                    return $priorityColors[$key] ?? '#6b7280';
                }, $priorityLabels),
            ],
            'departmentChart' => [
                'labels' => $deptLabels,
                'data'   => $deptCounts,
            ],
            'trendChart'      => [
                'labels' => $trendLabels,
                'data'   => $trendCounts,
            ],
        ]);
    }

    /**
     * GET /api/dashboard/super-admin/overview
     * Users-per-department and most-active maintenance staff.
     * Response FLAT: { success, departmentUsers, activeStaff }
     */
    public function superAdminOverview(Request $request): JsonResponse
    {
        $departmentUsers = DB::table('departments as d')
            ->selectRaw('d.name, COUNT(u.user_id) as count')
            ->leftJoin('users as u', function ($join) {
                $join->on('d.department_id', '=', 'u.department_id')
                     ->where('u.status', '=', 'active');
            })
            ->groupBy('d.department_id', 'd.name')
            ->orderByDesc('count')
            ->get();

        $activeStaff = DB::table('users as u')
            ->selectRaw('u.full_name, COUNT(mr.report_id) as assigned_count')
            ->leftJoin('maintenance_reports as mr', 'u.user_id', '=', 'mr.assigned_to')
            ->where('u.role', 'maintenance_staff')
            ->where('u.status', 'active')
            ->groupBy('u.user_id', 'u.full_name')
            ->orderByDesc('assigned_count')
            ->limit(5)
            ->get();

        return response()->json([
            'success'         => true,
            'departmentUsers' => $departmentUsers,
            'activeStaff'     => $activeStaff,
        ]);
    }

    /**
     * GET /api/dashboard/super-admin/activity
     * Last 10 activity log entries with user name.
     * Response FLAT: { success, activities }
     */
    public function superAdminActivity(Request $request): JsonResponse
    {
        $activities = DB::table('activity_logs as al')
            ->select([
                'al.id',
                'al.action',
                'al.entity_type',
                'al.entity_id',
                'al.details',
                'al.created_at',
                'u.full_name',
            ])
            ->leftJoin('users as u', 'al.user_id', '=', 'u.user_id')
            ->orderByDesc('al.created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success'    => true,
            'activities' => $activities,
        ]);
    }

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    private function normalizeRole(string $role): string
    {
        return RoleNormalizerService::normalizeWithStaffDefault($role);
    }
}
