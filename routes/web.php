<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\LegacyBridgeController;
use App\Http\Controllers\Api\ReportController;
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
    Route::get('/replacement-tracking', fn() => redirect('/frontend/pages/replacement-tracking.php'))->name('inventory.replacement-tracking');
    Route::get('/settings', fn() => redirect('/frontend/pages/settings.php'))->name('settings');
    Route::get('/maintenance-dashboard', fn() => redirect('/frontend/pages/maintenance-dashboard.php'))->name('maintenance.dashboard');
    Route::get('/maintenance-reports', fn() => redirect('/frontend/pages/maintenance-reports-list.php'))->name('maintenance.reports.index');
    Route::get('/maintenance-reports/{id}', fn($id) => redirect('/frontend/pages/maintenance-report-detail.php?id=' . (int) $id))->name('maintenance.reports.show');
    Route::get('/staff-dashboard', fn() => redirect('/frontend/pages/staff-dashboard.php'))->name('staff.dashboard');
    Route::get('/super-admin-dashboard', fn() => redirect('/frontend/pages/super-admin-dashboard.php'))->name('superadmin.dashboard');
    Route::get('/activity-log', fn() => redirect('/frontend/pages/activity-log.php'))->name('activity.log');
    Route::get('/analytics', fn() => redirect('/frontend/pages/analytics.php'))->name('analytics');
    Route::get('/buildings-overview', fn() => redirect('/frontend/pages/buildings-overview.php'))->name('buildings.overview');
    Route::get('/inventory-transactions', fn() => redirect('/frontend/pages/inventory-transactions.php'))->name('inventory.transactions');
    
    // Logout route (web)
    Route::get('/logout', function () {
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
    return redirect('/public/' . ltrim($path, '/'));
})->where('path', '.*');

// Keep the new /public-prefixed path working consistently as well.
Route::get('/School_Facility_Maintenance_System/{path}', function (string $path) {
    return redirect('/' . ltrim($path, '/'));
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
        Route::get('reports', [ReportController::class, 'index']);
        Route::post('reports', [ReportController::class, 'store']);
        Route::get('reports/{report}', [ReportController::class, 'show']);
        Route::patch('reports/{report}', [ReportController::class, 'update']);

        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('items', [ItemController::class, 'index']);

        Route::prefix('users')->group(function (): void {
            Route::get('', [UserController::class, 'index']);
            Route::patch('{user}/deactivate', [UserController::class, 'deactivate'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::patch('{user}/activate', [UserController::class, 'activate'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::patch('{user}/approve', [UserController::class, 'approve'])
                ->middleware(EnsureRole::class . ':super_admin');
            Route::delete('{user}/reject', [UserController::class, 'reject'])
                ->middleware(EnsureRole::class . ':super_admin');
        });
    });
});

Route::prefix('backend/api')->group(function (): void {
    Route::match(['get', 'post'], 'auth.php', [LegacyBridgeController::class, 'auth']);
    Route::match(['get', 'post'], 'reports-api.php', [LegacyBridgeController::class, 'reports'])
        ->middleware(EnsureApiAuthenticated::class);
    Route::match(['get', 'post'], 'maintenance-dashboard-api.php', [LegacyBridgeController::class, 'maintenanceDashboard'])
        ->middleware(EnsureApiAuthenticated::class);
    Route::match(['get', 'post'], 'users-api.php', [LegacyBridgeController::class, 'users'])
        ->middleware([EnsureApiAuthenticated::class, EnsureRole::class . ':super_admin']);
});
