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
<body>
    <?php if ($user): ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <nav class="navbar navbar-dark">
        <div class="navbar-container">
            <div class="navbar-left">
                <a href="/School_Facility_Maintenance_System/frontend/pages/dashboard.php" class="navbar-brand">
                  
                </a>
            </div>

            <div class="navbar-right d-flex align-center gap-sm">
                <div class="nav-links">
                    <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php">Reports</a>
                    <a href="/School_Facility_Maintenance_System/frontend/pages/create-report.php">New Report</a>
                </div>

                <div class="nav-item notification-wrapper">
                    <button id="notificationBell" class="notification-bell" aria-label="Notifications">
                        <span class="bell-icon">🔔</span>
                        <span id="notificationCount" class="notification-count">0</span>
                    </button>
                    <div id="notificationDropdown" class="notification-dropdown">
                        <div class="notification-header">
                            <strong>Notifications</strong>
                            <button id="clearNotifications" class="clear-notifications">Mark all read</button>
                        </div>
                        <div id="notificationList" class="notification-list"></div>
                    </div>
                </div>

                <div class="user-profile" title="<?php echo htmlspecialchars($user['full_name']); ?>">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="avatar" class="avatar-img" />
                    <?php else: ?>
                        <div class="avatar-circle" data-user-avatar><?php echo strtoupper($user['full_name'][0] ?? 'U'); ?></div>
                    <?php endif; ?>
                </div>

              
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Logout is handled via server-side logout.php to ensure session is destroyed reliably -->
