<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'All Reports - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div style="flex: 1;">
                <h2>Maintenance Reports</h2>
                <p class="text-muted mb-0">View and manage all maintenance reports</p>
            </div>
            <div class="d-flex gap-sm align-center" style="flex-wrap: wrap;">
                <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php?last_month=1" id="last-month-report-link" class="btn btn-secondary" style="height: fit-content; margin-top: 0; display: inline-flex; align-items: center; gap: 8px;">
                    <span>📅</span> Last Month Reports
                </a>
                <a href="/School_Facility_Maintenance_System/frontend/pages/create-report.php" class="btn btn-primary" style="height: fit-content; margin-top: 0;">
                    + New Report
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filters -->
            <div class="d-flex gap-sm mb-md" style="flex-wrap: wrap; align-items: center;">
                <select id="filter-status" class="form-control" style="max-width: 200px;">
                    <option value="">All Status</option>
                    <option value="submitted">Submitted</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="closed">Closed</option>
                </select>
                
                <select id="filter-priority" class="form-control" style="max-width: 200px;">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>

                <input type="date" id="filter-date-from" class="form-control" style="max-width: 180px;" title="From date">
                <input type="date" id="filter-date-to" class="form-control" style="max-width: 180px;" title="To date">
                <button id="clear-date-filters" class="btn btn-secondary" type="button">Clear Date</button>
            </div>
            
            <!-- Reports Table -->
            <div id="reports-container">
                <?php
                // PHP fallback: fetch reports on server side in case JS fails
                try {
                    require_once __DIR__ . '/../../backend/config/database.php';
                    $pdo = getDBConnection();
                    $stmt = $pdo->query("SELECT r.report_id, r.title, r.priority, r.status, r.location,
                                               r.created_at, creator.full_name as creator_name
                                        FROM maintenance_reports r
                                        LEFT JOIN users creator ON r.created_by = creator.user_id
                                        ORDER BY r.created_at DESC");
                    $phpReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $phpReports = [];
                }

                if (!empty($phpReports)) {
                    echo '<table class="table" style="width:100%;">';
                    echo '<thead><tr><th>ID</th><th>Title</th><th>Location</th><th>Priority</th><th>Status</th><th>Created By</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
                    foreach ($phpReports as $r) {
                        $date = date('M d, Y', strtotime($r['created_at']));
                        echo '<tr>';
                        echo '<td>#' . htmlspecialchars($r['report_id']) . '</td>';
                        echo '<td><strong>' . htmlspecialchars($r['title']) . '</strong></td>';
                        echo '<td>' . htmlspecialchars($r['location']) . '</td>';
                        echo '<td>' . strtoupper(htmlspecialchars($r['priority'])) . '</td>';
                        echo '<td>' . strtoupper(str_replace('_',' ',htmlspecialchars($r['status']))) . '</td>';
                        echo '<td>' . htmlspecialchars($r['creator_name'] ?? '') . '</td>';
                        echo '<td>' . $date . '</td>';
                        echo '<td>';
                        echo '<a href="report-detail.php?id=' . $r['report_id'] . '" class="btn btn-sm btn-primary" style="margin-right:4px;">View</a>';
                        echo '<a href="edit-report.php?id=' . $r['report_id'] . '" class="btn btn-sm btn-secondary">Edit</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<div class="text-muted" style="padding:20px;">No reports found in the database. Create a report or import sample data.</div>';
                }
                ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
let allReports = [];
let lastMonthOnly = false;
const REPORTS_API = '/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php';

function getLastMonthDateRange() {
    const today = new Date();
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
    const lastMonthStart = new Date(lastMonthEnd.getFullYear(), lastMonthEnd.getMonth(), 1);

    return {
        start: lastMonthStart,
        end: lastMonthEnd
    };
}

function formatLocalDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function extractReportDateKey(createdAt) {
    if (!createdAt) return null;

    // MySQL DATETIME usually comes as "YYYY-MM-DD HH:MM:SS"
    const raw = String(createdAt).trim();
    const directMatch = raw.match(/^(\d{4}-\d{2}-\d{2})/);
    if (directMatch) {
        return directMatch[1];
    }

    // Fallback parser for other possible formats.
    const normalized = raw.replace(' ', 'T');
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) return null;
    return formatLocalDate(parsed);
}

// Load all reports
async function loadReports(filters = {}) {
    try {
        const params = new URLSearchParams({
            action: 'list',
            per_page: 200,
            ...filters
        });

        const res = await fetch(`${REPORTS_API}?${params.toString()}`, {
            credentials: 'include'
        });
        const response = await res.json();
        console.log('✅ API Response:', response);
        
        if (!response.success) {
            throw new Error(response.message || 'Failed to fetch reports');
        }
        
        if (!response.data || !Array.isArray(response.data.reports)) {
            console.warn('⚠️ Unexpected response format:', response);
            throw new Error('Invalid response format - missing reports array');
        }
        
        allReports = response.data.reports;
        console.log(`📊 Total reports loaded: ${allReports.length}`);
        
        if (allReports.length === 0) {
            document.getElementById('reports-container').innerHTML = 
                '<p class="text-muted text-center" style="padding: 20px;">No reports found. <a href="/School_Facility_Maintenance_System/frontend/pages/create-report.php">Create a new report</a></p>';
            return;
        }
        
        displayReports(allReports);
    } catch (error) {
        console.error('❌ Error loading reports:', error);
        console.error('Error stack:', error.stack);
        document.getElementById('reports-container').innerHTML = 
            '<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 4px;">' +
            '<strong>Error loading reports:</strong><br>' + 
            escapeHtml(error.message) + 
            '<br><br><small style="color: #999;">Check browser console (F12) for more details</small>' +
            '</div>';
    }
}

// Display reports
function displayReports(reports) {
    const container = document.getElementById('reports-container');
    
    console.log('📊 displayReports called with:', reports);
    
    if (!reports || reports.length === 0) {
        console.warn('⚠️ No reports to display');
        container.innerHTML = '<p class="text-muted text-center" style="padding: 20px;">No reports found. <a href="/School_Facility_Maintenance_System/frontend/pages/create-report.php">Create a new report</a></p>';
        return;
    }
    
    console.log('✅ Building table for', reports.length, 'reports');
    
    // Start building HTML
    let html = '';
    html += '<div style="width: 100%; overflow-x: auto;">';
    html += '<table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd;">';
    html += '<thead style="background: #f8f9fa;">';
    html += '<tr>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">ID</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Title</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Priority</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Status</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Location</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Created By</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Date</th>';
    html += '<th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Actions</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    reports.forEach((report) => {
        const reportId = report.report_id;
        const title = escapeHtml(report.title);
        const priority = (report.priority || 'medium').toLowerCase();
        const status = (report.status || 'unknown').toLowerCase();
        const location = escapeHtml(report.location);
        const creatorName = escapeHtml(report.creator_name || 'Unknown');
        const createdAt = formatDate(report.created_at);
        
        const priorityColor = getPriorityColor(priority);
        const statusColor = getStatusColor(status);
        
        // set data-id for highlighting later
        html += `<tr data-id="${reportId}" style="border-bottom: 1px solid #ddd;">`;
        html += `<td style="padding: 12px; border: 1px solid #ddd;">#${reportId}</td>`;
        html += `<td style="padding: 12px; border: 1px solid #ddd;"><strong>${title}</strong></td>`;
        html += `<td style="padding: 12px; border: 1px solid #ddd;"><span style="background: ${priorityColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${priority.toUpperCase()}</span></td>`;
        html += `<td style="padding: 12px; border: 1px solid #ddd;"><span style="background: ${statusColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${status.replace(/_/g, ' ').toUpperCase()}</span></td>`;
        html += `<td style="padding: 12px; border: 1px solid #ddd; font-size: 13px;">${location}</td>`;
        html += `<td style="padding: 12px; border: 1px solid #ddd;">${creatorName}</td>`;
        html += `<td style="padding: 12px; border: 1px solid #ddd; font-size: 13px;">${createdAt}</td>`;
        html += `<td style="padding: 12px; border: 1px solid #ddd;">
            <a href="report-detail.php?id=${reportId}" class="btn btn-sm btn-primary" style="margin-right:6px;">View</a>
            <a href="edit-report.php?id=${reportId}" class="btn btn-sm btn-secondary">Edit</a>
        </td>`;
        html += '</tr>';
    });
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    
    console.log('✅ HTML generated, setting to container');
    
    container.innerHTML = html;
    console.log('✅ Reports displayed successfully!');
    // check URL for new_id to highlight
    const newId = getQueryParam('new_id');
    if (newId) highlightNewReport(newId);
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

