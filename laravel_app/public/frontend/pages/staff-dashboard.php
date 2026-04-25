<?php
/**
 * Maintenance Staff Dashboard
 */
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/index.php');
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
        header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/maintenance-dashboard.php');
        exit;
    }

    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/dashboard.php');
    exit;
}

$assignedReportsCount = 0;
$buildingsCount = 0;
$totalReportsCount = 0;

try {
    $assignedStmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_reports WHERE assigned_to = ?");
    $assignedStmt->execute([(int)($user['user_id'] ?? 0)]);
    $assignedReportsCount = (int)$assignedStmt->fetchColumn();

    $buildingsStmt = $pdo->query("SELECT COUNT(*) FROM buildings");
    $buildingsCount = (int)$buildingsStmt->fetchColumn();

    $totalStmt = $pdo->query("SELECT COUNT(*) FROM maintenance_reports");
    $totalReportsCount = (int)$totalStmt->fetchColumn();
} catch (Throwable $e) {
    // Keep dashboard usable even if queries fail.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/maintenance-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container staff-dashboard-page">
    <div class="stats-grid">
        <div class="stat-card stat-card-total stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php?status_group=assigned_to_me" aria-label="Open assigned reports">
            <div class="stat-content">
                <p class="stat-label">Assigned to you</p>
                <h3 class="stat-value" id="my-assigned-reports"><?php echo (int)$assignedReportsCount; ?></h3>
                <p class="stat-meta text-muted">Reports waiting on your action</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">📄</div>
        </div>

        <div class="stat-card stat-card-pending stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/buildings-overview.php" aria-label="Open buildings overview">
            <div class="stat-content">
                <p class="stat-label">Buildings overview</p>
                <h3 class="stat-value" id="my-buildings"><?php echo (int)$buildingsCount; ?></h3>
                <p class="stat-meta text-muted">Tracked buildings in the system</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">🏢</div>
        </div>

        <div class="stat-card stat-card-total stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php" aria-label="Open all reports">
            <div class="stat-content">
                <p class="stat-label">Total Reports</p>
                <h3 class="stat-value" id="total-reports"><?php echo (int)$totalReportsCount; ?></h3>
                <p class="stat-meta text-muted">All reports in system</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">📊</div>
        </div>

        <div class="stat-card stat-card-pending stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php?status=submitted" aria-label="Open pending reports">
            <div class="stat-content">
                <p class="stat-label">Pending</p>
                <h3 class="stat-value" id="my-pending">0</h3>
                <p class="stat-meta text-muted">Need action</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">⏳</div>
        </div>

        <div class="stat-card stat-card-progress stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php?status=in_progress" aria-label="Open in progress reports">
            <div class="stat-content">
                <p class="stat-label">In Progress</p>
                <h3 class="stat-value" id="my-in-progress">0</h3>
                <p class="stat-meta text-muted">Currently working</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">🔧</div>
        </div>

        <div class="stat-card stat-card-completed stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php?status=completed" aria-label="Open completed reports">
            <div class="stat-content">
                <p class="stat-label">Completed</p>
                <h3 class="stat-value" id="my-completed">0</h3>
                <p class="stat-meta text-muted">Finished reports</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">✅</div>
        </div>
    </div>

    <div class="charts-section">
        <div class="card chart-card status-chart-card">
            <div class="card-header">
                <div class="status-chart-head-row">
                    <h2>Reports by Status</h2>
                    <span class="status-live-badge">Live</span>
                </div>
                <p class="text-muted mb-0">Current distribution of report statuses.</p>
            </div>
            <div class="card-body status-chart-body">
                <canvas id="statusChart" class="chart-canvas"></canvas>
            </div>
        </div>

        <div class="card chart-card priority-chart-card">
            <div class="card-header">
                <h2>Reports by Priority</h2>
                <p class="text-muted mb-0">Priority levels across assigned reports.</p>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" class="chart-canvas"></canvas>
            </div>
        </div>
    </div>

</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/staff-dashboard.inline.css?v=20260419-1">

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
let staffStatusChart = null;
let staffPriorityChart = null;

async function loadStaffDashboardData() {
    try {
        const response = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/maintenance-reports-api.php?action=list&per_page=200');
        const data = await response.json();

        if (!data.success || !data.data || !Array.isArray(data.data.reports)) {
            return;
        }

        const reports = data.data.reports;
        const pending = reports.filter(r => r.status === 'submitted' || r.status === 'assigned').length;
        const inProgress = reports.filter(r => r.status === 'in_progress').length;
        const completed = reports.filter(r => r.status === 'completed' || r.status === 'closed').length;

        document.getElementById('my-pending').textContent = pending;
        document.getElementById('my-in-progress').textContent = inProgress;
        document.getElementById('my-completed').textContent = completed;

    } catch (error) {
        console.error('Failed to load staff dashboard data', error);
    }
}

async function loadStaffChartData() {
    try {
        const response = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/maintenance-dashboard-api.php?action=charts');
        const chartData = await response.json();

        if (!chartData.success || !chartData.data) {
            return;
        }

        initializeStaffCharts(chartData.data);
    } catch (error) {
        console.error('Failed to load staff chart data', error);
    }
}

function initializeStaffCharts(data) {
    const isLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
    const chartPrimaryText = isLightMode ? '#111827' : '#f8fafc';
    const chartMutedText = isLightMode ? '#374151' : '#94a3b8';
    const chartGridY = isLightMode ? 'rgba(17, 24, 39, 0.12)' : 'rgba(148, 163, 184, 0.35)';
    const chartGridX = isLightMode ? 'rgba(17, 24, 39, 0.08)' : 'rgba(148, 163, 184, 0.25)';
    const doughnutBorder = isLightMode ? '#ffffff' : '#0f172a';

    const statusLabelsRaw = Array.isArray(data?.status_data?.labels) ? data.status_data.labels : [];
    const statusValuesRaw = Array.isArray(data?.status_data?.values) ? data.status_data.values : [];
    const statusTotals = { submitted: 0, in_progress: 0, completed: 0 };

    statusLabelsRaw.forEach((label, index) => {
        const normalized = String(label || '').trim().toLowerCase().replace(/\s+/g, '_');
        const value = Number(statusValuesRaw[index] || 0);

        if (normalized === 'submitted' || normalized === 'assigned' || normalized === 'draft') {
            statusTotals.submitted += value;
            return;
        }

        if (normalized === 'in_progress') {
            statusTotals.in_progress += value;
            return;
        }

        if (normalized === 'completed' || normalized === 'closed') {
            statusTotals.completed += value;
        }
    });

    const statusDataset = [statusTotals.submitted, statusTotals.in_progress, statusTotals.completed];
    const statusTotal = statusDataset.reduce((sum, value) => sum + value, 0);

    const statusCenterTextPlugin = {
        id: 'statusCenterTextPlugin',
        beforeDraw(chart) {
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || !meta.data.length) {
                return;
            }

            const point = meta.data[0];
            const x = point.x;
            const y = point.y;
            const ctx = chart.ctx;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.font = '700 22px "Segoe UI", sans-serif';
            ctx.fillStyle = chartPrimaryText;
            ctx.fillText(String(statusTotal), x, y - 4);

            ctx.font = '500 11px "Segoe UI", sans-serif';
            ctx.fillStyle = chartMutedText;
            ctx.fillText('total', x, y + 14);
            ctx.restore();
        }
    };

    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas) {
        const statusCtx = statusCanvas.getContext('2d');
        if (staffStatusChart) {
            staffStatusChart.destroy();
        }

        staffStatusChart = new Chart(statusCtx, {
            type: 'doughnut',
            plugins: [statusCenterTextPlugin],
            data: {
                labels: ['Submitted', 'In Progress', 'Completed'],
                datasets: [{
                    data: statusDataset,
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981'],
                    borderColor: doughnutBorder,
                    borderWidth: 2,
                    hoverOffset: 3,
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'right',
                        align: 'center',
                        labels: {
                            color: chartMutedText,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 14
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => ` ${context.formattedValue} report${Number(context.formattedValue) !== 1 ? 's' : ''}`
                        }
                    }
                }
            }
        });
    }

    const priorityLabelsRaw = Array.isArray(data?.priority_data?.labels) ? data.priority_data.labels : [];
    const priorityValuesRaw = Array.isArray(data?.priority_data?.values) ? data.priority_data.values : [];
    const priorityTotals = { low: 0, medium: 0, high: 0, critical: 0 };

    priorityLabelsRaw.forEach((label, index) => {
        const normalized = String(label || '').trim().toLowerCase();
        const value = Number(priorityValuesRaw[index] || 0);

        if (normalized === 'low') {
            priorityTotals.low += value;
            return;
        }

        if (normalized === 'medium') {
            priorityTotals.medium += value;
            return;
        }

        if (normalized === 'high') {
            priorityTotals.high += value;
            return;
        }

        if (normalized === 'critical' || normalized === 'urgent') {
            priorityTotals.critical += value;
        }
    });

    const priorityCanvas = document.getElementById('priorityChart');
    if (priorityCanvas) {
        const priorityCtx = priorityCanvas.getContext('2d');
        if (staffPriorityChart) {
            staffPriorityChart.destroy();
        }

        staffPriorityChart = new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: ['Low', 'Medium', 'High', 'Critical'],
                datasets: [{
                    label: 'Reports',
                    data: [priorityTotals.low, priorityTotals.medium, priorityTotals.high, priorityTotals.critical],
                    backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#991b1b'],
                    borderRadius: 6,
                    barThickness: 64,
                    maxBarThickness: 72
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 2,
                            color: chartMutedText
                        },
                        grid: {
                            color: chartGridY,
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: { color: chartMutedText },
                        grid: {
                            color: chartGridX,
                            drawBorder: false
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => ` ${context.parsed.y} report${context.parsed.y !== 1 ? 's' : ''}`
                        }
                    }
                }
            }
        });
    }
}

function initializeClickableCards() {
    document.querySelectorAll('.stat-card-clickable').forEach((card) => {
        const navigate = () => {
            const target = card.getAttribute('data-href');
            if (target) {
                window.location.href = target;
            }
        };

        card.addEventListener('click', navigate);
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                navigate();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadStaffDashboardData();
    loadStaffChartData();
    initializeClickableCards();
});
</script>

</body>
</html>

