<?php
/**
 * FacilityFlow Sidebar Navigation
 * Reusable sidebar component for all pages of the School Facility Maintenance System
 */

// Get current page to set active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-shell">
        <div class="sidebar-brand" aria-label="FacilityFlow workspace">
            <div class="sidebar-brand-mark">F</div>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-title">FacilityFlow</span>
                <span class="sidebar-brand-subtitle">Operations</span>
            </div>
        </div>

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

                $showInventoryNav = in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin', 'maintenance_staff'], true);
                $showUserManagement = !empty($user) && (($user['role'] ?? '') === 'super_admin');
                $showAuditLogs = !empty($user) && in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true);
                $showCreateReport = in_array(($user['role'] ?? ''), ['maintenance_staff', 'maintenance_admin', 'super_admin'], true);
                $showNotificationsCenter = !empty($user);
            ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/' . $dashLink)); ?>" class="nav-link <?php echo ($current_page === basename($dashLink)) ? 'active' : ''; ?>" data-page="dashboard">
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
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/' . $reportsLink)); ?>" class="nav-link <?php echo in_array($current_page, ['reports.php', 'maintenance-reports-list.php', 'maintenance-report-detail.php'], true) ? 'active' : ''; ?>" data-page="reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6C4.9 2 4 2.9 4 4V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V8L14 2Z"></path>
                        <path d="M14 2V8H20"></path>
                        <line x1="8" y1="11" x2="16" y2="11"></line>
                        <line x1="8" y1="16" x2="16" y2="16"></line>
                    </svg>
                    <span class="nav-text">All Reports</span>
                </a>
            </li>

            <?php if ($showCreateReport): ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/create-report.php')); ?>" class="nav-link <?php echo ($current_page === 'create-report.php') ? 'active' : ''; ?>" data-page="create-report">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    <span class="nav-text">Create Report</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($showInventoryNav): ?>
            <!-- Inventory -->
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/' . $inventoryLink)); ?>" class="nav-link <?php echo ($current_page === basename($inventoryLink)) ? 'active' : ''; ?>" data-page="inventory">
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

            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/damage-reports.php')); ?>" class="nav-link <?php echo in_array($current_page, ['damage-reports.php', 'damage-report-create.php', 'damage-report-detail.php', 'damage-report-update.php'], true) ? 'active' : ''; ?>" data-page="damage-reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3L3 7V13C3 18 6.6 22.4 12 23C17.4 22.4 21 18 21 13V7L12 3Z"></path>
                        <path d="M9.5 9.5L14.5 14.5"></path>
                        <path d="M14.5 9.5L9.5 14.5"></path>
                    </svg>
                    <span class="nav-text">Damage Reports</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/repair-requests.php')); ?>" class="nav-link <?php echo in_array($current_page, ['repair-requests.php', 'repair-detail.php', 'repair-assignment.php', 'repair-update.php', 'replacement-request.php'], true) ? 'active' : ''; ?>" data-page="repairs">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 7L17 10L9 18L6 18L6 15L14 7Z"></path>
                        <path d="M16 5L19 8"></path>
                        <path d="M3 21H21"></path>
                    </svg>
                    <span class="nav-text">Repair Requests</span>
                </a>
            </li>

            <?php if (in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true)): ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/dispatches')); ?>"
                   class="nav-link <?php echo in_array($current_page, ['dispatches.php', 'dispatch-detail.php', 'dispatch-create.php'], true) ? 'active' : ''; ?>"
                   data-page="dispatches">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12H19"></path>
                        <path d="M12 5L19 12L12 19"></path>
                    </svg>
                    <span class="nav-text">Dispatches</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true)): ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/inventory-reports')); ?>"
                   class="nav-link <?php echo ($current_page === 'inventory-reports.php') ? 'active' : ''; ?>"
                   data-page="inventory-reports">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <line x1="8" y1="8" x2="16" y2="8"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                        <line x1="8" y1="16" x2="12" y2="16"></line>
                    </svg>
                    <span class="nav-text">Inventory Reports</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true)): ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/purchase-receipts')); ?>"
                   class="nav-link <?php echo ($current_page === 'purchase-receipts.php') ? 'active' : ''; ?>"
                   data-page="purchase-receipts">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7C5.9 5 5 5.9 5 7V19C5 20.1 5.9 21 7 21H17C18.1 21 19 20.1 19 19V7C19 5.9 18.1 5 17 5H15"></path>
                        <rect x="9" y="3" width="6" height="4" rx="1"></rect>
                        <line x1="9" y1="12" x2="15" y2="12"></line>
                        <line x1="9" y1="16" x2="13" y2="16"></line>
                    </svg>
                    <span class="nav-text">Purchase Receipts</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true)): ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/deployment-tracking')); ?>"
                   class="nav-link <?php echo ($current_page === 'deployment-tracking.php') ? 'active' : ''; ?>"
                   data-page="deployment-tracking">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 2v3M12 19v3M2 12h3M19 12h3"></path>
                        <path d="M5.64 5.64l2.12 2.12M16.24 16.24l2.12 2.12M5.64 18.36l2.12-2.12M16.24 7.76l2.12-2.12"></path>
                    </svg>
                    <span class="nav-text">Deployment Tracking</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true)): ?>
            <li class="nav-section-label"><span>Analytics</span></li>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/analytics')); ?>"
                   class="nav-link <?php echo ($current_page === 'analytics-dashboard.php') ? 'active' : ''; ?>"
                   data-page="analytics">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span class="nav-text">Analytics Dashboard</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($showUserManagement): ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/users.php')); ?>" class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" data-page="users">
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

            <?php if ($showAuditLogs): ?>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/activity-log.php')); ?>" class="nav-link <?php echo in_array($current_page, ['activity-log.php', 'activity-log-detail.php'], true) ? 'active' : ''; ?>" data-page="activity-logs">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"></path>
                        <path d="M6.5 2H20V22H6.5A2.5 2.5 0 0 1 4 19.5V4.5A2.5 2.5 0 0 1 6.5 2Z"></path>
                        <path d="M8 7H16"></path>
                        <path d="M8 11H16"></path>
                        <path d="M8 15H13"></path>
                    </svg>
                    <span class="nav-text">Activity Logs</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-section-label"><span>Account</span></li>
            <li class="nav-item">
                <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/account.php')); ?>" class="nav-link <?php echo ($current_page === 'account.php') ? 'active' : ''; ?>" data-page="account">
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
        <link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/sidebar.inline.css')); ?>">
    </div>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>



