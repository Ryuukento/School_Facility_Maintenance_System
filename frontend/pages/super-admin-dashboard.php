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
            color: #1e3a5f;
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
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #1e3a5f;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card.users {
            border-left-color: #3b82f6;
        }

        .stat-card.reports {
            border-left-color: #10b981;
        }

        .stat-card.departments {
            border-left-color: #f59e0b;
        }

        .stat-card.pending {
            border-left-color: #ef4444;
        }

        .stat-card.overdue {
            border-left-color: #f97316;
        }

        .stat-card.completed {
            border-left-color: #6366f1;
        }

        .stat-label {
            color: #6b7280;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-value {
            color: #1e3a5f;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-detail {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 8px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
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
            color: #1e3a5f;
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
            color: #1e3a5f;
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
            color: #1e3a5f;
            font-weight: 500;
        }

        .overview-list .count {
            background: #e5e7eb;
            color: #1e3a5f;
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
            color: #1e3a5f;
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
            background: #1e3a5f;
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
            color: #1e3a5f;
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
            border-top-color: #1e3a5f;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
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
                font-size: 24px;
            }

            .stat-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header/Navigation -->
    <?php include '../includes/header.php'; ?>
    
    <div class="system-dashboard">
        <h1>System Administration Dashboard</h1>
        <p class="dashboard-subtitle">System-wide overview and statistics</p>

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
            <div class="chart-container">
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
    </div>

    <script>
        let statusChart, priorityChart, departmentChart, trendChart;

        // Load all dashboard data
        async function loadDashboardData() {
            try {
                // Load statistics
                const statsResponse = await fetch('/backend/api/super-admin-dashboard-api.php?action=getDashboardStats');
                const statsData = await statsResponse.json();
                renderStatistics(statsData);

                // Load chart data
                const chartResponse = await fetch('/backend/api/super-admin-dashboard-api.php?action=getChartData');
                const chartData = await chartResponse.json();
                renderCharts(chartData);

                // Load system overview
                const overviewResponse = await fetch('/backend/api/super-admin-dashboard-api.php?action=getSystemOverview');
                const overviewData = await overviewResponse.json();
                renderOverview(overviewData);

                // Load recent activity
                const activityResponse = await fetch('/backend/api/super-admin-dashboard-api.php?action=getRecentActivity');
                const activityData = await activityResponse.json();
                renderActivity(activityData);

            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        function renderStatistics(data) {
            if (!data.success) return;

            const container = document.getElementById('statsContainer');
            container.innerHTML = `
                <div class="stat-card users">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value">${data.totalUsers}</div>
                    <div class="stat-detail">
                        Super Admin: ${data.roleBreakdown.super_admin || 0}<br>
                        Dept Admin: ${data.roleBreakdown.department_admin || 0}<br>
                        Staff: ${data.roleBreakdown.maintenance_staff || 0}
                    </div>
                </div>

                <div class="stat-card reports">
                    <div class="stat-label">Total Reports</div>
                    <div class="stat-value">${data.totalReports}</div>
                    <div class="stat-detail">
                        Completed: ${data.statusBreakdown.completed || 0}
                    </div>
                </div>

                <div class="stat-card departments">
                    <div class="stat-label">Departments</div>
                    <div class="stat-value">${data.totalDepartments}</div>
                    <div class="stat-detail">
                        Active maintenance departments
                    </div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-label">Pending Reports</div>
                    <div class="stat-value">${data.pendingReports}</div>
                    <div class="stat-detail">
                        Need assignment or action
                    </div>
                </div>

                <div class="stat-card overdue">
                    <div class="stat-label">Overdue Reports</div>
                    <div class="stat-value">${data.overdueReports}</div>
                    <div class="stat-detail">
                        Past due date, not completed
                    </div>
                </div>

                <div class="stat-card completed">
                    <div class="stat-label">Completed (This Month)</div>
                    <div class="stat-value">${data.completedThisMonth}</div>
                    <div class="stat-detail">
                        Current month achievement
                    </div>
                </div>
            `;
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
                type: 'doughnut',
                data: {
                    labels: data.priorityChart.labels,
                    datasets: [{
                        data: data.priorityChart.data,
                        backgroundColor: data.priorityChart.colors,
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

            const ctx3 = document.getElementById('departmentChart').getContext('2d');
            departmentChart = new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: data.departmentChart.labels,
                    datasets: [{
                        label: 'Number of Reports',
                        data: data.departmentChart.data,
                        backgroundColor: '#1e3a5f',
                        borderColor: '#1e3a5f',
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
                        borderColor: '#1e3a5f',
                        backgroundColor: 'rgba(30, 58, 95, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#1e3a5f',
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
            deptList.innerHTML = data.departmentUsers
                .map(dept => `
                    <li>
                        <span class="name">${dept.name}</span>
                        <span class="count">${dept.count || 0} users</span>
                    </li>
                `)
                .join('');

            // Active staff
            const staffList = document.getElementById('activeStaffList');
            staffList.innerHTML = data.activeStaff.length ? data.activeStaff
                .map(staff => `
                    <li>
                        <span class="name">${staff.full_name}</span>
                        <span class="count">${staff.assigned_count} assigned</span>
                    </li>
                `)
                .join('') : '<li><span style="color: #9ca3af;">No staff data available</span></li>';
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
        document.addEventListener('DOMContentLoaded', loadDashboardData);

        // Refresh every 5 minutes
        setInterval(loadDashboardData, 5 * 60 * 1000);
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
