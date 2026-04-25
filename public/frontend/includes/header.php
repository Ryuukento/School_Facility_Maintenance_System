<?php
require_once __DIR__ . '/../../backend/config/settings.php';

$publicBasePath = function_exists('sfms_public_base_path') ? sfms_public_base_path() : APP_PUBLIC_PATH;

if (!function_exists('public_url')) {
    function public_url($path) {
        global $publicBasePath;
        return ($publicBasePath !== '' ? $publicBasePath : '') . '/' . ltrim((string)$path, '/');
    }
}

if (!defined('SFMS_URL_REWRITE_ACTIVE')) {
    define('SFMS_URL_REWRITE_ACTIVE', true);
    $legacyPrefix = '/School_Facility_Maintenance_System';
    ob_start(function ($buffer) use ($publicBasePath, $legacyPrefix) {
        if ($publicBasePath === $legacyPrefix) {
            return $buffer;
        }

        return str_replace($legacyPrefix, $publicBasePath, $buffer);
    });
}

// Check if user is logged in (except for login page)
$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['index.php', 'login.php'];

if (!in_array($currentPage, $publicPages) && !isset($_SESSION['user'])) {
    header('Location: ' . public_url('/frontend/pages/index.php'));
    exit;
}

$pageTitle = $pageTitle ?? 'SFMS';
$user = $_SESSION['user'] ?? null;
$isForceProfileUpdate = !empty($user['force_profile_update']);

if ($user && $isForceProfileUpdate && !in_array($currentPage, ['account.php', 'logout.php'], true)) {
    header('Location: ' . public_url('/frontend/pages/account.php?setup=1'));
    exit;
}

$roleTitleMap = [
    'super_admin' => 'Super Admin',
    'department_admin' => 'Department Admin',
    'maintenance_admin' => 'Maintenance Admin',
    'maintenance_staff' => 'Maintenance Staff',
    'admin_maintenance' => 'Maintenance Admin',
    'eelab_staff' => 'Maintenance Staff',
    'maintenance_personnel' => 'Maintenance Staff',
    '' => 'Maintenance Staff',
    'user' => 'User'
];
$userTitle = $roleTitleMap[$user['role'] ?? ''] ?? 'User';

$brandLink = public_url('/frontend/pages/dashboard.php');
if (!empty($user['role']) && $user['role'] === 'maintenance_admin') {
    $brandLink = public_url('/frontend/pages/maintenance-dashboard.php');
} elseif (!empty($user['role']) && $user['role'] === 'maintenance_staff') {
    $brandLink = public_url('/frontend/pages/staff-dashboard.php');
}

$initialNotifications = [];
$initialNotificationCount = 0;

if ($user && !empty($user['user_id'])) {
    try {
        require_once __DIR__ . '/../../backend/config/database.php';
        require_once __DIR__ . '/../../backend/models/Notification.php';

        $headerPdo = getDBConnection();
        $notificationModel = new Notification($headerPdo);
        $initialNotifications = $notificationModel->getUnread((int)$user['user_id'], 10);
        $initialNotificationCount = count($initialNotifications);
    } catch (Throwable $e) {
        $initialNotifications = [];
        $initialNotificationCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('sfms_settings_theme') || localStorage.getItem('sfms_theme_mode') || 'dark';
                var mode = (storedTheme === 'light' || storedTheme === 'dark') ? storedTheme : 'dark';
                var fontSizeMode = localStorage.getItem('sfms_settings_font_size') || 'medium';
                var resolved = mode;
                var root = document.documentElement;
                var sizeScaleMap = { small: 0.92, medium: 1, large: 1.12 };
                var safeFontSizeMode = Object.prototype.hasOwnProperty.call(sizeScaleMap, fontSizeMode) ? fontSizeMode : 'medium';
                var safeScale = sizeScaleMap[safeFontSizeMode];

                root.setAttribute('data-theme-mode', mode);
                root.setAttribute('data-theme-resolved', resolved);
                root.setAttribute('data-font-size-mode', safeFontSizeMode);
                root.style.colorScheme = resolved === 'dark' ? 'dark' : 'light';
                root.style.setProperty('--ui-font-scale', String(safeScale));
                root.style.setProperty('--ui-zoom', '1');
            } catch (error) {
                // Ignore localStorage access errors and keep default light theme.
            }
        })();
    </script>
    <script>
        window.SFMS_BASE_PATH = <?php echo json_encode($publicBasePath); ?>;
        window.SFMS_PUBLIC_URL = window.SFMS_PUBLIC_URL || function (path) {
            const basePath = String(window.SFMS_BASE_PATH || '').replace(/\/$/, '');
            const normalizedPath = '/' + String(path || '').replace(/^\/+/, '');
            return `${basePath}${normalizedPath}`;
        };
        window.SFMS_BACKEND_API_BASE = window.SFMS_PUBLIC_URL('/backend/api');
        window.SFMS_FRONTEND_BASE = window.SFMS_PUBLIC_URL('/frontend');
    </script>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/sidebar.css?v=20260415-4')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/styles.css?v=20260419-4')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/layout.css?v=20260415-3')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/color-scheme.css?v=20260415-3')); ?>">