function getPriorityClass(priority) {
    switch (priority) {
        case 'urgent':
        case 'critical':
            return 'danger';
        case 'high':
            return 'warning';
        case 'medium':
            return 'info';
        case 'low':
            return 'secondary';
        default:
            return 'light';
    }
}

function getPriorityColor(priority) {
    switch (priority.toLowerCase()) {
        case 'urgent':
        case 'critical':
            return '#dc3545'; // red
        case 'high':
            return '#fd7e14'; // orange
        case 'medium':
            return '#0dcaf0'; // light blue
        case 'low':
            return '#6c757d'; // grey
        default:
            return '#6c757d';
    }
}

function getStatusClass(status) {
    switch (status) {
        case 'completed':
            return 'success';
        case 'in_progress':
        case 'assigned':
            return 'info';
        case 'submitted':
            return 'warning';
        case 'closed':
            return 'secondary';
        default:
            return 'light';
    }
}

function getStatusColor(status) {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'closed':
            return '#198754'; // green
        case 'in_progress':
        case 'assigned':
            return '#0dcaf0'; // light blue
        case 'submitted':
            return '#fd7e14'; // orange
        default:
            return '#6c757d'; // grey
    }
}

// Filter reports
function filterReports() {
    const status = document.getElementById('filter-status').value;
    const priority = document.getElementById('filter-priority').value;
    const dateFrom = document.getElementById('filter-date-from').value;
    const dateTo = document.getElementById('filter-date-to').value;
    const hasManualDateRange = Boolean(dateFrom || dateTo);
    const filters = {};

    // Quick mode applies only when date inputs are blank.
    if (lastMonthOnly && !hasManualDateRange) {
        const range = getLastMonthDateRange();
        filters.date_from = formatLocalDate(range.start);
        filters.date_to = formatLocalDate(range.end);
    } else {
        if (dateFrom) filters.date_from = dateFrom;
        if (dateTo) filters.date_to = dateTo;
    }

    if (status) filters.status = status;
    if (priority) filters.priority = priority;

    loadReports(filters);
}

