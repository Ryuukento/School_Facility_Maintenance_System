<?php
/**
 * Maintenance Admin Dashboard
 * Main interface for maintenance administrators
 */
session_start();

// Check if user is logged in and has maintenance_admin role
if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
if (!in_array($user['role'], ['super_admin', 'maintenance_admin', 'maintenance_staff'])) {
    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/dashboard.php');
    exit;
}

$isMaintenanceAdminView = in_array($user['role'], ['super_admin', 'maintenance_admin'], true);
$reportsPageLink = $isMaintenanceAdminView
    ? '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php'
    : '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/maintenance-reports-list.php';

require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Dashboard - School Facility Maintenance System</title>
    <script>
        (function () {
            try {
                var mode = localStorage.getItem('sfmsThemeMode') || 'light';
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var resolved = mode === 'auto' ? (prefersDark ? 'dark' : 'light') : mode;
                var root = document.documentElement;

                root.setAttribute('data-theme-mode', mode);
                root.setAttribute('data-theme-resolved', resolved);
                root.style.colorScheme = resolved === 'dark' ? 'dark' : 'light';
            } catch (error) {
                // Keep default theme if storage is unavailable.
            }
        })();
    </script>
        <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/styles.css?v=20260415-3">
        <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/color-scheme.css?v=20260415-3">
        <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/maintenance-dashboard.css?v=20260415-3">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container maintenance-admin-dashboard-page">
    <!-- Summary Cards Grid -->
    <div class="summary-cards-grid">
        <div class="summary-card summary-card-total summary-card-action" onclick="navigateToReportsCard('total')">
            <div class="summary-card-content">
                <h3 class="summary-card-title">Total reports</h3>
                <div class="summary-card-value" id="stat-total">0</div>
                <p class="summary-card-desc summary-trend-positive">all reports in the system</p>
            </div>
            <div class="summary-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #a78bfa; stroke: #a78bfa;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <path d="M14 2v6h6"></path>
                </svg>
            </div>
        </div>

        <div class="summary-card summary-card-today summary-card-action" onclick="navigateToReportsCard('today')">
            <div class="summary-card-content">
                <h3 class="summary-card-title">Reports today</h3>
                <div class="summary-card-value" id="stat-today">0</div>
                <p class="summary-card-desc summary-trend-positive">submitted today</p>
            </div>
            <div class="summary-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #22d3ee; stroke: #22d3ee;">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                </svg>
            </div>
        </div>

        <div class="summary-card summary-card-pending summary-card-action" onclick="navigateToReportsCard('pending_tasks')">
            <div class="summary-card-content">
                <h3 class="summary-card-title">Pending tasks</h3>
                <div class="summary-card-value" id="stat-pending">0</div>
                <p class="summary-card-desc summary-trend-warning">needs attention</p>
            </div>
            <div class="summary-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #fbbf24; stroke: #fbbf24;">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 7v6l4 2"></path>
                </svg>
            </div>
        </div>

        <div class="summary-card summary-card-progress summary-card-action" onclick="navigateToReportsCard('in_progress')">
            <div class="summary-card-content">
                <h3 class="summary-card-title">In progress</h3>
                <div class="summary-card-value" id="stat-in-progress">0</div>
                <p class="summary-card-desc summary-trend-positive">on track</p>
            </div>
            <div class="summary-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #c084fc; stroke: #c084fc;">
                    <path d="M20 7a5 5 0 0 1-7 4.6L7.6 17A2 2 0 1 1 5 14.4l5.4-5.4A5 5 0 1 1 20 7z"></path>
                </svg>
            </div>
        </div>

        <div class="summary-card summary-card-completed summary-card-action" onclick="navigateToReportsCard('completed')">
            <div class="summary-card-content">
                <h3 class="summary-card-title">Completed</h3>
                <div class="summary-card-value" id="stat-completed">0</div>
                <p class="summary-card-desc summary-trend-positive">this month</p>
            </div>
            <div class="summary-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #34d399; stroke: #34d399;">
                    <path d="M20 6L9 17l-5-5"></path>
                </svg>
            </div>
        </div>

        <div class="summary-card summary-card-low-stock summary-card-alert summary-card-action" onclick="navigateToReportsCard('low_stock')">
            <div class="summary-card-content">
                <h3 class="summary-card-title">Low stock</h3>
                <div class="summary-card-value" id="stat-low">&mdash;</div>
                <p class="summary-card-desc summary-trend-danger">restock now</p>
            </div>
            <div class="summary-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #fb7185; stroke: #fb7185;">
                    <path d="M10.29 3.86l-8 14A1 1 0 0 0 3.14 19h17.72a1 1 0 0 0 .85-1.5l-8-14a1 1 0 0 0-1.72 0z"></path>
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                </svg>
            </div>
        </div>

        <div class="summary-card summary-card-buildings summary-card-action" onclick="navigateToBuildingsOverview()">
            <div class="summary-card-content">
                <h3 class="summary-card-title">Buildings overview</h3>
                <div class="summary-card-value summary-card-value-action">Open</div>
                <p class="summary-card-desc">browse buildings and rooms</p>
            </div>
            <div class="summary-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #60a5fa; stroke: #60a5fa;">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l7-4 7 4v14"></path>
                    <path d="M9 9h6"></path>
                    <path d="M9 13h6"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
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
                <p class="text-muted mb-0">Priority levels across all reports.</p>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" class="chart-canvas"></canvas>
            </div>
        </div>
    </div>

    <?php if (!$isMaintenanceAdminView): ?>
    <!-- Recent Reports -->
    <div class="card mt-lg">
        <div class="card-header d-flex justify-between align-center">
            <h3 style="margin: 0;">My Recent Reports</h3>
            <a href="<?php echo htmlspecialchars($reportsPageLink, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-body">
            <p class="text-muted" style="margin-bottom: 10px;">Showing reports you created or are assigned to.</p>
            <div id="recent-reports-container">
                <div class="loading">Loading reports...</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/maintenance-dashboard.inline.css?v=20260419-1">

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/js/api.js"></script>

