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
if (!in_array($user['role'], ['super_admin', 'maintenance_admin', 'maintenance_staff'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

// Temporary consolidation: use a single All Reports page to avoid duplicate pages.
$redirectTarget = '/School_Facility_Maintenance_System/frontend/pages/reports.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $redirectTarget .= '?' . $_SERVER['QUERY_STRING'];
}
header('Location: ' . $redirectTarget);
exit;

$isStaff = (($user['role'] ?? '') === 'maintenance_staff');

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

<main class="container maintenance-reports-page">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2><?php echo $isStaff ? 'My Reports' : 'Maintenance Reports'; ?></h2>
                <p class="text-muted mb-0"><?php echo $isStaff ? 'View your own maintenance reports' : 'View and manage all maintenance reports'; ?></p>
            </div>
            <?php if ($isStaff): ?>
            <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-create-report.php" class="btn btn-primary">
                ➕ New Report
            </a>
            <?php endif; ?>
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

            <div id="week-pagination" class="week-pagination" style="margin-bottom: 16px;"></div>
            
            <!-- Reports Table -->
            <div id="reports-container">
                <div class="loading">Loading reports...</div>
            </div>

            <!-- Pagination -->
            <div id="pagination-container" class="reports-pagination"></div>
        </div>
    </div>
</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-reports-list.inline.css">

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
let allReports = [];
let currentPage = 1;
let rowsPerPage = 20;
let currentFilters = {};
let lastMonthOnly = false;
let selectedWeek = 0;

const API_BASE = '/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php';

function getReportWeekNumber(createdAt) {
    if (!createdAt) return null;
    const raw = String(createdAt).trim();
    const directMatch = raw.match(/^(\d{4}-\d{2}-\d{2})/);
    let date;

    if (directMatch) {
        date = new Date(`${directMatch[1]}T00:00:00`);
    } else {
        date = new Date(raw.replace(' ', 'T'));
    }

    if (Number.isNaN(date.getTime())) return null;

    const day = date.getDate();
    if (day <= 7) return 1;
    if (day <= 14) return 2;
    if (day <= 21) return 3;
    return 4;
}

function getReportsBySelectedWeek(reports) {
    if (!selectedWeek) return reports;
    return reports.filter((report) => getReportWeekNumber(report.created_at) === selectedWeek);
}

function renderWeekPagination(sourceReports) {
    const container = document.getElementById('week-pagination');
    if (!container) return;

    const counts = { 1: 0, 2: 0, 3: 0, 4: 0 };
    sourceReports.forEach((report) => {
        const week = getReportWeekNumber(report.created_at);
        if (week && counts[week] !== undefined) {
            counts[week] += 1;
        }
    });

    let html = '<div class="week-pagination-controls">';
    html += `<button type="button" class="week-page-btn ${selectedWeek === 0 ? 'active' : ''}" data-week="0">All Weeks (${sourceReports.length})</button>`;

    for (let week = 1; week <= 4; week += 1) {
        const isActive = selectedWeek === week ? 'active' : '';
        const isDisabled = counts[week] === 0 ? 'disabled' : '';
        html += `<button type="button" class="week-page-btn ${isActive}" data-week="${week}" ${isDisabled}>Week ${week} (${counts[week]})</button>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

function renderReportsView() {
    renderWeekPagination(allReports);
    const filteredReports = getReportsBySelectedWeek(allReports);
    const totalReports = filteredReports.length;
    const totalPages = Math.max(1, Math.ceil(totalReports / rowsPerPage));

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const pagedReports = filteredReports.slice(startIndex, endIndex);

    displayReports(pagedReports);
    renderPagination(totalReports, totalPages, startIndex);
}

function renderPagination(totalReports, totalPages, startIndex) {
    const container = document.getElementById('pagination-container');
    if (!container) return;

    if (totalReports === 0) {
        container.innerHTML = '';
        return;
    }

    const endIndex = Math.min(startIndex + rowsPerPage, totalReports);
    const pageButtons = [];
    for (let page = 1; page <= totalPages; page += 1) {
        pageButtons.push(`
            <button type="button" class="reports-pagination-btn ${currentPage === page ? 'active' : ''}" data-page="${page}">${page}</button>
        `);
    }

    container.innerHTML = `
        <div class="reports-pagination-summary">Showing <strong>${startIndex + 1}-${endIndex}</strong> of <strong>${totalReports}</strong> reports</div>
        <div class="reports-pagination-controls">
            <button type="button" class="reports-pagination-btn" data-page="1" ${currentPage === 1 ? 'disabled' : ''}>&laquo;</button>
            <button type="button" class="reports-pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>&lsaquo;</button>
            ${pageButtons.join('')}
            <button type="button" class="reports-pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>&rsaquo;</button>
            <button type="button" class="reports-pagination-btn" data-page="${totalPages}" ${currentPage === totalPages ? 'disabled' : ''}>&raquo;</button>
        </div>
        <label class="reports-pagination-size">
            Rows per page:
            <select id="rows-per-page-select">
                <option value="5" ${rowsPerPage === 5 ? 'selected' : ''}>5</option>
                <option value="7" ${rowsPerPage === 7 ? 'selected' : ''}>7</option>
                <option value="10" ${rowsPerPage === 10 ? 'selected' : ''}>10</option>
                <option value="20" ${rowsPerPage === 20 ? 'selected' : ''}>20</option>
            </select>
        </label>
    `;
}

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

function getCurrentMonthDateRange() {
    const today = new Date();
    const start = new Date(today.getFullYear(), today.getMonth(), 1);
    return {
        start: start.toISOString().split('T')[0],
        end: today.toISOString().split('T')[0]
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
            per_page: 200,
            ...currentFilters
        });

        const response = await fetch(`${API_BASE}?${params}`);
        const data = await response.json();

        if (data.success) {
            allReports = data.data.reports;
            renderReportsView();
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
    
    const isDark = document.documentElement.getAttribute('data-theme-resolved') === 'dark';
    const tableBg = isDark ? '#111827' : '#ffffff';
    const headerBg = isDark ? '#1f2937' : '#f3f4f6';
    const headerText = isDark ? '#f3f4f6' : '#1f2937';
    const rowOddBg = isDark ? '#111827' : '#ffffff';
    const rowEvenBg = isDark ? '#0f172a' : '#f9fafb';
    const borderColor = isDark ? '#374151' : '#d1d5db';
    const textPrimary = isDark ? '#f3f4f6' : '#1f2937';
    const textMuted = isDark ? '#cbd5e1' : '#4b5563';

    let html = `<table class="table dashboard-table" style="width: 100%; border-collapse: collapse; background: ${tableBg}; border: 1px solid ${borderColor};">`;
    html += '<thead><tr>';
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">ID</th>`;
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">Title</th>`;
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">Location</th>`;
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">Priority</th>`;
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">Status</th>`;
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">Assigned To</th>`;
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">Created</th>`;
    html += `<th style="background: ${headerBg}; color: ${headerText}; border: 1px solid ${borderColor};">Action</th>`;
    html += '</tr></thead><tbody>';
    
    reports.forEach((report, index) => {
        const priorityClass = UI.getPriorityBadge(report.priority);
        const statusClass = UI.getStatusBadge(report.status);
        const createdDate = UI.formatDate(report.created_at);
        const rowBg = index % 2 === 0 ? rowOddBg : rowEvenBg;
        
        html += `<tr style="background: ${rowBg};">`;
        html += `<td style="color: ${textPrimary}; border: 1px solid ${borderColor};">#${report.report_id}</td>`;
        html += `<td style="color: ${textPrimary}; border: 1px solid ${borderColor};"><strong>${report.title}</strong></td>`;
        html += `<td style="color: ${textMuted}; border: 1px solid ${borderColor};">${report.location}</td>`;
        html += `<td style="border: 1px solid ${borderColor};"><span class="badge ${priorityClass}">${report.priority.toUpperCase()}</span></td>`;
        html += `<td style="border: 1px solid ${borderColor};"><span class="badge ${statusClass}">${report.status.replace('_', ' ').toUpperCase()}</span></td>`;
        html += `<td style="color: ${textMuted}; border: 1px solid ${borderColor};">${report.assigned_name || 'Unassigned'}</td>`;
        html += `<td style="color: ${textMuted}; border: 1px solid ${borderColor};">${createdDate}</td>`;
        html += `<td style="border: 1px solid ${borderColor};"><a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=${report.report_id}" class="btn btn-sm btn-primary">View</a></td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Apply filters
function applyFilters() {
    const dateFromValue = document.getElementById('filter-date-from').value;
    const dateToValue = document.getElementById('filter-date-to').value;

    if (!dateFromValue && !dateToValue) {
        if (lastMonthOnly) {
            const lastRange = getLastMonthDateRange();
            document.getElementById('filter-date-from').value = lastRange.start;
            document.getElementById('filter-date-to').value = lastRange.end;
        } else {
            const currentRange = getCurrentMonthDateRange();
            document.getElementById('filter-date-from').value = currentRange.start;
            document.getElementById('filter-date-to').value = currentRange.end;
        }
    }

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
    
    currentPage = 1;
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
    const range = getCurrentMonthDateRange();
    document.getElementById('filter-date-from').value = range.start;
    document.getElementById('filter-date-to').value = range.end;
    currentFilters = {};
    lastMonthOnly = false;
    selectedWeek = 0;
    currentPage = 1;
    loadReports(1);
});

// Last Month button handler
document.getElementById('last-month-btn').addEventListener('click', () => {
    const dateRange = getLastMonthDateRange();
    lastMonthOnly = true;
    document.getElementById('filter-date-from').value = dateRange.start;
    document.getElementById('filter-date-to').value = dateRange.end;
    applyFilters();
});

// Clear date filters button
document.getElementById('clear-date-filters').addEventListener('click', () => {
    const range = getCurrentMonthDateRange();
    document.getElementById('filter-date-from').value = range.start;
    document.getElementById('filter-date-to').value = range.end;
    lastMonthOnly = false;
    selectedWeek = 0;
    currentPage = 1;
    applyFilters();
});

document.getElementById('pagination-container').addEventListener('click', (event) => {
    const pageButton = event.target.closest('[data-page]');
    if (!pageButton || pageButton.hasAttribute('disabled')) return;

    const nextPage = Number(pageButton.dataset.page);
    if (Number.isNaN(nextPage) || nextPage < 1) return;

    currentPage = nextPage;
    renderReportsView();
});

document.getElementById('pagination-container').addEventListener('change', (event) => {
    if (event.target.id !== 'rows-per-page-select') return;

    const nextRows = Number(event.target.value);
    if (Number.isNaN(nextRows) || nextRows < 1) return;

    rowsPerPage = nextRows;
    currentPage = 1;
    renderReportsView();
});

document.getElementById('week-pagination').addEventListener('click', (event) => {
    const button = event.target.closest('.week-page-btn');
    if (!button || button.hasAttribute('disabled')) return;

    const nextWeek = Number(button.dataset.week);
    if (Number.isNaN(nextWeek)) return;

    selectedWeek = nextWeek;
    currentPage = 1;
    renderReportsView();
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Check if coming from "Last Month" button
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('last_month') === '1') {
        const dateRange = getLastMonthDateRange();
        lastMonthOnly = true;
        document.getElementById('filter-date-from').value = dateRange.start;
        document.getElementById('filter-date-to').value = dateRange.end;
    } else {
        const range = getCurrentMonthDateRange();
        document.getElementById('filter-date-from').value = range.start;
        document.getElementById('filter-date-to').value = range.end;
    }
    selectedWeek = 0;
    loadReports(1);
});
</script>

</body>
</html>


