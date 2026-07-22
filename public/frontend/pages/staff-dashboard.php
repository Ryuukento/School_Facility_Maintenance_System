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
$currentUserId = (int)($user['user_id'] ?? 0);

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
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
    <script src="/School_Facility_Maintenance_System/frontend/assets/js/chart-lite.js?v=20260504-5"></script>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container staff-dashboard-page">
    <div class="stats-grid">
        <div class="stat-card stat-card-total stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status_group=assigned_to_me" aria-label="Open assigned reports">
            <div class="stat-content">
                <p class="stat-label">Assigned to you</p>
                <h3 class="stat-value" id="my-assigned-reports"><?php echo (int)$assignedReportsCount; ?></h3>
                <p class="stat-meta text-muted">Reports waiting on your action</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">&#128196;</div>
        </div>

        <div class="stat-card stat-card-pending stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/buildings-overview.php" aria-label="Open buildings overview">
            <div class="stat-content">
                <p class="stat-label">Buildings overview</p>
                <h3 class="stat-value" id="my-buildings"><?php echo (int)$buildingsCount; ?></h3>
                <p class="stat-meta text-muted">Tracked buildings in the system</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">&#127970;</div>
        </div>

        <div class="stat-card stat-card-total stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php" aria-label="Open all reports">
            <div class="stat-content">
                <p class="stat-label">Total Reports</p>
                <h3 class="stat-value" id="total-reports"><?php echo (int)$totalReportsCount; ?></h3>
                <p class="stat-meta text-muted">All reports in system</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">&#128202;</div>
        </div>

        <div class="stat-card stat-card-pending stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status=submitted" aria-label="Open pending reports">
            <div class="stat-content">
                <p class="stat-label">Pending</p>
                <h3 class="stat-value" id="my-pending">0</h3>
                <p class="stat-meta text-muted">Need action</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">&#9203;</div>
        </div>

        <div class="stat-card stat-card-progress stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status=in_progress" aria-label="Open in progress reports">
            <div class="stat-content">
                <p class="stat-label">In Progress</p>
                <h3 class="stat-value" id="my-in-progress">0</h3>
                <p class="stat-meta text-muted">Currently working</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">&#128295;</div>
        </div>

        <div class="stat-card stat-card-completed stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status=completed" aria-label="Open completed reports">
            <div class="stat-content">
                <p class="stat-label">Completed</p>
                <h3 class="stat-value" id="my-completed">0</h3>
                <p class="stat-meta text-muted">Finished reports</p>
            </div>
            <div class="stat-icon-chip" aria-hidden="true">&#9989;</div>
        </div>
    </div>

    <div class="charts-section">
        <div class="card chart-card status-chart-card chart-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php" aria-label="Open reports by status">
            <div class="card-header">
                <div class="status-chart-head-row">
                    <h2>Reports by Status</h2>
                </div>
                <p class="text-muted mb-0">Current distribution of report statuses.</p>
            </div>
            <div class="card-body status-chart-body">
                <div style="position:relative;height:280px;">
                    <canvas id="statusChart" class="chart-canvas" style="display:block;width:100%;height:280px;"></canvas>
                </div>
            </div>
        </div>

        <div class="card chart-card priority-chart-card chart-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php" aria-label="Open reports by priority">
            <div class="card-header">
                <h2>Reports by Priority</h2>
                <p class="text-muted mb-0">Priority levels across assigned reports.</p>
            </div>
            <div class="card-body">
                <div style="position:relative;height:280px;">
                    <canvas id="priorityChart" class="chart-canvas" style="display:block;width:100%;height:280px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="recent-reports-stack">
        <div class="card recent-reports-card">
            <div class="card-header d-flex justify-between align-center recent-reports-header">
                <h3 style="margin: 0;">Recent Reports</h3>
                <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status_group=assigned_to_me" class="recent-reports-link">View all &rarr;</a>
            </div>
            <div class="card-body recent-reports-body">
                <p class="text-muted recent-reports-subtitle">Showing today's reports assigned to you or created by you.</p>
                <div id="staff-recent-reports-container" class="recent-reports-container">
                    <div class="loading">Loading reports...</div>
                </div>
            </div>
        </div>

        <div class="card recent-reports-card inventory-status-card">
            <div class="card-header d-flex justify-between align-center recent-reports-header">
                <h3 style="margin:0;">Inventory Status</h3>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span id="staff-inventory-summary" style="font-size:13px;color:#94a3b8;"></span>
                    <button type="button" id="staff-inventory-export-btn" class="btn btn-secondary" style="font-size:12px;padding:6px 12px;border-radius:8px;">Export CSV</button>
                    <a href="/School_Facility_Maintenance_System/frontend/pages/inventory.php" class="recent-reports-link">View all &rarr;</a>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted recent-reports-subtitle">Items needing attention — low stock and out of stock.</p>
                <div id="staff-inventory-widget"><div class="loading">Loading inventory...</div></div>
            </div>
        </div>
    </div>
