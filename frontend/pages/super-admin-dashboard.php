<?php
// Super Admin Dashboard
// System-wide overview, statistics, and management

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if user is super_admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'System Administration Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/maintenance-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .system-dashboard {
            padding: 30px;
            background: #f3f4f6;
            min-height: 100vh;
        }

        .system-dashboard h1 {
            color: #8F00CC;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }

        .dashboard-subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid rgba(143, 0, 204, 0.2);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 122px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
            border-color: rgba(143, 0, 204, 0.35);
        }

        .stat-head {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-icon-chip {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            background: #f3e8ff;
        }

        .stat-card.users .stat-icon-chip {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        }

        .stat-card.reports .stat-icon-chip {
            background: linear-gradient(135deg, #dbeafe, #d1d5db);
        }

        .stat-card.departments .stat-icon-chip {
            background: linear-gradient(135deg, #f3e8ff, #ddd6fe);
        }

        .stat-card.pending .stat-icon-chip {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
        }

        .stat-card.in-progress .stat-icon-chip {
            background: linear-gradient(135deg, #fde68a, #fdba74);
        }

        .stat-card.completed .stat-icon-chip {
            background: linear-gradient(135deg, #bbf7d0, #86efac);
        }

        .stat-card.overdue .stat-icon-chip {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
        }

        .stat-label {
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 0;
            font-weight: 700;
        }

        .stat-value {
            color: #1f2a4a;
            font-size: 48px;
            line-height: 1;
            font-weight: 700;
            margin: 0;
        }

        .stat-detail {
            color: #4b5563;
            font-size: 13px;
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .chart-title {
            color: #8F00CC;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .chart-canvas {
            position: relative;
            height: 300px;
            margin-bottom: 15px;
        }

        .priority-chart-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
            padding: 0;
            overflow: hidden;
        }

        .priority-chart-card .chart-title {
            color: #111827;
            margin: 0;
            padding: 22px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .priority-chart-card .chart-canvas {
            margin-bottom: 0;
            padding: 18px 16px 14px;
        }

        .overview-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .overview-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .overview-title {
            color: #8F00CC;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .overview-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .overview-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .overview-list li:last-child {
            border-bottom: none;
        }

        .overview-list .name {
            color: #8F00CC;
            font-weight: 500;
        }

        .overview-list .count {
            background: #e5e7eb;
            color: #8F00CC;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .recent-activity-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .recent-activity-title {
            color: #8F00CC;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            font-size: 13px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-badge {
            background: #8F00CC;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            margin-top: 2px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-user {
            color: #8F00CC;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .activity-detail {
            color: #6b7280;
            margin-bottom: 4px;
        }

        .activity-time {
            color: #9ca3af;
            font-size: 12px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-left: 10px;
            border: 3px solid #e5e7eb;
            border-top-color: #8F00CC;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
        }

        .last-month-filters {
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .last-month-filter-control {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            background: var(--card-color);
            color: var(--text-light);
        }

        .last-month-clear-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 500;
            background: var(--accent-cancel);
            color: #ffffff;
        }

        .last-month-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: var(--card-color);
            border: 1px solid var(--border);
        }

        .last-month-table thead tr {
            background: var(--table-header-bg);
            border-bottom: 2px solid var(--table-border);
        }

        .last-month-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--table-header-text);
            border-color: var(--table-border);
        }

        .last-month-table td {
            padding: 12px;
            color: var(--text-light);
            border-bottom: 1px solid var(--table-border);
        }

        .last-month-table tbody tr:nth-child(odd) {
            background: var(--card-color);
        }

        .last-month-table tbody tr:nth-child(even) {
            background: var(--muted-card);
        }

        .last-month-table tbody tr:hover {
            background: var(--table-row-hover);
        }

        .last-month-table .cell-muted {
            color: var(--muted-text);
        }

        .last-month-table .cell-emphasis {
            color: var(--primary-color);
            font-weight: 600;
        }

        .last-month-view-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            border: 1px solid var(--primary-color);
            padding: 4px 12px;
            border-radius: 4px;
            display: inline-block;
            background: transparent;
        }

        .last-month-view-link:hover {
            background: var(--primary-color);
            color: #ffffff;
        }

        :root[data-theme-resolved='dark'] .system-dashboard {
            background: #080d18;
        }

        :root[data-theme-resolved='dark'] .recent-activity-section {
            background: #111827;
            border: 1px solid #374151;
        }

        :root[data-theme-resolved='dark'] .recent-activity-title {
            color: #f3f4f6;
            border-bottom-color: #374151;
        }

        :root[data-theme-resolved='dark'] .last-month-filter-control {
            background: #111827 !important;
            color: #e5e7eb !important;
            border-color: #374151 !important;
        }

        :root[data-theme-resolved='dark'] .last-month-filter-control::placeholder {
            color: #9ca3af;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table {
            background: #111827 !important;
            border-color: #374151 !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table thead,
        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table thead tr,
        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table th {
            background: #1f2937 !important;
            color: #f3f4f6 !important;
            border-color: #374151 !important;
            opacity: 1 !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table td {
            color: #e5e7eb !important;
            border-color: #374151 !important;
            opacity: 1 !important;
            text-shadow: none !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table tbody tr,
        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table tbody tr:nth-child(odd),
        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table tbody tr:nth-child(even) {
            background: #111827 !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table tbody tr:hover {
            background: #1a2233 !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table .cell-muted {
            color: #cbd5e1 !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-table .cell-emphasis {
            color: #f3f4f6 !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-view-link {
            color: #e5e7eb !important;
            border-color: #8A2BE2 !important;
            background: rgba(138, 43, 226, 0.12) !important;
        }

        :root[data-theme-resolved='dark'] #lastMonthReportsContainer .last-month-view-link:hover {
            color: #ffffff !important;
            background: #8A2BE2 !important;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .system-dashboard {
                padding: 15px;
            }

            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .stat-value {
                font-size: 38px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-icon-chip {
                width: 44px;
                height: 44px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header/Navigation -->
    <?php include '../includes/header.php'; ?>
    
    <div class="system-dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1>System Administration Dashboard</h1>
                <p class="dashboard-subtitle">System-wide overview and statistics</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php?last_month=1" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; text-decoration: none; border-radius: 6px; background: #8F00CC; color: white; font-weight: 500; white-space: nowrap;">
                    <span>ðŸ“…</span> Last Month Reports
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-grid" id="statsContainer">
            <div class="loading">Loading statistics...</div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-container">
                <div class="chart-title">Reports by Status</div>
                <div class="chart-canvas">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="chart-container priority-chart-card">
                <div class="chart-title">Reports by Priority</div>
                <div class="chart-canvas">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-title">Department Report Volume</div>
                <div class="chart-canvas">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-title">6-Month Trend</div>
                <div class="chart-canvas">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Reports Overview Section -->
        <div class="overview-section" style="margin-bottom: 30px;">
            <div class="overview-card" style="grid-column: 1 / -1;">
                <div class="overview-title">ðŸ“Š Reports Overview by Status</div>
                <div id="reportsOverviewContainer" style="display: flex; flex-wrap: wrap; gap: 12px; padding-top: 5px;">
                    <div class="loading">Loading reports overview...</div>
                </div>
            </div>
        </div>

        <!-- Overview Section -->
        <div class="overview-section">
            <div class="overview-card">
                <div class="overview-title">Users by Department</div>
                <ul class="overview-list" id="departmentUsersList">
                    <li><div class="loading">Loading...</div></li>
                </ul>
            </div>
            <div class="overview-card">
                <div class="overview-title">Most Active Staff</div>
                <ul class="overview-list" id="activeStaffList">
                    <li><div class="loading">Loading...</div></li>
                </ul>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity-section">
            <div class="recent-activity-title">Recent System Activity</div>
            <ul class="activity-list" id="activityList">
                <li><div class="loading">Loading activity...</div></li>
            </ul>
        </div>

        <!-- Last Month Reports Section -->
        <div class="recent-activity-section">
            <div class="recent-activity-title">ðŸ“… Last Month Reports</div>
            
            <!-- Filter Options -->
            <div class="last-month-filters">
                <select id="last-month-status-filter" class="form-control last-month-filter-control">
                    <option value="">All Status</option>
                    <option value="submitted">Submitted</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="closed">Closed</option>
                </select>
                
                <select id="last-month-priority-filter" class="form-control last-month-filter-control">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                </select>

                <input type="date" id="last-month-date-from" class="form-control last-month-filter-control">

                <input type="date" id="last-month-date-to" class="form-control last-month-filter-control">

                <input type="text" id="last-month-search" placeholder="Search title..." class="form-control last-month-filter-control">
                
                <button id="last-month-clear-filters" class="btn btn-secondary last-month-clear-btn">Clear Filters</button>
            </div>

            <!-- Reports Table -->
            <div id="lastMonthReportsContainer" style="overflow-x: auto;">
                <div class="loading">Loading last month reports...</div>
            </div>
        </div>
    </div>

    <script>
        let statusChart, priorityChart, departmentChart, trendChart;
        const APP_BASE = '/School_Facility_Maintenance_System';

        // Load all dashboard data
        async function loadDashboardData() {
            try {
                // Load statistics
                const statsResponse = await fetch(`${APP_BASE}/backend/api/super-admin-dashboard-api.php?action=getDashboardStats`);
                const statsData = await statsResponse.json();
                renderStatistics(statsData);

                // Load chart data
                const chartResponse = await fetch(`${APP_BASE}/backend/api/super-admin-dashboard-api.php?action=getChartData`);
                const chartData = await chartResponse.json();
                renderCharts(chartData);

                // Load system overview
                const overviewResponse = await fetch(`${APP_BASE}/backend/api/super-admin-dashboard-api.php?action=getSystemOverview`);
                const overviewData = await overviewResponse.json();
                renderOverview(overviewData);

                // Load recent activity
                const activityResponse = await fetch(`${APP_BASE}/backend/api/super-admin-dashboard-api.php?action=getRecentActivity`);
                const activityData = await activityResponse.json();
                renderActivity(activityData);

            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        function renderStatistics(data) {
            if (!data.success) {
                document.getElementById('statsContainer').innerHTML = '<div style="color: #ef4444; padding: 20px;">Failed to load statistics. Please refresh the page.</div>';
                return;
            }

            const container = document.getElementById('statsContainer');
            container.innerHTML = `
                <div class="stat-card users">
                    <div class="stat-head">
                        <span class="stat-icon-chip" aria-hidden="true">&#128101;</span>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-value">${data.totalUsers}</div>
                    <div class="stat-detail">
                        Super Admin: ${data.roleBreakdown.super_admin || 0}<br>
                        Dept Admin: ${data.roleBreakdown.department_admin || 0}<br>
                        Staff: ${data.roleBreakdown.maintenance_staff || 0}
                    </div>
                </div>

                <div class="stat-card reports">
                    <div class="stat-head">
                        <span class="stat-icon-chip" aria-hidden="true">&#128196;</span>
                        <div class="stat-label">Total Reports</div>
                    </div>
                    <div class="stat-value">${data.totalReports}</div>
                    <div class="stat-detail">
                        System-wide report volume
                    </div>
                </div>

                <div class="stat-card departments">
                    <div class="stat-head">
                        <span class="stat-icon-chip" aria-hidden="true">&#127970;</span>
                        <div class="stat-label">Departments</div>
                    </div>
                    <div class="stat-value">${data.totalDepartments}</div>
                    <div class="stat-detail">
                        Active maintenance departments
                    </div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-head">
                        <span class="stat-icon-chip" aria-hidden="true">&#9203;</span>
                        <div class="stat-label">Pending Tasks</div>
                    </div>
                    <div class="stat-value">${data.pendingReports}</div>
                    <div class="stat-detail">
                        Submitted + assigned
                    </div>
                </div>

                <div class="stat-card in-progress">
                    <div class="stat-head">
                        <span class="stat-icon-chip" aria-hidden="true">&#128295;</span>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-value">${data.inProgressReports || 0}</div>
                    <div class="stat-detail">
                        Currently being worked on
                    </div>
                </div>

                <div class="stat-card overdue">
                    <div class="stat-head">
                        <span class="stat-icon-chip" aria-hidden="true">&#9888;</span>
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-value">${data.overdueReports}</div>
                    <div class="stat-detail">
                        Past due date, not yet completed
                    </div>
                </div>

                <div class="stat-card completed">
                    <div class="stat-head">
                        <span class="stat-icon-chip" aria-hidden="true">&#10004;</span>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-value">${data.completedThisMonth}</div>
                    <div class="stat-detail">
                        This month
                    </div>
                </div>
            `;

            // Render reports overview badges
            renderReportsOverview(data);
        }

        function renderReportsOverview(data) {
            const container = document.getElementById('reportsOverviewContainer');
            if (!container) return;

            const statusConfig = [
                { key: 'submitted',   label: 'Submitted',   color: '#3b82f6', icon: 'ðŸ“‹' },
                { key: 'assigned',    label: 'Assigned',    color: '#f59e0b', icon: 'ðŸ‘¤' },
                { key: 'in_progress', label: 'In Progress', color: '#8b5cf6', icon: 'ðŸ”§' },
                { key: 'completed',   label: 'Completed',   color: '#10b981', icon: 'âœ…' },
                { key: 'closed',      label: 'Closed',      color: '#6b7280', icon: 'ðŸ”’' },
                { key: 'cancelled',   label: 'Cancelled',   color: '#ef4444', icon: 'âŒ' }
            ];

            const totalReports = data.totalReports || 0;

            container.innerHTML = statusConfig.map(s => {
                const count = data.statusBreakdown[s.key] || 0;
                const pct = totalReports > 0 ? Math.round((count / totalReports) * 100) : 0;
                return `
                    <div style="
                        background: white;
                        border: 1.5px solid ${s.color}33;
                        border-left: 4px solid ${s.color};
                        border-radius: 8px;
                        padding: 14px 20px;
                        min-width: 150px;
                        flex: 1;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
                    ">
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                            ${s.icon} ${s.label}
                        </div>
                        <div style="font-size: 28px; font-weight: 700; color: ${s.color}; line-height: 1;">${count}</div>
                        <div style="font-size: 12px; color: #9ca3af; margin-top: 5px;">${pct}% of total</div>
                        <div style="margin-top: 8px; background: #e5e7eb; border-radius: 99px; height: 5px; overflow: hidden;">
                            <div style="background: ${s.color}; width: ${pct}%; height: 100%; border-radius: 99px; transition: width 0.6s ease;"></div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderCharts(data) {
            if (!data.success) return;

            const ctx1 = document.getElementById('statusChart').getContext('2d');
            statusChart = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: data.statusChart.labels,
                    datasets: [{
                        data: data.statusChart.data,
                        backgroundColor: data.statusChart.colors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            const ctx2 = document.getElementById('priorityChart').getContext('2d');
            priorityChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: data.priorityChart.labels,
                    datasets: [{
                        label: 'Reports',
                        data: data.priorityChart.data,
                        backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#ef4444', '#991b1b'],
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        barThickness: 70,
                        maxBarThickness: 76
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 2,
                                color: '#4b5563'
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.35)',
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                color: '#4b5563'
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.25)',
                                drawBorder: false
                            }
                        }
                    }
                }
            });

            const ctx3 = document.getElementById('departmentChart').getContext('2d');
            departmentChart = new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: data.departmentChart.labels,
                    datasets: [{
                        label: 'Number of Reports',
                        data: data.departmentChart.data,
                        backgroundColor: '#8F00CC',
                        borderColor: '#8F00CC',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });

            const ctx4 = document.getElementById('trendChart').getContext('2d');
            trendChart = new Chart(ctx4, {
                type: 'line',
                data: {
                    labels: data.trendChart.labels,
                    datasets: [{
                        label: 'Reports Created',
                        data: data.trendChart.data,
                        borderColor: '#8F00CC',
                        backgroundColor: 'rgba(143, 0, 204, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#8F00CC',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function renderOverview(data) {
            if (!data.success) return;

            // Department users
            const deptList = document.getElementById('departmentUsersList');
            if (data.departmentUsers && data.departmentUsers.length > 0) {
                deptList.innerHTML = data.departmentUsers
                    .map(dept => `
                        <li>
                            <span class="name">${dept.name}</span>
                            <span class="count">${dept.count || 0} user${dept.count != 1 ? 's' : ''}</span>
                        </li>
                    `)
                    .join('');
            } else {
                deptList.innerHTML = '<li><span style="color: #9ca3af;">No department data available</span></li>';
            }

            // Active staff
            const staffList = document.getElementById('activeStaffList');
            if (data.activeStaff && data.activeStaff.length > 0) {
                staffList.innerHTML = data.activeStaff
                    .map(staff => `
                        <li>
                            <span class="name">${staff.full_name}</span>
                            <span class="count">${staff.assigned_count} assigned</span>
                        </li>
                    `)
                    .join('');
            } else {
                staffList.innerHTML = '<li><span style="color: #9ca3af;">No staff data available</span></li>';
            }
        }

        function renderActivity(data) {
            if (!data.success) return;

            const activityList = document.getElementById('activityList');
            
            if (data.activities.length === 0) {
                activityList.innerHTML = '<li><span style="color: #9ca3af;">No recent activity</span></li>';
                return;
            }

            activityList.innerHTML = data.activities
                .map(activity => {
                    const date = new Date(activity.created_at);
                    const timeAgo = getTimeAgo(date);
                    
                    return `
                        <li class="activity-item">
                            <span class="activity-badge">${activity.action}</span>
                            <div class="activity-content">
                                <div class="activity-user">${activity.full_name || 'System'}</div>
                                <div class="activity-detail">${activity.details}</div>
                                <div class="activity-time">${timeAgo}</div>
                            </div>
                        </li>
                    `;
                })
                .join('');
        }

        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            let interval = seconds / 31536000;

            if (interval > 1) return Math.floor(interval) + " years ago";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            return Math.floor(seconds) + " seconds ago";
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const defaultRange = getLastMonthDateRange();
            const fromInput = document.getElementById('last-month-date-from');
            const toInput = document.getElementById('last-month-date-to');
            if (fromInput && toInput) {
                fromInput.value = defaultRange.start;
                toInput.value = defaultRange.end;
                lastMonthFilters.date_from = defaultRange.start;
                lastMonthFilters.date_to = defaultRange.end;
            }

            loadDashboardData();
            loadLastMonthReports();
        });

        // Refresh every 5 minutes
        setInterval(loadDashboardData, 5 * 60 * 1000);
        setInterval(loadLastMonthReports, 5 * 60 * 1000);

        // ========== LAST MONTH REPORTS SECTION ==========
        let lastMonthFilters = {};

        function formatLocalDate(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        // Helper function to get last month date range
        function getLastMonthDateRange() {
            const today = new Date();
            const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0); // Last day of previous month
            const lastMonthStart = new Date(lastMonthEnd.getFullYear(), lastMonthEnd.getMonth(), 1); // First day of previous month
            
            return {
                start: formatLocalDate(lastMonthStart),
                end: formatLocalDate(lastMonthEnd)
            };
        }

        // Load last month reports
        async function loadLastMonthReports() {
            try {
                const defaultRange = getLastMonthDateRange();
                const dateFrom = lastMonthFilters.date_from || defaultRange.start;
                const dateTo = lastMonthFilters.date_to || defaultRange.end;
                
                const params = new URLSearchParams({
                    action: 'list',
                    per_page: 100,
                    date_from: dateFrom,
                    date_to: dateTo,
                    ...lastMonthFilters
                });

                const response = await fetch(`${APP_BASE}/backend/api/maintenance-reports-api.php?${params}`);
                const data = await response.json();

                if (data.success && data.data.reports) {
                    displayLastMonthReports(data.data.reports);
                } else {
                    document.getElementById('lastMonthReportsContainer').innerHTML = '<p class="text-muted text-center">No reports found for last month</p>';
                }
            } catch (error) {
                console.error('Error loading last month reports:', error);
                document.getElementById('lastMonthReportsContainer').innerHTML = '<p class="text-danger text-center">Failed to load reports</p>';
            }
        }

        // Display last month reports in table format
        function displayLastMonthReports(reports) {
            const container = document.getElementById('lastMonthReportsContainer');
            
            if (reports.length === 0) {
                container.innerHTML = '<p class="text-muted text-center" style="padding: 20px;">No reports found for last month</p>';
                return;
            }

            // Helper functions for badges
            function getPriorityColor(priority) {
                const colors = {
                    'low': '#3b82f6',
                    'medium': '#f59e0b',
                    'high': '#ef4444',
                    'urgent': '#dc2626',
                    'critical': '#991b1b'
                };
                return colors[priority] || '#6b7280';
            }

            function getStatusColor(status) {
                const colors = {
                    'submitted': '#3b82f6',
                    'assigned': '#f59e0b',
                    'in_progress': '#8b5cf6',
                    'completed': '#10b981',
                    'closed': '#6b7280',
                    'draft': '#94a3b8'
                };
                return colors[status] || '#6b7280';
            }

            function formatDate(date) {
                return new Date(date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }

            function getResolvedTheme() {
                const rootTheme = document.documentElement.getAttribute('data-theme-resolved');
                if (rootTheme === 'dark' || rootTheme === 'light') {
                    return rootTheme;
                }

                try {
                    const mode = localStorage.getItem('sfmsThemeMode') || 'light';
                    if (mode === 'auto') {
                        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    }
                    return mode === 'dark' ? 'dark' : 'light';
                } catch (error) {
                    return 'light';
                }
            }

            const isDark = getResolvedTheme() === 'dark';
            const tableBg = isDark ? '#111827' : '#ffffff';
            const borderColor = isDark ? '#374151' : '#e5e7eb';
            const headerBg = isDark ? '#1f2937' : '#f3f4f6';
            const headerText = isDark ? '#f3f4f6' : '#8F00CC';
            const rowOdd = isDark ? '#111827' : '#ffffff';
            const rowEven = isDark ? '#0f172a' : '#f9fafb';
            const textPrimary = isDark ? '#f3f4f6' : '#8F00CC';
            const textMuted = isDark ? '#cbd5e1' : '#6b7280';
            const viewText = isDark ? '#e5e7eb' : '#8F00CC';
            const viewBg = isDark ? 'rgba(138, 43, 226, 0.14)' : 'transparent';

            let html = `<table class="last-month-table" style="width: 100%; border-collapse: collapse; font-size: 14px; background: ${tableBg}; border: 1px solid ${borderColor};">`;
            html += '<thead>';
            html += `<tr style="background: ${headerBg}; border-bottom: 2px solid ${borderColor};">`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">ID</th>`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">Title</th>`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">Location</th>`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">Priority</th>`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">Status</th>`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">Assigned To</th>`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">Created</th>`;
            html += `<th style="padding: 12px; text-align: left; font-weight: 600; color: ${headerText}; border-color: ${borderColor};">Action</th>`;
            html += '</tr>';
            html += '</thead><tbody>';

            reports.forEach((report, index) => {
                const priorityColor = getPriorityColor(report.priority);
                const statusColor = getStatusColor(report.status);
                const createdDate = formatDate(report.created_at);
                const rowBg = index % 2 === 0 ? rowOdd : rowEven;

                html += `<tr style="background: ${rowBg}; border-bottom: 1px solid ${borderColor};">`;
                html += `<td class="cell-emphasis" style="padding: 12px; color: ${textPrimary}; border-color: ${borderColor}; font-weight: 600;">#${report.report_id}</td>`;
                html += `<td class="cell-emphasis" style="padding: 12px; color: ${textPrimary}; border-color: ${borderColor}; font-weight: 600;">${report.title}</td>`;
                html += `<td class="cell-muted" style="padding: 12px; color: ${textMuted}; border-color: ${borderColor};">${report.location}</td>`;
                html += `<td style="padding: 12px; border-color: ${borderColor};"><span style="background: ${priorityColor}; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600;">${report.priority.toUpperCase()}</span></td>`;
                html += `<td style="padding: 12px; border-color: ${borderColor};"><span style="background: ${statusColor}; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600;">${report.status.replace('_', ' ').toUpperCase()}</span></td>`;
                html += `<td class="cell-muted" style="padding: 12px; color: ${textMuted}; border-color: ${borderColor};">${report.assigned_name || 'Unassigned'}</td>`;
                html += `<td class="cell-muted" style="padding: 12px; color: ${textMuted}; border-color: ${borderColor};">${createdDate}</td>`;
                html += `<td style="padding: 12px; border-color: ${borderColor};"><a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=${report.report_id}" class="last-month-view-link" style="color: ${viewText}; text-decoration: none; font-weight: 600; border: 1px solid #8A2BE2; padding: 6px 14px; border-radius: 6px; display: inline-block; background: ${viewBg};">View</a></td>`;
                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        // Apply last month filters
        function applyLastMonthFilters() {
            lastMonthFilters = {
                status: document.getElementById('last-month-status-filter').value,
                priority: document.getElementById('last-month-priority-filter').value,
                search: document.getElementById('last-month-search').value,
                date_from: document.getElementById('last-month-date-from').value,
                date_to: document.getElementById('last-month-date-to').value
            };

            // Remove empty filters
            Object.keys(lastMonthFilters).forEach(key => {
                if (!lastMonthFilters[key]) delete lastMonthFilters[key];
            });

            loadLastMonthReports();
        }

        // Event listeners for last month filters
        document.getElementById('last-month-status-filter')?.addEventListener('change', applyLastMonthFilters);
        document.getElementById('last-month-priority-filter')?.addEventListener('change', applyLastMonthFilters);
        document.getElementById('last-month-date-from')?.addEventListener('change', applyLastMonthFilters);
        document.getElementById('last-month-date-to')?.addEventListener('change', applyLastMonthFilters);
        document.getElementById('last-month-search')?.addEventListener('keyup', applyLastMonthFilters);

        document.getElementById('last-month-clear-filters')?.addEventListener('click', () => {
            document.getElementById('last-month-status-filter').value = '';
            document.getElementById('last-month-priority-filter').value = '';
            document.getElementById('last-month-search').value = '';
            const dateRange = getLastMonthDateRange();
            document.getElementById('last-month-date-from').value = dateRange.start;
            document.getElementById('last-month-date-to').value = dateRange.end;
            lastMonthFilters = {
                date_from: dateRange.start,
                date_to: dateRange.end
            };
            loadLastMonthReports();
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