// Event listeners
document.getElementById('filter-status').addEventListener('change', filterReports);
document.getElementById('filter-priority').addEventListener('change', filterReports);
document.getElementById('filter-date-from').addEventListener('change', () => {
    // Manual date range should take priority over quick last-month mode.
    if (document.getElementById('filter-date-from').value || document.getElementById('filter-date-to').value) {
        lastMonthOnly = false;
        const nextUrl = new URL(window.location.href);
        nextUrl.searchParams.delete('last_month');
        window.history.replaceState({}, '', nextUrl.toString());
    }
    filterReports();
});
document.getElementById('filter-date-to').addEventListener('change', () => {
    // Manual date range should take priority over quick last-month mode.
    if (document.getElementById('filter-date-from').value || document.getElementById('filter-date-to').value) {
        lastMonthOnly = false;
        const nextUrl = new URL(window.location.href);
        nextUrl.searchParams.delete('last_month');
        window.history.replaceState({}, '', nextUrl.toString());
    }
    filterReports();
});
document.getElementById('last-month-report-link').addEventListener('click', (event) => {
    event.preventDefault();
    lastMonthOnly = true;
    const range = getLastMonthDateRange();
    document.getElementById('filter-date-from').value = formatLocalDate(range.start);
    document.getElementById('filter-date-to').value = formatLocalDate(range.end);
    filterReports();

    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.set('last_month', '1');
    window.history.replaceState({}, '', nextUrl.toString());
});

document.getElementById('clear-date-filters').addEventListener('click', () => {
    document.getElementById('filter-date-from').value = '';
    document.getElementById('filter-date-to').value = '';
    lastMonthOnly = false;

    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.delete('last_month');
    window.history.replaceState({}, '', nextUrl.toString());

    filterReports();
});

// Initialize - ensure API is available
function initializeReportsPage() {
    // Match maintenance dashboard behavior: always use server-side filters.
    filterReports();
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('last_month') === '1') {
        lastMonthOnly = true;
        const range = getLastMonthDateRange();
        document.getElementById('filter-date-from').value = formatLocalDate(range.start);
        document.getElementById('filter-date-to').value = formatLocalDate(range.end);
        const btn = document.getElementById('last-month-report-link');
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-primary');
    }

    console.log('📄 DOM Content Loaded - starting initialization');
    initializeReportsPage();
});

// after loading reports, check query param for new_id to highlight
function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

function highlightNewReport(id) {
    if (!id) return;
    const row = document.querySelector(`#reports-container table tr[data-id='${id}']`);
    if (row) {
        row.style.transition = 'background-color 0.5s';
        row.style.backgroundColor = '#d4edda';
        setTimeout(() => { row.style.backgroundColor = ''; }, 3000);
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// modify displayReports to add data-id attributes
// NOTE: we'll insert additional logic inside displayReports function above - patch separately

</script>