</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/staff-dashboard.inline.css?v=20260507-1">

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardMonthSelection();
    window.addEventListener('sfms:monthSelected', onDashboardMonthSelected);
    loadStaffDashboardData();
});

const DASHBOARD_MONTH_STORAGE_KEY = 'sfms:dashboardMonthSelection';
let selectedMonth = (new Date().getMonth() + 1);
let selectedYear = (new Date().getFullYear());

function normalizeMonthSelection(year, month) {
    const normalizedYear = Number(year);
    const normalizedMonth = Number(month);

    if (!Number.isInteger(normalizedYear) || normalizedYear < 2000) {
        return null;
    }

    if (!Number.isInteger(normalizedMonth) || normalizedMonth < 1 || normalizedMonth > 12) {
        return null;
    }

    return { year: normalizedYear, month: normalizedMonth };
}

function loadDashboardMonthSelection() {
    try {
        const storedValue = localStorage.getItem(DASHBOARD_MONTH_STORAGE_KEY);
        if (!storedValue) {
            return;
        }

        const parsedValue = JSON.parse(storedValue);
        const normalized = normalizeMonthSelection(parsedValue.year, parsedValue.month);
        if (normalized) {
            selectedYear = normalized.year;
            selectedMonth = normalized.month;
        }
    } catch (error) {
        console.warn('Unable to load staff dashboard month selection', error);
    }
}

function saveDashboardMonthSelection(year, month) {
    const normalized = normalizeMonthSelection(year, month);
    if (!normalized) {
        return;
    }

    selectedYear = normalized.year;
    selectedMonth = normalized.month;

    try {
        localStorage.setItem(DASHBOARD_MONTH_STORAGE_KEY, JSON.stringify(normalized));
    } catch (error) {
        console.warn('Unable to persist staff dashboard month selection', error);
    }
}

function onDashboardMonthSelected(event) {
    const detail = event && event.detail ? event.detail : {};
    const normalized = normalizeMonthSelection(detail.year, detail.month);

    if (!normalized) {
        return;
    }

    saveDashboardMonthSelection(normalized.year, normalized.month);
    loadStaffDashboardData();
}

let staffStatusChart = null;
let staffPriorityChart = null;
const currentStaffUserId = <?php echo $currentUserId; ?>;

window.UI = window.UI || {
    getPriorityBadge(priority) {
        const colors = {
            'low': 'badge-priority-low',
            'medium': 'badge-priority-medium',
            'high': 'badge-priority-high',
            'urgent': 'badge-priority-urgent',
            'critical': 'badge-priority-critical'
        };
        return colors[priority] || 'badge-priority-low';
    },

    getStatusBadge(status) {
        const colors = {
            'submitted': 'badge-info',
            'assigned': 'badge-warning',
            'in_progress': 'badge-warning',
            'completed': 'badge-success',
            'closed': 'badge-success',
            'draft': 'badge-info'
        };
        return colors[status] || 'badge-info';
    },

    formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
};

function refreshStaffChartsForTheme() {
    const isLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
    const chartMutedText = isLightMode ? '#374151' : '#94a3b8';
    const chartGridY = isLightMode ? 'rgba(17, 24, 39, 0.12)' : 'rgba(148, 163, 184, 0.35)';
    const chartGridX = isLightMode ? 'rgba(17, 24, 39, 0.08)' : 'rgba(148, 163, 184, 0.25)';
    const doughnutBorder = isLightMode ? '#ffffff' : '#0f172a';

    if (staffStatusChart) {
        const dataset = staffStatusChart.data?.datasets?.[0];
        if (dataset) {
            dataset.borderColor = doughnutBorder;
        }
        staffStatusChart.update('none');
        requestAnimationFrame(() => {
            if (!staffStatusChart) return;
            staffStatusChart.render();
            staffStatusChart.update('none');
        });
    }

    if (staffPriorityChart) {
        if (staffPriorityChart.options?.scales?.y?.ticks) {
            staffPriorityChart.options.scales.y.ticks.color = chartMutedText;
        }
        if (staffPriorityChart.options?.scales?.y?.grid) {
            staffPriorityChart.options.scales.y.grid.color = chartGridY;
        }
        if (staffPriorityChart.options?.scales?.x?.ticks) {
            staffPriorityChart.options.scales.x.ticks.color = chartMutedText;
        }
        if (staffPriorityChart.options?.scales?.x?.grid) {
            staffPriorityChart.options.scales.x.grid.color = chartGridX;
        }
        staffPriorityChart.update('none');
    }
}

