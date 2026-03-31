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

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'School Facility Maintenance System Laravel API',
    ]);
});

Route::prefix('api')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
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
