<?php
/**
 * FacilityFlow Sidebar Navigation
 * Reusable sidebar component for all pages of the School Facility Maintenance System
 */

// Get current page to set active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <span class="hamburger-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>

    <!-- Main Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-section-label"><span>Main</span></li>
            <!-- Dashboard -->
            <?php
                $dashLink = 'dashboard.php';
                if (!empty($user) && ($user['role'] === 'maintenance_admin')) {
                    $dashLink = 'maintenance-dashboard.php';
                } elseif (!empty($user) && ($user['role'] === 'maintenance_staff')) {
                    $dashLink = 'staff-dashboard.php';
                }

                $reportsLink = 'reports.php';
                if (!empty($user) && (($user['role'] ?? '') === 'maintenance_staff')) {
                    $reportsLink = 'maintenance-reports-list.php';
                }

                $inventoryLink = 'inventory.php';

                $showInventoryNav = in_array(($user['role'] ?? ''), ['super_admin', 'admin_maintenance', 'maintenance_admin', 'maintenance_staff'], true);
                $showUserManagement = !empty($user) && (($user['role'] ?? '') === 'super_admin');
                $showAuditLogs = !empty($user) && in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin', 'admin_maintenance'], true);
                $showNotificationsCenter = !empty($user);
            ?>
            <li class="nav-item">
                <a href="<?php echo $dashLink; ?>" class="nav-link <?php echo ($current_page === basename($dashLink)) ? 'active' : ''; ?>" data-page="dashboard">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <path d="M9 11H15V15H9Z"></path>
                        <path d="M3 9H9V15H3Z"></path>
                        <path d="M15 3V9"></path>
                        <path d="M3 15V21"></path>
                    </svg>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- All Reports -->
            <li class="nav-item">
                <a href="<?php echo $reportsLink; ?>" class="nav-link <?php echo ($current_page === basename($reportsLink)) ? 'active' : ''; ?>" data-page="reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6C4.9 2 4 2.9 4 4V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V8L14 2Z"></path>
                        <path d="M14 2V8H20"></path>
                        <line x1="8" y1="11" x2="16" y2="11"></line>
                        <line x1="8" y1="16" x2="16" y2="16"></line>
                    </svg>
                    <span class="nav-text">All Reports</span>
                </a>
            </li>

            <?php if ($showInventoryNav): ?>
            <!-- Inventory -->
            <li class="nav-item">
                <a href="<?php echo $inventoryLink; ?>" class="nav-link <?php echo ($current_page === basename($inventoryLink)) ? 'active' : ''; ?>" data-page="inventory">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 2C7.9 2 7 2.9 7 4V20C7 21.1 7.9 22 9 22H19C20.1 22 21 21.1 21 20V8L13 2H9Z"></path>
                        <path d="M13 2V8H21"></path>
                        <line x1="10" y1="11" x2="18" y2="11"></line>
                        <line x1="10" y1="15" x2="18" y2="15"></line>
                        <line x1="10" y1="19" x2="14" y2="19"></line>
                    </svg>
                    <span class="nav-text">Inventory</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($showUserManagement): ?>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" data-page="users">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21V19C16 16.79 14.21 15 12 15H6C3.79 15 2 16.79 2 19V21"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21V19C22 17.14 20.73 15.56 19 15.13"></path>
                        <path d="M16 3.13C17.73 3.56 19 5.14 19 7C19 8.86 17.73 10.44 16 10.87"></path>
                    </svg>
                    <span class="nav-text">User Management</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-section-label"><span>Account</span></li>
            <li class="nav-item">
                <a href="account.php" class="nav-link <?php echo ($current_page === 'account.php') ? 'active' : ''; ?>" data-page="account">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21V19C20 16.79 18.21 15 16 15H8C5.79 15 4 16.79 4 19V21"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="nav-text">Account</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link logout-link" data-logout>
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5A2 2 0 0 1 3 19V5A2 2 0 0 1 5 3H9"></path>
                        <path d="M16 17L21 12L16 7"></path>
                        <path d="M21 12H9"></path>
                    </svg>
                    <span class="nav-text">Log Out</span>
                </a>
            </li>
            
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <?php if (!empty($user)): ?>
        <div class="user-profile sidebar-user-profile" title="<?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>">
            <div class="user-avatar-wrap sidebar-user-avatar-wrap">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="avatar" class="avatar-img" id="header-user-avatar" />
                <?php else: ?>
                    <div class="avatar-circle" data-user-avatar id="header-user-avatar-fallback"><?php echo strtoupper($user['full_name'][0] ?? 'U'); ?></div>
                <?php endif; ?>
            </div>
            <div class="user-profile-meta sidebar-user-meta">
                <div class="user-name" id="header-user-name"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></div>
                <div class="user-title" id="header-user-title"><?php echo htmlspecialchars($userTitle ?? 'User'); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
        <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/sidebar.inline.css">
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>