// ========== LOAD STAFF DASHBOARD DATA WITH MONTH/YEAR =============
async function loadStaffDashboardData() {
    try {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/reports?per_page=200&status_group=assigned_to_me'), { credentials: 'include' });
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
        renderRecentReports(reports);

    } catch (error) {
        console.error('Failed to load staff dashboard data', error);
        renderRecentReports([]);
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatReportMetaDate(value) {
    if (!value) {
        return 'No date';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }

    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });
}

function getReportSortTime(report) {
    const candidates = [
        report?.updated_at,
        report?.created_at,
        report?.date_created,
        report?.report_date
    ];

    for (const candidate of candidates) {
        if (!candidate) {
            continue;
        }

        const parsed = new Date(candidate).getTime();
        if (!Number.isNaN(parsed)) {
            return parsed;
        }
    }

    return 0;
}

function getReportDateValue(report) {
    const candidates = [
        report?.created_at,
        report?.updated_at,
        report?.date_created,
        report?.report_date
    ];

    for (const candidate of candidates) {
        if (!candidate) {
            continue;
        }

        const parsed = new Date(candidate);
        if (!Number.isNaN(parsed.getTime())) {
            return parsed;
        }
    }

    return null;
}

function isTodayReport(report) {
    const reportDate = getReportDateValue(report);
    if (!reportDate) {
        return false;
    }

    const now = new Date();

    return reportDate.getFullYear() === now.getFullYear()
        && reportDate.getMonth() === now.getMonth()
        && reportDate.getDate() === now.getDate();
}

function normalizeStatusLabel(status) {
    const normalized = String(status || '').trim().toLowerCase();

    if (normalized === 'in_progress') return 'In Progress';
    if (normalized === 'submitted') return 'Submitted';
    if (normalized === 'assigned') return 'Assigned';
    if (normalized === 'completed') return 'Completed';
    if (normalized === 'closed') return 'Closed';

    return normalized
        .split('_')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ') || 'Unknown';
}

