<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\DamageReportController;
use App\Http\Controllers\Api\DeploymentTrackingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\InventoryCategoryController;
use App\Http\Controllers\Api\InventoryRoomController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PurchaseReceiptController;
use App\Http\Controllers\Api\ReplacementTrackingController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\RepairController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AnalyticsReportController;
use App\Http\Controllers\Api\InventoryStockController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureApiAuthenticated;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// Authentication routes (no auth middleware)
Route::get('/', function () {
    if (request()->session()->has('auth_user') || request()->session()->has('user')) {
        return redirect('/frontend/pages/dashboard.php');
    }
    return redirect('/frontend/pages/index.php');
})->name('login');

Route::get('/login', function () {
    if (request()->session()->has('auth_user') || request()->session()->has('user')) {
        return redirect('/frontend/pages/dashboard.php');
    }
    return redirect('/frontend/pages/index.php');
})->name('auth.login');

// Web routes that require authentication
Route::middleware(EnsureApiAuthenticated::class)->group(function (): void {
    Route::get('/dashboard', fn() => redirect('/frontend/pages/dashboard.php'))->name('dashboard');
    Route::get('/reports', fn() => redirect('/frontend/pages/reports.php'))->name('reports.index');
    Route::get('/reports/create', fn() => redirect('/frontend/pages/create-report.php'))->name('reports.create');
    Route::get('/reports/{id}', fn($id) => redirect('/frontend/pages/report-detail.php?id=' . (int) $id))->name('reports.show');
    Route::get('/profile', fn() => redirect('/frontend/pages/profile.php'))->name('profile');
    Route::get('/users', fn() => redirect('/frontend/pages/users.php'))->name('users.index');
    Route::get('/notifications', fn() => redirect('/frontend/pages/notifications-center.php'))->name('notifications');
    Route::get('/inventory', fn() => redirect('/frontend/pages/inventory.php'))->name('inventory.index');
    Route::get('/suppliers/manage', fn() => redirect('/frontend/pages/suppliers-manage.php'))->name('suppliers.manage');
    Route::get('/dispatches', fn() => redirect('/frontend/pages/dispatches.php'))->name('dispatches.index');
    Route::get('/dispatches/create', fn() => redirect('/frontend/pages/dispatch-create.php'))->name('dispatches.create');
    Route::get('/dispatches/{id}', function ($id) { return redirect('/frontend/pages/dispatch-detail.php?id=' . (int)$id); })->name('dispatches.show');
    Route::get('/damage-reports', fn() => redirect('/frontend/pages/damage-reports.php'))->name('damage-reports.index');
    Route::get('/damage-reports/create', fn() => redirect('/frontend/pages/damage-report-create.php'))->name('damage-reports.create');
    Route::get('/damage-reports/{id}', fn($id) => redirect('/frontend/pages/damage-report-detail.php?id=' . (int)$id))->name('damage-reports.show');
    Route::get('/damage-reports/{id}/update', fn($id) => redirect('/frontend/pages/damage-report-update.php?id=' . (int)$id))->name('damage-reports.update');
    Route::get('/repairs', fn() => redirect('/frontend/pages/repair-requests.php'))->name('repairs.index');
    Route::get('/repairs/{id}', fn($id) => redirect('/frontend/pages/repair-detail.php?id=' . (int)$id))->name('repairs.show');
    Route::get('/repairs/{id}/assign', fn($id) => redirect('/frontend/pages/repair-assignment.php?id=' . (int)$id))->name('repairs.assign-page');
    Route::get('/repairs/{id}/update', fn($id) => redirect('/frontend/pages/repair-update.php?id=' . (int)$id))->name('repairs.update-page');
    Route::get('/repairs/{id}/replacement', fn($id) => redirect('/frontend/pages/replacement-request.php?id=' . (int)$id))->name('repairs.replacement-page');
    Route::get('/replacement-tracking', fn() => redirect('/frontend/pages/replacement-tracking.php'))->name('inventory.replacement-tracking');
    Route::get('/settings', fn() => redirect('/frontend/pages/settings.php'))->name('settings');
    Route::get('/maintenance-dashboard', fn() => redirect('/frontend/pages/maintenance-dashboard.php'))->name('maintenance.dashboard');
    Route::get('/maintenance-reports', fn() => redirect('/frontend/pages/maintenance-reports-list.php'))->name('maintenance.reports.index');
    Route::get('/maintenance-reports/{id}', fn($id) => redirect('/frontend/pages/maintenance-report-detail.php?id=' . (int) $id))->name('maintenance.reports.show');
    Route::get('/staff-dashboard', fn() => redirect('/frontend/pages/staff-dashboard.php'))->name('staff.dashboard');
    Route::get('/super-admin-dashboard', fn() => redirect('/frontend/pages/super-admin-dashboard.php'))->name('superadmin.dashboard');
    Route::get('/activity-log', fn() => redirect('/frontend/pages/activity-log.php'))->name('activity.log');
    Route::get('/activity-logs', fn() => redirect('/frontend/pages/activity-log.php'))->name('activity-logs.index');
    Route::get('/activity-logs/{id}', fn($id) => redirect('/frontend/pages/activity-log-detail.php?id=' . (int) $id))->name('activity-logs.show');
    Route::get('/analytics', fn() => redirect('/frontend/pages/analytics-dashboard.php'))->name('analytics');
    Route::get('/buildings-overview', fn() => redirect('/frontend/pages/buildings-overview.php'))->name('buildings.overview');
    Route::get('/inventory-transactions', fn() => redirect('/frontend/pages/inventory-transactions.php'))->name('inventory.transactions');
    Route::get('/inventory-reports', fn() => redirect('/frontend/pages/inventory-reports.php'))->name('inventory.reports');
    Route::get('/purchase-receipts', fn() => redirect('/frontend/pages/purchase-receipts.php'))->name('purchase-receipts.page');
    Route::get('/deployment-tracking', fn() => redirect('/frontend/pages/deployment-tracking.php'))->name('deployment.tracking');
    
    // Logout route (web)
    Route::get('/logout', function () {
        // Clear native PHP $_SESSION so legacy frontend pages lose access too
        $_SESSION = [];

        request()->session()->forget('auth_user');
        request()->session()->forget('user');
        request()->session()->forget('user_id');
        request()->session()->forget('role');
        request()->session()->forget('last_activity');
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/frontend/pages/index.php');
    })->name('logout');
});