</head>
<body data-user-role="<?php echo htmlspecialchars($user['role'] ?? ''); ?>" data-force-profile-setup="<?php echo $isForceProfileUpdate ? '1' : '0'; ?>">
    <?php if ($user): ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <nav class="navbar navbar-dark">
        <div class="navbar-container">
            <div class="navbar-left">
                <button id="sidebarToggleMobile" class="sidebar-toggle-mobile" type="button" aria-label="Toggle navigation menu" title="Toggle navigation menu">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                        <path d="M4 6H20M4 12H20M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
                <div class="navbar-brand" aria-label="PHILCST Centralized School Facility Maintenance Reporting System">
                    <img src="<?php echo htmlspecialchars(public_url('/frontend/assets/images/logo.png')); ?>" alt="PHILCST Centralized School Facility Maintenance Reporting System" class="navbar-brand-logo" />
                    <span class="navbar-brand-text">
                        <span class="navbar-brand-title">PHILCST CENTRALIZED SCHOOL FACILITY MAINTENANCE REPORTING SYSTEM</span>
                        <span class="navbar-brand-school">Philippine College of Science and Technology</span>
                    </span>
                </div>
            </div>

            <div class="navbar-right d-flex align-center gap-sm">
                <div id="networkSignalIndicator" class="network-signal-indicator" role="status" aria-live="polite" aria-atomic="true">
                    <span class="network-signal-logo-wrap" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars(public_url('/frontend/assets/images/logo.png')); ?>" alt="" class="network-signal-logo" />
                        <span class="network-signal-ring"></span>
                    </span>
                    <span id="networkSignalText" class="network-signal-text">Weak signal</span>
                </div>
                <div class="nav-item theme-toggle-wrapper">
                    <button id="themeToggle" class="theme-toggle-button" type="button" data-theme-toggle aria-label="Toggle light and dark mode" title="Toggle light and dark mode">
                        <svg class="theme-toggle-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7.5 7.5 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <div class="nav-item notification-wrapper">
                    <button id="notificationBell" class="notification-bell" aria-label="Notifications">
                        <span class="bell-icon">🔔</span>
                        <span id="notificationCount" class="notification-count" style="display: <?php echo $initialNotificationCount > 0 ? 'flex' : 'none'; ?>;">
                            <?php echo $initialNotificationCount > 9 ? '9+' : (int)$initialNotificationCount; ?>
                        </span>
                    </button>
                    <div id="notificationDropdown" class="notification-dropdown">
                        <div class="notification-header">
                            <strong>Notifications</strong>
                            <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/notifications-center.php')); ?>" class="notification-see-all">See all</a>
                        </div>
                        <div class="notification-tabs">
                            <button class="notification-tab active" data-filter="all">All</button>
                            <button class="notification-tab" data-filter="unread">Unread</button>
                            <button id="clearNotifications" class="clear-notifications">Mark all read</button>
                        </div>
                        <div id="notificationList" class="notification-list">
                            <?php if (empty($initialNotifications)): ?>
                                <div class="notification-empty">No notifications</div>
                            <?php else: ?>
                                <?php foreach ($initialNotifications as $notif): ?>
                                    <div class="notification-item unread" onclick="NotificationManager.handleNotificationClick(<?php echo (int)$notif['notification_id']; ?>, <?php echo (int)($notif['report_id'] ?? 0); ?>, this)">
                                        <div class="notification-icon">📋</div>
                                        <div class="notification-content">
                                            <div class="notification-title"><?php echo htmlspecialchars($notif['title'] ?? 'Notification'); ?></div>
                                            <div class="notification-message"><?php echo htmlspecialchars($notif['message'] ?? ''); ?></div>
                                            <div class="notification-time"><?php echo htmlspecialchars($notif['created_at'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/notifications-center.php')); ?>" class="notification-footer-btn">See previous notifications</a>
                        </div>
                    </div>
                </div>
                <div id="headerDashboardKicker" class="header-dashboard-kicker" aria-live="polite"></div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Logout is handled via server-side logout.php to ensure session is destroyed reliably -->
