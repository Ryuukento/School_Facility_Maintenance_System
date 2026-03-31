<?php
/**
 * FacilityFlow Sidebar Navigation
 * Reusable sidebar component for all pages of the School Facility Maintenance System
 */

// Get current page to set active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_settings_tab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'general';
$allowed_settings_tabs = ['general', 'account', 'notifications'];
if (!in_array($current_settings_tab, $allowed_settings_tabs, true)) {
    $current_settings_tab = 'general';
}
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

    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="logo-container">
            <!-- image logo: replace assets/images/logo.png with your custom logo file -->
            <img src="/School_Facility_Maintenance_System/frontend/assets/images/logo.png" alt="FacilityFlow Logo" class="sidebar-logo" />
            <h1 class="system-name">FacilityFlow</h1>
        </div>
    </div>

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

                $inventoryLink = '#inventory';
                if (!empty($user) && in_array(($user['role'] ?? ''), ['maintenance_admin', 'maintenance_staff'], true)) {
                    $inventoryLink = 'buildings-overview.php';
                }

                $showUserManagement = !empty($user) && (($user['role'] ?? '') === 'super_admin');
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
            <!-- Settings -->
            <li class="nav-item settings-item">
                <a href="#" class="nav-link settings-link <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>" data-page="settings" data-settings-toggle aria-expanded="false" aria-controls="settings-submenu">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1V3M12 21V23M4.22 4.22L5.64 5.64M18.36 18.36L19.78 19.78M1 12H3M21 12H23M4.22 19.78L5.64 18.36M18.36 5.64L19.78 4.22"></path>
                    </svg>
                    <span class="nav-text">Settings</span>
                    <span class="nav-caret" aria-hidden="true">▾</span>
                </a>
                <ul class="settings-submenu" id="settings-submenu">
                    <li class="nav-item">
                        <a href="settings.php?tab=general" class="nav-link settings-subitem <?php echo ($current_page === 'settings.php' && $current_settings_tab === 'general') ? 'active' : ''; ?>" data-page="settings-general">
                            <span class="nav-text">General</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php?tab=account" class="nav-link settings-subitem <?php echo ($current_page === 'settings.php' && $current_settings_tab === 'account') ? 'active' : ''; ?>" data-page="settings-account">
                            <span class="nav-text">Account</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php?tab=notifications" class="nav-link settings-subitem <?php echo ($current_page === 'settings.php' && $current_settings_tab === 'notifications') ? 'active' : ''; ?>" data-page="settings-notifications">
                            <span class="nav-text">Notifications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link logout-link logout-subitem" data-logout>
                            <span class="nav-text">Log Out</span>
                        </a>
                    </li>
                </ul>
            </li>
            
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer"></div>
    <style>
        /* logo styling for sidebar header image – make circular */
        .sidebar-header .logo-container img.sidebar-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            object-position: center;
            border-radius: 50%;
            margin: 0;
            display: block;
        }
    </style>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