// Compatibility redirect for legacy hardcoded URLs that still include the old
// /laravel_app/public segment after the app was moved to the project root.
Route::get('/School_Facility_Maintenance_System/laravel_app/public/{path}', function (string $path) {
    $qs  = request()->getQueryString();
    $url = '/public/' . ltrim($path, '/');
    return redirect($qs ? $url . '?' . $qs : $url);
})->where('path', '.*');

// Keep the new /public-prefixed path working consistently as well.
Route::get('/School_Facility_Maintenance_System/{path}', function (string $path) {
    $qs  = request()->getQueryString();
    $url = '/' . ltrim($path, '/');
    return redirect($qs ? $url . '?' . $qs : $url);
})->where('path', '.*');

Route::prefix('api')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('forgot_password_request', [AuthController::class, 'forgotPasswordRequest']);
        Route::post('forgot_password_reset', [AuthController::class, 'forgotPasswordReset']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware(EnsureApiAuthenticated::class);
        Route::get('check', [AuthController::class, 'check'])->middleware(EnsureApiAuthenticated::class);
    });

    Route::middleware([EnsureApiAuthenticated::class])->group(function (): void {
        Route::get('reports',          [ReportController::class, 'index']);
        Route::post('reports',         [ReportController::class, 'store']);
        Route::get('reports/recent',   [ReportController::class, 'recent']);
        Route::get('reports/{report}', [ReportController::class, 'show']);
        Route::patch('reports/{report}', [ReportController::class, 'update']);
        Route::delete('reports/{report}', [ReportController::class, 'destroy']);

        Route::prefix('dashboard')->group(function (): void {
            Route::get('stats', [DashboardController::class, 'stats']);
            Route::prefix('maintenance')->group(function (): void {
                Route::get('stats',  [DashboardController::class, 'maintenanceStats']);
                Route::get('charts', [DashboardController::class, 'maintenanceCharts']);
            });
            Route::prefix('super-admin')->middleware(EnsureRole::class . ':super_admin')->group(function (): void {
                Route::get('stats',    [DashboardController::class, 'superAdminStats']);
                Route::get('charts',   [DashboardController::class, 'superAdminCharts']);
                Route::get('overview', [DashboardController::class, 'superAdminOverview']);
                Route::get('activity', [DashboardController::class, 'superAdminActivity']);
            });
        });

        Route::get('departments', [DepartmentController::class, 'index']);

        Route::get('rooms',         [RoomController::class, 'index']);
        Route::post('rooms',        [RoomController::class, 'store']);
        Route::delete('rooms/{id}', [RoomController::class, 'destroy']);

        Route::get('buildings',                                  [BuildingController::class, 'index']);
        Route::post('buildings',                                 [BuildingController::class, 'store']);
        Route::patch('buildings/{id}',                           [BuildingController::class, 'update']);
        Route::delete('buildings/{id}',                          [BuildingController::class, 'destroy']);
        Route::get('buildings/deployed-items',                   [BuildingController::class, 'deployedItems']);
        Route::get('buildings/{id}/floors',                      [BuildingController::class, 'floors']);
        Route::post('buildings/{id}/floors',                     [BuildingController::class, 'storeFloor']);
        Route::delete('buildings/{id}/floors/{floorId}',         [BuildingController::class, 'destroyFloor']);

        Route::get('inventory-categories',    [InventoryCategoryController::class, 'index']);
        Route::post('inventory-categories',   [InventoryCategoryController::class, 'store'])
            ->middleware(EnsureRole::class . ':super_admin,maintenance_admin');
        Route::patch('inventory-categories/{id}', [InventoryCategoryController::class, 'update'])
            ->middleware(EnsureRole::class . ':super_admin,maintenance_admin');
        Route::delete('inventory-categories/{id}', [InventoryCategoryController::class, 'destroy'])
            ->middleware(EnsureRole::class . ':super_admin,maintenance_admin');

        Route::prefix('notifications')->group(function (): void {
            Route::get('',              [NotificationController::class, 'index']);
            Route::get('unread',        [NotificationController::class, 'unread']);
            Route::post('read-all',     [NotificationController::class, 'markAllRead']);
            Route::post('{id}/read',    [NotificationController::class, 'markRead']);
            Route::delete('{id}',       [NotificationController::class, 'destroy']);
        });

        Route::get('items', [ItemController::class, 'index']);
        Route::post('items', [ItemController::class, 'store']);
        Route::get('items/{item}', [ItemController::class, 'show']);
        Route::patch('items/{item}', [ItemController::class, 'update']);
        Route::delete('items/{item}', [ItemController::class, 'destroy']);
        Route::get('items/{item}/history', [ItemController::class, 'history']);
        Route::post('items/{item}/adjust-stock', [ItemController::class, 'adjustStock'])
            ->middleware(EnsureRole::class . ':super_admin,maintenance_admin');

        Route::prefix('users')->group(function (): void {
            Route::get('', [UserController::class, 'index'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::patch('profile', [UserController::class, 'updateProfile']);
            Route::post('', [UserController::class, 'store'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::patch('{user}/activate', [UserController::class, 'activate'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::patch('{user}/approve', [UserController::class, 'approve'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::delete('{user}/reject', [UserController::class, 'reject'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::post('{user}/reset-password', [UserController::class, 'resetPassword'])
                ->middleware(EnsureRole::class . ':super_admin');
        });

        // Suppliers
        Route::prefix('suppliers')->group(function (): void {
            Route::get('', [\App\Http\Controllers\Api\SupplierController::class, 'index']);
            Route::post('', [\App\Http\Controllers\Api\SupplierController::class, 'store']);
            Route::get('{supplier}', [\App\Http\Controllers\Api\SupplierController::class, 'show']);
            Route::patch('{supplier}', [\App\Http\Controllers\Api\SupplierController::class, 'update']);
            Route::delete('{supplier}', [\App\Http\Controllers\Api\SupplierController::class, 'destroy']);
            Route::get('{supplier}/history', [\App\Http\Controllers\Api\SupplierController::class, 'history']);
        });

        // Stock management
        Route::prefix('stock')->group(function (): void {
            Route::get('summary', [\App\Http\Controllers\Api\StockController::class, 'summary']);
            Route::get('movements', [\App\Http\Controllers\Api\StockController::class, 'movements']);
        });

        // Dispatching
        Route::prefix('dispatches')->group(function (): void {
            Route::get('', [\App\Http\Controllers\Api\DispatchController::class, 'index']);
            Route::post('', [\App\Http\Controllers\Api\DispatchController::class, 'store']);
            Route::get('{dispatch}', [\App\Http\Controllers\Api\DispatchController::class, 'show']);
            Route::post('{dispatch}/approve', [\App\Http\Controllers\Api\DispatchController::class, 'approve'])
                ->middleware(\App\Http\Middleware\EnsureRole::class . ':super_admin,maintenance_admin');
            Route::post('{dispatch}/release', [\App\Http\Controllers\Api\DispatchController::class, 'release'])
                ->middleware(\App\Http\Middleware\EnsureRole::class . ':super_admin,maintenance_admin');
            Route::post('{dispatch}/cancel', [\App\Http\Controllers\Api\DispatchController::class, 'cancel'])
                ->middleware(\App\Http\Middleware\EnsureRole::class . ':super_admin,maintenance_admin');
            Route::get('{dispatch}/print', [\App\Http\Controllers\Api\DispatchController::class, 'print']);
        });

        Route::prefix('damage-reports')->group(function (): void {
            Route::get('', [DamageReportController::class, 'index']);
            Route::post('', [DamageReportController::class, 'store'])
                ->middleware(EnsureRole::class . ':super_admin,maintenance_admin,maintenance_staff');
            Route::get('{damageReport}', [DamageReportController::class, 'show']);
            Route::post('{damageReport}/status', [DamageReportController::class, 'updateStatus'])
                ->middleware(EnsureRole::class . ':super_admin,maintenance_admin,maintenance_staff');
            Route::get('{damageReport}/history', [DamageReportController::class, 'history']);
        });

        Route::prefix('repairs')->group(function (): void {
            Route::get('', [RepairController::class, 'index']);
            Route::post('', [RepairController::class, 'store'])
                ->middleware(EnsureRole::class . ':super_admin,maintenance_admin,maintenance_staff');
            Route::get('support/damage-reports', [RepairController::class, 'supportDamageReports']);
            Route::get('support/technicians', [RepairController::class, 'supportTechnicians']);
            Route::get('{repairRequest}', [RepairController::class, 'show']);
            Route::get('{repairRequest}/history', [RepairController::class, 'history']);
            Route::post('{repairRequest}/assign', [RepairController::class, 'assign'])
                ->middleware(EnsureRole::class . ':super_admin,maintenance_admin');
            Route::post('{repairRequest}/update', [RepairController::class, 'update'])
                ->middleware(EnsureRole::class . ':super_admin,maintenance_admin,maintenance_staff');
            Route::post('{repairRequest}/replacement', [RepairController::class, 'replacement'])
                ->middleware(EnsureRole::class . ':super_admin,maintenance_admin');
        });

        Route::prefix('activity-logs')->middleware(EnsureRole::class . ':super_admin,maintenance_admin')->group(function (): void {
            Route::get('', [ActivityLogController::class, 'index']);
            Route::get('support/options', [ActivityLogController::class, 'options']);
            Route::get('{activityLog}', [ActivityLogController::class, 'show']);
        });

        Route::apiResource('inventory-rooms', InventoryRoomController::class)
            ->except(['show'])
            ->parameters(['inventory-rooms' => 'id']);

        Route::prefix('deployment-tracking')->group(function (): void {
            Route::get('search', [DeploymentTrackingController::class, 'search']);
            Route::get('',       [DeploymentTrackingController::class, 'index']);
        });

        Route::get('replacement-tracking', [ReplacementTrackingController::class, 'index']);

        // Purchase receipts — search MUST precede apiResource to avoid {id} capture
        Route::get('purchase-receipts/search', [PurchaseReceiptController::class, 'search']);
        Route::apiResource('purchase-receipts', PurchaseReceiptController::class)
            ->except(['update', 'destroy'])
            ->parameters(['purchase-receipts' => 'id']);
        Route::post('purchase-receipts/{id}/items', [PurchaseReceiptController::class, 'addItem']);
        Route::post('purchase-receipts/{id}/post',  [PurchaseReceiptController::class, 'postReceipt']);

        // Inventory stock management
        Route::prefix('inventory-stock')->group(function (): void {
            Route::get('',                  [InventoryStockController::class, 'index']);
            Route::get('summary',           [InventoryStockController::class, 'summary']);
            Route::get('entries',           [InventoryStockController::class, 'listEntries']);
            Route::get('{id}/transactions', [InventoryStockController::class, 'transactions']);
            Route::put('{id}',              [InventoryStockController::class, 'update']);
            Route::delete('{id}',           [InventoryStockController::class, 'destroy']);
            Route::post('{id}/adjust',      [InventoryStockController::class, 'adjust']);
            Route::post('{id}/deploy',      [InventoryStockController::class, 'deploy']);
        });

        // Analytics and reporting endpoints (aggregates, trends, exports)
        Route::prefix('analytics')->group(function (): void {
            Route::get('options', [AnalyticsReportController::class, 'options']);
            Route::get('overview', [AnalyticsReportController::class, 'overview']);
            Route::get('top-requested', [AnalyticsReportController::class, 'topRequested']);
            Route::get('top-repaired', [AnalyticsReportController::class, 'topRepaired']);
            Route::get('monthly-comparison', [AnalyticsReportController::class, 'monthlyComparison']);
            Route::get('department-usage', [AnalyticsReportController::class, 'departmentUsage']);
            Route::get('semester-comparison', [AnalyticsReportController::class, 'semesterComparison']);
            Route::get('inventory-health', [AnalyticsReportController::class, 'inventoryHealth']);
            Route::get('inventory-summary', [AnalyticsReportController::class, 'inventorySummary']);
            Route::get('low-stock', [AnalyticsReportController::class, 'lowStock']);
            Route::get('damaged-items', [AnalyticsReportController::class, 'damagedItems']);
            Route::get('dispatch-report', [AnalyticsReportController::class, 'dispatchReport']);
            Route::get('repair-report', [AnalyticsReportController::class, 'repairReport']);
            Route::get('replacement-report', [AnalyticsReportController::class, 'replacementReport']);
            Route::get('semester-detail', [AnalyticsReportController::class, 'semesterDetail']);
        });
    });
});

