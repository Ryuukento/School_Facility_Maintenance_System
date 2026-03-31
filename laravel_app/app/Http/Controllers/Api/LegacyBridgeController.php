<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LegacyBridgeController extends Controller
{
    public function auth(Request $request, AuthController $authController)
    {
        $action = strtolower((string)$request->query('action', ''));

        return match ($action) {
            'login' => $authController->login($request),
            'register' => $authController->register($request),
            'logout' => $authController->logout($request),
            'check' => $authController->check($request),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid action',
            ], 400),
        };
    }

    public function users(Request $request, UserController $userController)
    {
        $action = strtolower((string)$request->query('action', ''));

        return match ($action) {
            'list' => $userController->index(),
            'deactivate', 'delete' => $userController->deactivate($request, $this->bindUser($request)),
            'activate' => $userController->activate($request, $this->bindUser($request)),
            'approve' => $userController->approve($request, $this->bindUser($request)),
            'reject' => $userController->reject($request, $this->bindUser($request)),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid action',
            ], 400),
        };
    }

    public function reports(Request $request, ReportController $reportController)
    {
        $action = strtolower((string)$request->query('action', ''));
        $report = $this->bindReport($request);

        return match ($action) {
            'list' => $reportController->index($request),
            'create' => $reportController->store($request),
            'get' => $report ? $reportController->show($request, $report) : response()->json(['success' => false, 'message' => 'Report ID required'], 400),
            'update' => $report ? $reportController->update($request, $report) : response()->json(['success' => false, 'message' => 'Report ID required'], 400),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid action',
            ], 400),
        };
    }

    public function maintenanceDashboard(Request $request, DashboardController $dashboardController)
    {
        $action = strtolower((string)$request->query('action', ''));

        return match ($action) {
            'stats' => $dashboardController->stats($request),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid action',
            ], 400),
        };
    }

    private function bindUser(Request $request)
    {
        $userId = (int)($request->integer('user_id'));
        if ($userId <= 0) {
            abort(response()->json([
                'success' => false,
                'message' => 'User ID is required',
            ], 400));
        }

        return \App\Models\User::query()->findOrFail($userId);
    }

    private function bindReport(Request $request)
    {
        $reportId = (int)($request->query('report_id', $request->query('id', 0)));
        if ($reportId <= 0) {
            return null;
        }

        return \App\Models\MaintenanceReport::query()->find($reportId);
    }
}