function normalizePriorityLabel(priority) {
    const normalized = String(priority || '').trim().toLowerCase();
    if (!normalized) {
        return 'N/A';
    }

    return normalized
        .split('_')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function getStatusClass(status) {
    const normalized = String(status || '').trim().toLowerCase();
    if (normalized === 'submitted') return 'status-submitted';
    if (normalized === 'assigned') return 'status-assigned';
    if (normalized === 'in_progress') return 'status-in-progress';
    if (normalized === 'completed' || normalized === 'closed') return 'status-completed';
    if (normalized === 'cancelled') return 'status-cancelled';
    return 'status-submitted';
}

function getPriorityClass(priority) {
    const normalized = String(priority || '').trim().toLowerCase();
    if (normalized === 'low') return 'priority-low';
    if (normalized === 'medium') return 'priority-medium';
    if (normalized === 'high') return 'priority-high';
    if (normalized === 'urgent') return 'priority-urgent';
    if (normalized === 'critical') return 'priority-critical';
    return 'priority-medium';
}

function renderRecentReports(reports) {
    const container = document.getElementById('staff-recent-reports-container');
    if (!container) {
        return;
    }

    const relevantReports = (Array.isArray(reports) ? reports : [])
        .filter((report) => Number(report?.assigned_to || 0) === currentStaffUserId || Number(report?.created_by || 0) === currentStaffUserId)
        .filter((report) => isTodayReport(report))
        .sort((a, b) => getReportSortTime(b) - getReportSortTime(a))
        .slice(0, 5);

    if (!relevantReports.length) {
        container.innerHTML = '<p class="recent-reports-empty">No reports today.</p>';
        return;
    }

    const rows = relevantReports.map((report) => {
        const reportId = Number(report?.report_id || 0);
        const title = escapeHtml(report?.title || `Report #${reportId}`);
        const location = escapeHtml(report?.location || 'No location provided');
        const priority = escapeHtml(normalizePriorityLabel(report?.priority));
        const status = escapeHtml(normalizeStatusLabel(report?.status));
        const createdAt = escapeHtml(UI.formatDate(report?.created_at || report?.updated_at || report?.report_date));
        const assignedTo = escapeHtml(report?.assigned_name || 'Unassigned');
        const priorityClass = getPriorityClass(report?.priority);
        const statusClass = getStatusClass(report?.status);

        return `
            <div class="recent-report-table-row">
                <div class="recent-report-cell recent-report-main">
                    <strong>${title}</strong>
                    <span>${location}</span>
                </div>
                <div class="recent-report-cell recent-report-date">${createdAt}</div>
                <div class="recent-report-cell recent-report-assigned">${assignedTo}</div>
                <div class="recent-report-cell recent-report-pill-cell">
                    <span class="recent-report-badge ${priorityClass}">${priority}</span>
                </div>
                <div class="recent-report-cell recent-report-pill-cell">
                    <span class="recent-report-badge ${statusClass}">${status}</span>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = `
        <div class="recent-reports-table-wrap">
            <div class="recent-reports-table-head">
                <div>Report</div>
                <div>Date</div>
                <div>Assigned To</div>
                <div>Priority</div>
                <div>Status</div>
            </div>
            <div class="recent-reports-table-body">
                ${rows}
            </div>
        </div>
    `;
}

async function loadStaffChartData() {
    try {
        const response = await fetch(
            window.SFMS_PUBLIC_URL(`/api/dashboard/maintenance/charts?year=${selectedYear}&month=${selectedMonth}`),
            { credentials: 'include' }
        );
        const chartData = await response.json();

        if (!chartData.success || !chartData.data) {
            await _loadStaffChartFromReports();
            return;
        }

        // Fall back to all-time when the selected month has no data
        const hasData = (chartData.data.status_data?.values || []).some((v) => Number(v) > 0);
        if (!hasData) {
            await _loadStaffChartFromReports();
            return;
        }

        initializeStaffCharts(chartData.data);
    } catch (error) {
        console.error('Failed to load staff chart data', error);
        await _loadStaffChartFromReports();
    }
}

async function _loadStaffChartFromReports() {
    try {
        const response = await fetch(
            window.SFMS_PUBLIC_URL('/api/reports?per_page=200'),
            { credentials: 'include' }
        );
        const data = await response.json();
        if (!data.success) return;

        const reports = Array.isArray(data.data?.reports) ? data.data.reports
            : Array.isArray(data.data?.data) ? data.data.data : [];

        const statuses   = { submitted: 0, in_progress: 0, completed: 0 };
        const priorities = { low: 0, medium: 0, high: 0, critical: 0 };

        reports.forEach((r) => {
            const s = String(r.status || '').toLowerCase();
            if (s === 'submitted' || s === 'assigned')       statuses.submitted++;
            else if (s === 'in_progress')                    statuses.in_progress++;
            else if (s === 'completed' || s === 'closed')    statuses.completed++;

            const p  = String(r.priority || '').toLowerCase();
            const np = p === 'urgent' ? 'critical' : p;
            if (priorities[np] !== undefined) priorities[np]++;
        });

        initializeStaffCharts({
            status_data: {
                labels: ['submitted', 'in_progress', 'completed'],
                values: [statuses.submitted, statuses.in_progress, statuses.completed],
            },
            priority_data: {
                labels: ['low', 'medium', 'high', 'critical'],
                values: [priorities.low, priorities.medium, priorities.high, priorities.critical],
            },
        });
    } catch (e) {
        console.error('Failed to load all-time staff chart data', e);
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
        afterDatasetsDraw(chart) {
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || !meta.data.length) {
                return;
            }

            const point = meta.data[0];
            const x = point.x;
            const y = point.y;
            const ctx = chart.ctx;
            const currentIsLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
            const centerTextColor = currentIsLightMode ? '#111827' : '#f8fafc';
            const centerMutedColor = currentIsLightMode ? '#374151' : '#94a3b8';

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.font = '700 28px "Segoe UI", sans-serif';
            ctx.fillStyle = centerTextColor;
            ctx.fillText(String(statusTotal), x, y - 7);

            ctx.font = '500 13px "Segoe UI", sans-serif';
            ctx.fillStyle = centerMutedColor;
            ctx.fillText('total', x, y + 15);
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
                radiusScale: 1.15,
                plugins: {
                    legend: {
                        position: 'right',
                        align: 'center',
                        labels: {
                            color: chartMutedText,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 16,
                            font: {
                                size: 14,
                                weight: '600'
                            }
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
                onClick: (event, elements) => {
                    if (!elements || !elements.length) {
                        return;
                    }

                    const idx = elements[0].index;
                    const priorities = ['low', 'medium', 'high', 'critical'];
                    const priority = priorities[idx];
                    if (!priority) {
                        return;
                    }

                    window.location.href = `/School_Facility_Maintenance_System/frontend/pages/reports.php?priority=${encodeURIComponent(priority)}`;
                },
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
    document.querySelectorAll('.stat-card-clickable, .chart-card-clickable').forEach((card) => {
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
    loadStaffInventoryWidget();
    document.getElementById('staff-inventory-export-btn')?.addEventListener('click', exportInventoryCSV);
    loadStaffChartData();
    initializeClickableCards();

    const root = document.documentElement;
    const themeObserver = new MutationObserver((mutations) => {
        const changedTheme = mutations.some((mutation) => mutation.type === 'attributes' && mutation.attributeName === 'data-theme-resolved');
        if (changedTheme) {
            refreshStaffChartsForTheme();
            setTimeout(() => {
                refreshStaffChartsForTheme();
            }, 60);
        }
    });
    themeObserver.observe(root, { attributes: true, attributeFilter: ['data-theme-resolved'] });
});
let staffInventoryAllItems = [];

async function loadStaffInventoryWidget() {
    const widget = document.getElementById('staff-inventory-widget');
    const summary = document.getElementById('staff-inventory-summary');
    if (!widget) return;
    try {
        const res = await fetch(window.SFMS_PUBLIC_URL('/api/inventory-stock'), { credentials: 'same-origin' });
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const data = await res.json();
        if (!data.success || !Array.isArray(data.data?.items)) {
            console.warn('Invalid inventory response:', data);
            widget.innerHTML = '<p class="recent-reports-empty">No inventory data available.</p>';
            return;
        }

        staffInventoryAllItems = data.data.items;
        const low = staffInventoryAllItems.filter(i => i.status === 'low_stock');
        const out = staffInventoryAllItems.filter(i => i.status === 'out_of_stock');
        const ok = staffInventoryAllItems.filter(i => i.status === 'available');

        if (summary) {
            summary.innerHTML = `<span style="color:#f87171;margin-right:8px;">&#9679; ${out.length} Out of Stock</span><span style="color:#fbbf24;margin-right:8px;">&#9679; ${low.length} Low Stock</span><span style="color:#34d399;">&#9679; ${ok.length} OK</span>`;
        }

        const attention = [...out, ...low];
        if (!attention.length) {
            widget.innerHTML = '<p class="recent-reports-empty">&#10003; All items are sufficiently stocked.</p>';
            return;
        }

        const rows = attention.map(item => {
            const badge = item.status === 'out_of_stock'
                ? '<span class="badge badge-danger">OUT OF STOCK</span>'
                : '<span class="badge badge-warning">LOW STOCK</span>';
            return `<div class="recent-report-table-row">
                <div class="recent-report-cell recent-report-main">
                    <strong>${item.name || '-'}</strong>
                    <span>${item.inventory_room_name || 'Inventory Room'}</span>
                </div>
                <div class="recent-report-cell">${item.category_name || 'Uncategorized'}</div>
                <div class="recent-report-cell">${Number(item.quantity ?? 0)} remaining</div>
                <div class="recent-report-cell">${badge}</div>
            </div>`;
        }).join('');

        widget.innerHTML = `<div class="recent-reports-table-wrap">
            <div class="recent-reports-table-head">
                <div>Item</div><div>Category</div><div>Quantity</div><div>Status</div>
            </div>
            <div class="recent-reports-table-body">${rows}</div>
        </div>`;
    } catch (e) {
        console.error('Failed to load inventory:', e);
        widget.innerHTML = '<p class="recent-reports-empty">Could not load inventory data.</p>';
    }
}

function exportInventoryCSV() {
    const items = staffInventoryAllItems;
    if (!items.length) { alert('No inventory data to export.'); return; }

    const headers = ['Item', 'Category', 'Location', 'Quantity', 'Threshold', 'Status'];
    const rows = items.map(i => [
        i.name || '',
        i.category_name || 'Uncategorized',
        i.inventory_room_name || '',
        String(i.quantity ?? 0),
        String(i.reorder_level ?? '-'),
        String(i.status || '').replace(/_/g, ' ').toUpperCase()
    ]);

    const csvEscape = v => `"${String(v).replace(/"/g, '""')}"`;
    const csv = '\uFEFF' + [headers, ...rows].map(r => r.map(csvEscape).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'inventory_status_' + new Date().toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
</script>

</body>
</html>




