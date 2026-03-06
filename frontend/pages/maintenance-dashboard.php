<?php
/**
 * Maintenance Admin Dashboard
 * Main interface for maintenance administrators
 */
session_start();

// Check if user is logged in and has maintenance_admin role
if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
if (!in_array($user['role'], ['super_admin', 'maintenance_admin'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Dashboard - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="margin-top: 20px;">
    <!-- Page Header -->
    <div class="page-header mb-lg">
        <h1 style="margin: 0;">Maintenance Dashboard</h1>
        <p class="text-muted" style="margin: 5px 0 0 0;">Track and manage facility maintenance reports</p>
        <p style="margin: 8px 0 0 0; font-size: 0.95em;">
            Logged in as <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
            <span class="badge badge-secondary" style="text-transform: capitalize; margin-left: 5px;">
                <?php echo htmlspecialchars(str_replace('_', ' ', $user['role'])); ?>
            </span>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe;">
                <span style="font-size: 24px;">📋</span>
            </div>
            <div class="stat-content">
                <p class="stat-label">Total Reports</p>
                <h3 class="stat-value" id="total-reports">0</h3>
                <p class="stat-meta text-muted">Created by you</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #e0f2fe;">
                <span style="font-size: 24px;">📅</span>
            </div>
            <div class="stat-content">
                <p class="stat-label">Reports Today</p>
                <h3 class="stat-value" id="reports-today">0</h3>
                <p class="stat-meta text-muted">Submitted today</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #fed7aa;">
                <span style="font-size: 24px;">⏳</span>
            </div>
            <div class="stat-content">
                <p class="stat-label">Pending Tasks</p>
                <h3 class="stat-value" id="pending-tasks">0</h3>
                <p class="stat-meta text-muted">Assigned to you</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #fed7aa;">
                <span style="font-size: 24px;">🔧</span>
            </div>
            <div class="stat-content">
                <p class="stat-label">In Progress</p>
                <h3 class="stat-value" id="in-progress">0</h3>
                <p class="stat-meta text-muted">Currently working</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #bbf7d0;">
                <span style="font-size: 24px;">✅</span>
            </div>
            <div class="stat-content">
                <p class="stat-label">Completed</p>
                <h3 class="stat-value" id="completed-month">0</h3>
                <p class="stat-meta text-muted">This month</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #fecaca;">
                <span style="font-size: 24px;">⚠️</span>
            </div>
            <div class="stat-content">
                <p class="stat-label">Overdue</p>
                <h3 class="stat-value" id="overdue-count">0</h3>
                <p class="stat-meta text-muted">Need attention</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #d1d5db;">
                <span style="font-size: 24px;">⏱️</span>
            </div>
            <div class="stat-content">
                <p class="stat-label">Avg. Completion</p>
                <h3 class="stat-value" id="avg-completion">0d</h3>
                <p class="stat-meta text-muted">Days to complete</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions mb-lg">
        <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-create-report.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <span>➕</span> Create New Report
        </a>
        <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <span>📋</span> View All Reports
        </a>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
        <div class="card">
            <div class="card-header">
                <h3>Reports by Status</h3>
            </div>
            <div class="card-body" style="position: relative; height: 300px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Reports by Priority</h3>
            </div>
            <div class="card-body" style="position: relative; height: 300px;">
                <canvas id="priorityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="card mt-lg">
        <div class="card-header d-flex justify-between align-center">
            <h3 style="margin: 0;">My Recent Reports</h3>
            <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="card-body">
            <p class="text-muted" style="margin-bottom: 10px;">Showing reports you created or are assigned to.</p>
            <div id="recent-reports-container">
                <div class="loading">Loading reports...</div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="card mt-lg">
        <div class="card-header">
            <h3 style="margin: 0;">Monthly Trend</h3>
        </div>
        <div class="card-body" style="position: relative; height: 300px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
// Ensure API is defined
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/backend/api',
    
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
            'low': 'badge-info',
            'medium': 'badge-warning',
            'high': 'badge-danger',
            'urgent': 'badge-danger',
            'critical': 'badge-danger'
        };
        return colors[priority] || 'badge-info';
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
        const statsResponse = await fetch('/School_Facility_Maintenance_System/backend/api/maintenance-dashboard-api.php?action=stats');
        const statsData = await statsResponse.json();

        if (statsData.success && statsData.data) {
            const stats = statsData.data;
            document.getElementById('total-reports').textContent = stats.total_reports || 0;
            document.getElementById('pending-tasks').textContent = stats.pending || 0;
            document.getElementById('in-progress').textContent = stats.in_progress || 0;
            document.getElementById('completed-month').textContent = stats.completed_this_month || 0;
            document.getElementById('overdue-count').textContent = stats.overdue || 0;
            
            const avgDays = stats.avg_completion_days ? Math.round(stats.avg_completion_days) : 0;
            document.getElementById('avg-completion').textContent = avgDays + 'd';
        }

        // compute today's reports count separately
        try {
            const today = new Date().toISOString().split('T')[0];
            const respToday = await fetch('/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php?action=list&per_page=1000');
            const dataToday = await respToday.json();
            if (dataToday.success && dataToday.data && Array.isArray(dataToday.data.reports)) {
                const countToday = dataToday.data.reports.filter(r => r.created_at && r.created_at.startsWith(today)).length;
                const elem = document.getElementById('reports-today');
                if (elem) elem.textContent = countToday;
            }
        } catch (err) {
            console.error('Error computing today count', err);
        }

        // Load chart data
        const chartResponse = await fetch('/School_Facility_Maintenance_System/backend/api/maintenance-dashboard-api.php?action=charts');
        const chartData = await chartResponse.json();
        if (chartData.success && chartData.data) {
            initializeCharts(chartData.data);
        }

        // Load recent reports
        const reportsResponse = await fetch('/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php?action=recent');
        const reportsData = await reportsResponse.json();

        if (reportsData.success && reportsData.data.reports) {
            displayRecentReports(reportsData.data.reports);
        }

    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
});

function initializeCharts(data) {
    // Status Chart
    if (data.status_data) {
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: data.status_data.labels,
                    datasets: [{
                        data: data.status_data.values,
                        backgroundColor: ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#64748b', '#94a3b8'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    }

    // Priority Chart
    if (data.priority_data) {
        const priorityCtx = document.getElementById('priorityChart');
        if (priorityCtx) {
            new Chart(priorityCtx, {
                type: 'bar',
                data: {
                    labels: data.priority_data.labels,
                    datasets: [{
                        label: 'Reports',
                        data: data.priority_data.values,
                        backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#ef4444', '#991b1b'],
                        borderColor: '#ffffff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
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
    
    if (reports.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No reports found</p>';
        return;
    }

    let html = '<table class="table">';
    html += '<thead><tr>';
    html += '<th>ID</th><th>Title</th><th>Location</th><th>Priority</th><th>Status</th>';
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
        html += `<td>${report.assigned_name || 'Unassigned'}</td>`;
        html += `<td>${createdDate}</td>`;
        html += `<td><a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=${report.report_id}" class="btn btn-sm btn-primary">View</a></td>`;
        html += '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}
</script>

</body>
</html>
