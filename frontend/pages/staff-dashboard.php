<?php
/**
 * Maintenance Staff Dashboard
 */
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];

// Sync role from database so stale sessions don't keep admins on staff dashboard.
require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();

try {
    $roleStmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
    $roleStmt->execute([(int)($user['user_id'] ?? 0)]);
    $actualRole = strtolower(trim((string)$roleStmt->fetchColumn()));

    if ($actualRole !== '') {
        $_SESSION['user']['role'] = $actualRole;
        $_SESSION['role'] = $actualRole;
        $user['role'] = $actualRole;
    }
} catch (Throwable $e) {
    // Keep session role if role sync fails.
}

if (($user['role'] ?? '') !== 'maintenance_staff') {
    if (($user['role'] ?? '') === 'maintenance_admin' || ($user['role'] ?? '') === 'super_admin') {
        header('Location: /School_Facility_Maintenance_System/frontend/pages/maintenance-dashboard.php');
        exit;
    }

    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container staff-dashboard-page">
    <div class="page-header mb-lg">
        <h1 style="margin: 0;">Maintenance Staff Dashboard</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-card-total">
            <div class="stat-content">
                <p class="stat-label">My Reports</p>
                <h3 class="stat-value" id="my-reports">0</h3>
                <p class="stat-meta text-muted">Reports created by you</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">📄</div>
        </div>

        <div class="stat-card stat-card-pending">
            <div class="stat-content">
                <p class="stat-label">Pending</p>
                <h3 class="stat-value" id="my-pending">0</h3>
                <p class="stat-meta text-muted">Need action</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">⏳</div>
        </div>

        <div class="stat-card stat-card-progress">
            <div class="stat-content">
                <p class="stat-label">In Progress</p>
                <h3 class="stat-value" id="my-in-progress">0</h3>
                <p class="stat-meta text-muted">Currently working</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">🔧</div>
        </div>

        <div class="stat-card stat-card-completed">
            <div class="stat-content">
                <p class="stat-label">Completed</p>
                <h3 class="stat-value" id="my-completed">0</h3>
                <p class="stat-meta text-muted">Finished reports</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">✅</div>
        </div>
    </div>

</main>

<style>
.staff-dashboard-page {
    width: calc(100% - var(--sidebar-width));
    max-width: calc(100% - var(--sidebar-width));
    margin-left: var(--sidebar-width);
    margin-right: 0;
    margin-top: 20px;
    padding-left: 24px;
    padding-right: 24px;
}

.navbar .navbar-container {
    max-width: none;
    margin: 0;
    padding-left: 24px;
    padding-right: 24px;
}

#sidebar.collapsed ~ main.staff-dashboard-page {
    width: calc(100% - var(--sidebar-width-collapsed));
    max-width: calc(100% - var(--sidebar-width-collapsed));
    margin-left: var(--sidebar-width-collapsed);
}

@media (max-width: 992px) {
    .staff-dashboard-page {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
    }
}

@media (max-width: 640px) {
    .navbar .navbar-container,
    .staff-dashboard-page {
        padding-left: 14px;
        padding-right: 14px;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
async function loadStaffDashboardData() {
    try {
        const response = await fetch('/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php?action=list&per_page=200');
        const data = await response.json();

        if (!data.success || !data.data || !Array.isArray(data.data.reports)) {
            return;
        }

        const reports = data.data.reports;
        const pending = reports.filter(r => r.status === 'submitted' || r.status === 'assigned').length;
        const inProgress = reports.filter(r => r.status === 'in_progress').length;
        const completed = reports.filter(r => r.status === 'completed' || r.status === 'closed').length;

        document.getElementById('my-reports').textContent = reports.length;
        document.getElementById('my-pending').textContent = pending;
        document.getElementById('my-in-progress').textContent = inProgress;
        document.getElementById('my-completed').textContent = completed;

    } catch (error) {
        console.error('Failed to load staff dashboard data', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadStaffDashboardData();
});
</script>

</body>
</html>