<script>
// Ensure API is defined
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/laravel_app/public/backend/api',
    
    async getDashboardStats() {
        const response = await fetch(`${this.baseURL}/maintenance-dashboard-api.php?action=stats`);
        if (!response.ok) throw new Error('Failed to fetch stats');
        return await response.json();
    },

    async getChartData() {
        const response = await fetch(`${this.baseURL}/maintenance-dashboard-api.php?action=charts`);
        if (!response.ok) throw new Error('Failed to fetch chart data');
        return await response.json();
    },

    async getRecentReports() {
        const response = await fetch(`${this.baseURL}/maintenance-reports-api.php?action=recent`);
        if (!response.ok) throw new Error('Failed to fetch recent reports');
        return await response.json();
    }
};

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

// Initialize dashboard
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // Load statistics
        const statsResponse = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/maintenance-dashboard-api.php?action=stats');
        const statsData = await statsResponse.json();

        if (statsData.success && statsData.data) {
            const stats = statsData.data;
            setTextById('stat-total', stats.total_reports || 0);
            setTextById('stat-pending', stats.pending || 0);
            setTextById('stat-in-progress', stats.in_progress || 0);
            setTextById('stat-completed', stats.completed_this_month || 0);
            
            const avgDays = stats.avg_completion_days ? Math.round(stats.avg_completion_days) : 0;
            window.maintenanceAvgCompletionDays = avgDays;
        }

        // compute today's reports count separately
        try {
            const today = new Date().toISOString().split('T')[0];
            const respToday = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/maintenance-reports-api.php?action=list&per_page=1000');
            const dataToday = await respToday.json();
            if (dataToday.success && dataToday.data && Array.isArray(dataToday.data.reports)) {
                const countToday = dataToday.data.reports.filter(r => r.created_at && r.created_at.startsWith(today)).length;
                const elem = document.getElementById('stat-today');
                if (elem) elem.textContent = countToday;
            }
        } catch (err) {
            console.error('Error computing today count', err);
        }

        // Load chart data
        const chartResponse = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/maintenance-dashboard-api.php?action=charts');
        const chartData = await chartResponse.json();
        if (chartData.success && chartData.data) {
            initializeCharts(chartData.data);
        }

        <?php if (!$isMaintenanceAdminView): ?>
        // Load recent reports for non-maintenance-admin users only
        const reportsResponse = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/maintenance-reports-api.php?action=recent');
        const reportsData = await reportsResponse.json();

        if (reportsData.success && reportsData.data.reports) {
            displayRecentReports(reportsData.data.reports);
        }
        <?php endif; ?>

    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
});

