<?php
/**
 * Maintenance Reports List
 */
session_start();

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
    <title>All Reports - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="margin-top: 20px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2>Maintenance Reports</h2>
                <p class="text-muted mb-0">View and manage all maintenance reports</p>
            </div>
            <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-create-report.php" class="btn btn-primary">
                ➕ New Report
            </a>
        </div>
        
        <div class="card-body">
            <!-- Filter Buttons -->
            <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <button id="last-month-btn" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <span>📅</span> Last Month Reports
                </button>
                <button id="clear-date-filters" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <span>🔄</span> Clear Date Filter
                </button>
            </div>

            <!-- Filters -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 20px;">
                <input type="text" id="search" placeholder="Search title..." class="form-control" style="min-width: 200px;">
                
                <input type="date" id="filter-date-from" placeholder="From Date" class="form-control">
                
                <input type="date" id="filter-date-to" placeholder="To Date" class="form-control">
                
                <select id="filter-status" class="form-control">
                    <option value="">All Status</option>
                    <option value="submitted">Submitted</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="closed">Closed</option>
                </select>
                
                <select id="filter-priority" class="form-control">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                    <option value="critical">Critical</option>
                </select>

                <button id="clear-filters" class="btn btn-secondary" style="justify-self: start;">Clear All Filters</button>
            </div>
            
            <!-- Reports Table -->
            <div id="reports-container">
                <div class="loading">Loading reports...</div>
            </div>

            <!-- Pagination -->
            <div id="pagination-container" style="display: flex; gap: 10px; justify-content: center; align-items: center; margin-top: 20px;">
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
let allReports = [];
let currentPage = 1;
let currentFilters = {};

const API_BASE = '/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php';

// Helper function to get last month date range
function getLastMonthDateRange() {
    const today = new Date();
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0); // Last day of previous month
    const lastMonthStart = new Date(lastMonthEnd.getFullYear(), lastMonthEnd.getMonth(), 1); // First day of previous month
    
    return {
        start: lastMonthStart.toISOString().split('T')[0],
        end: lastMonthEnd.toISOString().split('T')[0]
    };
}

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

// Load reports
async function loadReports(page = 1) {
    try {
        const params = new URLSearchParams({
            action: 'list',
            page: page,
            per_page: 20,
            ...currentFilters
        });

        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();

        if (data.success) {
            allReports = data.data.reports;
            displayReports(allReports);
            currentPage = page;
        }
    } catch (error) {
        console.error('Error loading reports:', error);
        document.getElementById('reports-container').innerHTML = 
            '<p class="text-danger">Failed to load reports</p>';
    }
}

// Display reports
function displayReports(reports) {
    const container = document.getElementById('reports-container');
    
    if (reports.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No reports found</p>';
        return;
    }
    
    let html = '<table class="table dashboard-table" style="width: 100%;">';
    html += '<thead><tr>';
    html += '<th>ID</th><th>Title</th><th>Location</th><th>Priority</th><th>Status</th>';
    html += '<th>Assigned To</th><th>Created</th><th>Action</th>';
    html += '</tr></thead><tbody>';
    
    reports.forEach(report => {
        const priorityClass = UI.getPriorityBadge(report.priority);
        const statusClass = UI.getStatusBadge(report.status);
        const createdDate = UI.formatDate(report.created_at);
        
        html += '<tr>';
        html += `<td>#${report.report_id}</td>`;
        html += `<td><strong>${report.title}</strong></td>`;
        html += `<td>${report.location}</td>`;
        html += `<td><span class="badge ${priorityClass}">${report.priority.toUpperCase()}</span></td>`;
        html += `<td><span class="badge ${statusClass}">${report.status.replace('_', ' ').toUpperCase()}</span></td>`;
        html += `<td>${report.assigned_name || 'Unassigned'}</td>`;
        html += `<td>${createdDate}</td>`;
        html += `<td><a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=${report.report_id}" class="btn btn-sm btn-primary">View</a></td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Apply filters
function applyFilters() {
    currentFilters = {
        search: document.getElementById('search').value,
        status: document.getElementById('filter-status').value,
        priority: document.getElementById('filter-priority').value,
        date_from: document.getElementById('filter-date-from').value,
        date_to: document.getElementById('filter-date-to').value
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) delete currentFilters[key];
    });
    
    loadReports(1);
}

// Event listeners
document.getElementById('search').addEventListener('keyup', applyFilters);
document.getElementById('filter-status').addEventListener('change', applyFilters);
document.getElementById('filter-priority').addEventListener('change', applyFilters);
document.getElementById('filter-date-from').addEventListener('change', applyFilters);
document.getElementById('filter-date-to').addEventListener('change', applyFilters);

document.getElementById('clear-filters').addEventListener('click', () => {
    document.getElementById('search').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-priority').value = '';
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    currentFilters = {};
    loadReports(1);
});

// Last Month button handler
document.getElementById('last-month-btn').addEventListener('click', () => {
    const dateRange = getLastMonthDateRange();
    document.getElementById('filter-date-from').value = dateRange.start;
    document.getElementById('filter-date-to').value = dateRange.end;
    applyFilters();
});

// Clear date filters button
document.getElementById('clear-date-filters').addEventListener('click', () => {
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    applyFilters();
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Check if coming from "Last Month" button
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('last_month') === '1') {
        const dateRange = getLastMonthDateRange();
        document.getElementById('filter-date-from').value = dateRange.start;
        document.getElementById('filter-date-to').value = dateRange.end;
    }
    loadReports(1);
});
</script>

</body>
</html>
