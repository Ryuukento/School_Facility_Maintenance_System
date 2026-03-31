<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponder;

    public function stats(Request $request)
    {
        $authUser = $request->session()->get('auth_user', []);
        $userId = (int)($authUser['user_id'] ?? 0);
        $role = $this->normalizeRole((string)($authUser['role'] ?? ''));

        $query = MaintenanceReport::query();
        if (!in_array($role, ['super_admin', 'maintenance_admin'], true)) {
            $query->where(function ($builder) use ($userId): void {
                $builder->where('created_by', $userId)->orWhere('assigned_to', $userId);
            });
        }

        $stats = (clone $query)
            ->selectRaw('COUNT(*) as total_reports')
            ->selectRaw("SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as reports_today")
            ->first();

        $lowStock = DB::table('items')
            ->whereIn('status', ['low_stock', 'out_of_stock'])
            ->count();

        return $this->ok('Dashboard stats retrieved', [
            'total_reports' => (int)($stats->total_reports ?? 0),
            'reports_today' => (int)($stats->reports_today ?? 0),
            'pending' => (int)($stats->pending ?? 0),
            'in_progress' => (int)($stats->in_progress ?? 0),
            'completed' => (int)($stats->completed ?? 0),
            'low_stock' => (int)$lowStock,
            'links' => [
                'total_reports' => '/api/reports',
                'reports_today' => '/api/reports?date=today',
                'pending' => '/api/reports?status=submitted',
                'in_progress' => '/api/reports?status=in_progress',
                'completed' => '/api/reports?status=completed',
                'low_stock' => '/api/items?status=low_stock',
            ],
        ]);
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
