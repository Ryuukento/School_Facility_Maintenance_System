<?php
/**
 * Frontend Root Index
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

ob_start();

if (!empty($_SESSION['user'])) {
    ob_end_clean();
    $role = $_SESSION['user']['role'] ?? '';
    if ($role === 'maintenance_admin') {
        header('Location: /School_Facility_Maintenance_System/frontend/pages/maintenance-dashboard.php', true, 302);
    } else {
        header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php', true, 302);
    }
    exit;
} else {
    ob_end_clean();
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php', true, 302);
    exit;
}
