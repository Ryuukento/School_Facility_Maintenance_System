<?php
// Server-side logout helper — destroys session and redirects to login
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Optionally log activity by calling backend API internally (skipped here)

// Clear session data and destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();

// Clear any client-side storage redirect target may rely on
// (client JS will also clear localStorage on login/logout flows)

header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/index.php');
exit;
