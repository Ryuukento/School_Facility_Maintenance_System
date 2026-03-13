<?php
require_once __DIR__ . '/../../backend/config/settings.php';

// Check if user is logged in (except for login page)
$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['index.php', 'login.php'];

if (!in_array($currentPage, $publicPages) && !isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$pageTitle = $pageTitle ?? 'SFMS';
$user = $_SESSION['user'] ?? null;
$roleTitleMap = [
    'super_admin' => 'Super Admin',
    'department_admin' => 'Department Admin',
    'maintenance_admin' => 'Maintenance Admin',
    'maintenance_staff' => 'Maintenance Staff',
    'user' => 'User'
];
$userTitle = $roleTitleMap[$user['role'] ?? ''] ?? 'User';

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
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/sidebar.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
</head>
<body data-user-role="<?php echo htmlspecialchars($user['role'] ?? ''); ?>">
    <?php if ($user): ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <nav class="navbar navbar-dark">
        <div class="navbar-container">
            <div class="navbar-left">
                <a href="/School_Facility_Maintenance_System/frontend/pages/dashboard.php" class="navbar-brand">
                  
                </a>
            </div>

            <div class="navbar-right d-flex align-center gap-sm">
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
                            <button id="clearNotifications" class="clear-notifications">Mark all read</button>
                        </div>
                        <div id="notificationList" class="notification-list">
                            <?php if (empty($initialNotifications)): ?>
                                <div class="notification-empty">No notifications</div>
                            <?php else: ?>
                                <?php foreach ($initialNotifications as $notif): ?>
                                    <div class="notification-item unread" onclick="NotificationManager.handleNotificationClick(<?php echo (int)$notif['notification_id']; ?>, <?php echo (int)($notif['report_id'] ?? 0); ?>)">
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
                    </div>
                </div>

                <div class="user-profile" title="<?php echo htmlspecialchars($user['full_name']); ?>">
                    <div class="user-avatar-wrap">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="avatar" class="avatar-img" id="header-user-avatar" />
                        <?php else: ?>
                            <div class="avatar-circle" data-user-avatar id="header-user-avatar-fallback"><?php echo strtoupper($user['full_name'][0] ?? 'U'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="user-profile-meta">
                        <div class="user-name" id="header-user-name"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></div>
                        <div class="user-title" id="header-user-title"><?php echo htmlspecialchars($userTitle); ?></div>
                    </div>
                </div>

              
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Logout is handled via server-side logout.php to ensure session is destroyed reliably -->
