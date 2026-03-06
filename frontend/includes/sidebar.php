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
            <!-- Dashboard -->
            <?php
                $dashLink = 'dashboard.php';
                if (!empty($user) && ($user['role'] === 'maintenance_admin')) {
                    $dashLink = 'maintenance-dashboard.php';
                }
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
                <a href="reports.php" class="nav-link <?php echo ($current_page === 'reports.php') ? 'active' : ''; ?>" data-page="reports">
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
                <a href="#inventory" class="nav-link" data-page="inventory">
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

            <!-- User Management -->
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" data-page="users">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M4 20C4 15.6 7.6 12 12 12C16.4 12 20 15.6 20 20"></path>
                        <circle cx="18" cy="18" r="2" opacity="0.6"></circle>
                    </svg>
                    <span class="nav-text">User Management</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <ul class="nav-menu">
            <!-- Settings -->
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>" data-page="settings">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1V3M12 21V23M4.22 4.22L5.64 5.64M18.36 18.36L19.78 19.78M1 12H3M21 12H23M4.22 19.78L5.64 18.36M18.36 5.64L19.78 4.22"></path>
                    </svg>
                    <span class="nav-text">Settings</span>
                </a>
            </li>

            <!-- Logout -->
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H9"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <style>
        /* logo styling for sidebar header image – make circular */
        .sidebar-header .logo-container img.sidebar-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
        }
    </style>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