function setTextById(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function navigateToBuildingsOverview() {
    window.location.href = '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/buildings-overview.php?from=maintenance';
}

function navigateToReportsCard(cardKey) {
    if (cardKey === 'low_stock') {
        window.location.href = '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/inventory.php?status_filter=low_stock';
        return;
    }

    const targetUrl = new URL('/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php', window.location.origin);

    if (cardKey === 'today') {
        targetUrl.searchParams.set('date_scope', 'today');
    } else if (cardKey === 'pending_tasks') {
        targetUrl.searchParams.set('status_group', 'pending_tasks');
    } else if (cardKey === 'in_progress') {
        targetUrl.searchParams.set('status', 'in_progress');
    } else if (cardKey === 'completed') {
        targetUrl.searchParams.set('status', 'completed');
    }

    window.location.href = targetUrl.toString();
}

function initializeCharts(data) {
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
        new Chart(statusCtx, {
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
        new Chart(priorityCtx, {
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

    // Trend Chart
    if (data.trend_data) {
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: data.trend_data.labels,
                    datasets: [
                        {
                            label: 'Created',
                            data: data.trend_data.created,
                            borderColor: '#1e3a5f',
                            backgroundColor: '#1e3a5f10',
                            tension: 0.4
                        },
                        {
                            label: 'Completed',
                            data: data.trend_data.completed,
                            borderColor: '#10b981',
                            backgroundColor: '#10b98110',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }
}

function displayRecentReports(reports) {
    const container = document.getElementById('recent-reports-container');
    const isMaintenanceAdminView = <?php echo $isMaintenanceAdminView ? 'true' : 'false'; ?>;
    
    if (reports.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No reports found</p>';
        return;
    }

    let html = '<table class="table">';
    html += '<thead><tr>';
    html += '<th>ID</th><th>Title</th><th>Location</th><th>Priority</th><th>Status</th>';
    if (isMaintenanceAdminView) {
        html += '<th>Submitted By</th>';
    }
    html += '<th>Assigned To</th><th>Created</th><th>Action</th>';
    html += '</tr></thead><tbody>';

    reports.forEach(report => {
        const priorityBadge = UI.getPriorityBadge(report.priority);
        const statusBadge = UI.getStatusBadge(report.status);
        const createdDate = UI.formatDate(report.created_at);

        html += '<tr>';
        html += `<td>#${report.report_id}</td>`;
        html += `<td><strong>${report.title}</strong></td>`;
        html += `<td>${report.location}</td>`;
        html += `<td><span class="badge ${priorityBadge}">${report.priority.toUpperCase()}</span></td>`;
        html += `<td><span class="badge ${statusBadge}">${report.status.replace('_', ' ').toUpperCase()}</span></td>`;
        if (isMaintenanceAdminView) {
            html += `<td>${report.creator_name || 'N/A'}</td>`;
        }
        html += `<td>${report.assigned_name || 'Unassigned'}</td>`;
        html += `<td>${createdDate}</td>`;
        html += `<td><a href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/maintenance-report-detail.php?id=${report.report_id}" class="btn btn-sm btn-primary">View</a></td>`;
        html += '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}
</script>

</body>
</html>


