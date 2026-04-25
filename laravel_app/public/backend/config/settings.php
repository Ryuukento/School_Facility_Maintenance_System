<?php
/**
 * Application Settings
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Application settings
define('APP_NAME', 'School Facility Maintenance System');
define('APP_VERSION', '1.0.0');

function sfms_detect_app_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        ? 'https'
        : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = '';

    if (strpos($scriptName, '/frontend/') !== false) {
        $basePath = rtrim(substr($scriptName, 0, strpos($scriptName, '/frontend/')), '/');
    } elseif (strpos($scriptName, '/backend/') !== false) {
        $basePath = rtrim(substr($scriptName, 0, strpos($scriptName, '/backend/')), '/');
    } else {
        $basePath = '/School_Facility_Maintenance_System';
    }

    if ($host !== '') {
        return $scheme . '://' . $host . $basePath;
    }

    return 'http://localhost/School_Facility_Maintenance_System';
}

define('APP_URL', sfms_detect_app_url());

$appUrlPath = parse_url(APP_URL, PHP_URL_PATH);
$appUrlPath = is_string($appUrlPath) ? trim($appUrlPath) : '';
$appUrlPath = $appUrlPath === '/' ? '' : rtrim($appUrlPath, '/');
define('APP_BASE_PATH', $appUrlPath);

// Keep legacy absolute path compatibility for existing static frontend links.
define('APP_PUBLIC_PATH', APP_BASE_PATH . '/laravel_app/public');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Security - Password hashing
define('BCRYPT_COST', 12);
define('PASSWORD_MIN_LENGTH', 8);

// Status constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_SUSPENDED', 'suspended');
define('STATUS_PENDING', 'pending');

// Role constants
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_DEPARTMENT_ADMIN', 'department_admin');
define('ROLE_MAINTENANCE_ADMIN', 'maintenance_admin');
define('ROLE_MAINTENANCE_STAFF', 'maintenance_staff');
define('ROLE_USER', 'user');

// Priority constants
define('PRIORITY_LOW', 'low');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_HIGH', 'high');
define('PRIORITY_URGENT', 'urgent');
define('PRIORITY_CRITICAL', 'critical');

// Timezone
date_default_timezone_set('Asia/Manila');

// Session settings
define('SESSION_NAME', 'SFMS_SESSION');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_REGENERATE_INTERVAL', 600); // 10 minutes

// Debug mode
define('APP_DEBUG', true);

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!function_exists('sfms_public_base_path')) {
    function sfms_public_base_path() {
        if (PHP_SAPI === 'cli-server') {
            return '';
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (strpos($scriptName, '/frontend/') !== false) {
            return rtrim(substr($scriptName, 0, strpos($scriptName, '/frontend/')), '/');
        }

        if (strpos($scriptName, '/backend/') !== false) {
            return rtrim(substr($scriptName, 0, strpos($scriptName, '/backend/')), '/');
        }

        return APP_BASE_PATH;
    }
}

if (!function_exists('public_url')) {
    function public_url($path) {
        $basePath = sfms_public_base_path();
        return ($basePath !== '' ? $basePath : '') . '/' . ltrim((string)$path, '/');
    }
}
