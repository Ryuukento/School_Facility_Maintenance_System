<?php
/**
 * Frontend Root Index
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

require_once __DIR__ . '/../../backend/config/settings.php';

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    if ($role === 'maintenance_admin') {
        header('Location: ' . public_url('/frontend/pages/maintenance-dashboard.php'), true, 302);
    } elseif ($role === 'maintenance_staff') {
        header('Location: ' . public_url('/frontend/pages/staff-dashboard.php'), true, 302);
    } else {
        header('Location: ' . public_url('/frontend/pages/dashboard.php'), true, 302);
    }
} else {
    header('Location: ' . public_url('/frontend/pages/index.php'), true, 302);
}
exit;
