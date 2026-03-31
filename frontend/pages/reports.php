<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;
$currentRole = strtolower(trim((string)($currentUser['role'] ?? '')));
$canCreateReport = in_array($currentRole, ['maintenance_staff', 'eelab_staff', 'maintenance_personnel'], true);

$departments = [];
try {
    require_once __DIR__ . '/../../backend/config/database.php';
    $deptPdo = getDBConnection();
    $deptStmt = $deptPdo->query("SELECT department_id, name FROM departments WHERE status = 'active' ORDER BY name");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $departments = [];
}

$pageTitle = 'All Reports - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container reports-page-container">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div style="flex: 1;">
                <h2>Maintenance Reports</h2>
                <p class="text-muted mb-0">View and manage all maintenance reports</p>
            </div>
            <div class="d-flex gap-sm align-center" style="flex-wrap: wrap;">
                <button type="button" id="print-report-btn" class="btn btn-secondary" style="height: fit-content; margin-top: 0; display: inline-flex; align-items: center; gap: 8px;">
                    Print Summary Report
                </button>
                <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php?last_month=1" id="last-month-report-link" class="btn btn-secondary" style="height: fit-content; margin-top: 0; display: inline-flex; align-items: center; gap: 8px;">
                    <span>📅</span> Last Month Reports
                </a>
                <?php if ($canCreateReport): ?>
                <a href="/School_Facility_Maintenance_System/frontend/pages/create-report.php" class="btn btn-primary" style="height: fit-content; margin-top: 0;">
                    + New Report
                </a>
                <?php endif; ?>
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

            <div id="week-pagination" class="week-pagination" style="margin-bottom: 16px;"></div>
            
            <!-- Reports Table -->
            <div id="reports-container">
                <?php
                // PHP fallback: fetch reports on server side in case JS fails
                try {
                    require_once __DIR__ . '/../../backend/config/database.php';
                    $pdo = getDBConnection();

                    $isLastMonth = isset($_GET['last_month']) && $_GET['last_month'] === '1';
                    if ($isLastMonth) {
                        $dateFrom = date('Y-m-01', strtotime('first day of last month'));
                        $dateTo = date('Y-m-t', strtotime('last month'));
                    } else {
                        $dateFrom = date('Y-m-01');
                        $dateTo = date('Y-m-d');
                    }

                    $stmt = $pdo->prepare("SELECT r.report_id, r.title, r.priority, r.status, r.location,
                                                  r.created_at, creator.full_name as creator_name
                                           FROM maintenance_reports r
                                           LEFT JOIN users creator ON r.created_by = creator.user_id
                                           WHERE DATE(r.created_at) BETWEEN ? AND ?
                                           ORDER BY r.created_at DESC");
                    $stmt->execute([$dateFrom, $dateTo]);
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
                        echo '<a href="report-detail.php?id=' . $r['report_id'] . '" class="btn btn-sm btn-primary">View</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<div class="text-muted" style="padding:20px;">No reports found in the database. Create a report or import sample data.</div>';
                }
                ?>
            </div>

            <div id="pagination-container" class="reports-pagination"></div>
        </div>
    </div>
</main>

<div id="report-edit-modal" class="report-edit-modal" aria-hidden="true">
    <div class="report-edit-modal-card">
        <div class="report-edit-modal-header">
            <h3 id="report-edit-title">Edit Maintenance Report</h3>
            <button type="button" class="report-edit-close" id="report-edit-close" aria-label="Close">&times;</button>
        </div>
        <div class="report-edit-modal-body">
            <div id="report-edit-alert"></div>
            <form id="report-edit-form">
                <div class="form-group">
                    <label for="report_edit_title">Report Title *</label>
                    <input type="text" id="report_edit_title" required>
                </div>

                <div class="form-group">
                    <label for="report_edit_location">Location *</label>
                    <input type="text" id="report_edit_location" required>
                </div>

                <div class="form-group">
                    <label for="report_edit_department">Department</label>
                    <select id="report_edit_department">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo (int)$dept['department_id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="report_edit_priority">Priority *</label>
                    <select id="report_edit_priority" required>
                        <option value="low">Low - Can wait</option>
                        <option value="medium">Medium - Normal</option>
                        <option value="high">High - Important</option>
                        <option value="urgent">Urgent - Critical</option>
                        <option value="critical">Critical - Immediate</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="report_edit_status">Status</label>
                    <select id="report_edit_status">
                        <option value="submitted">Submitted</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="report_edit_description">Description *</label>
                    <textarea id="report_edit_description" rows="4" required></textarea>
                </div>

                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="report-edit-save-btn">Save Changes</button>
                    <button type="button" class="btn btn-secondary" id="report-edit-cancel-btn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="report-edit-delete-btn">Delete Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="print-report-modal" class="report-edit-modal" aria-hidden="true">
    <div class="report-edit-modal-card print-report-modal-card">
        <div class="report-edit-modal-header">
            <h3>Print Report Filters</h3>
            <button type="button" class="report-edit-close" id="print-report-close" aria-label="Close">&times;</button>
        </div>
        <div class="report-edit-modal-body">
            <div class="form-group">
                <label for="print-filter-mode">Print Scope</label>
                <select id="print-filter-mode" class="form-control">
                    <option value="current">Current filtered results</option>
                    <option value="week">Specific week of month</option>
                    <option value="date">Specific date</option>
                </select>
            </div>

            <div class="form-group" id="print-week-group" style="display: none;">
                <label for="print-week-select">Week</label>
                <select id="print-week-select" class="form-control">
                    <option value="1">Week 1</option>
                    <option value="2">Week 2</option>
                    <option value="3">Week 3</option>
                    <option value="4">Week 4</option>
                </select>
            </div>

            <div class="form-group" id="print-date-group" style="display: none;">
                <label for="print-date-input">Date</label>
                <input type="date" id="print-date-input" class="form-control">
            </div>

            <div class="d-flex gap-sm">
                <button type="button" class="btn btn-secondary" id="print-modal-cancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="print-modal-generate">Generate Print</button>
            </div>
        </div>
    </div>
</div>

<style>
.reports-page-container {
    width: calc(100% - var(--sidebar-width));
    max-width: calc(100% - var(--sidebar-width));
    margin-left: var(--sidebar-width);
    margin-right: 0;
    padding-left: 24px;
    padding-right: 24px;
}

.navbar .navbar-container {
    max-width: none;
    margin: 0;
    padding-left: 24px;
    padding-right: 24px;
}

#sidebar.collapsed ~ main.reports-page-container {
    width: calc(100% - var(--sidebar-width-collapsed));
    max-width: calc(100% - var(--sidebar-width-collapsed));
    margin-left: var(--sidebar-width-collapsed);
}

.reports-page-container .card {
    margin-top: 14px;
}

.reports-page-container .card-header {
    padding: 20px 24px;
}

.reports-page-container .card-body {
    padding: 18px 24px 24px;
}

.reports-page-container .form-control {
    min-height: 40px;
}

.reports-page-container .reports-table th,
.reports-page-container .reports-table td,
.reports-page-container #reports-container table th,
.reports-page-container #reports-container table td {
    padding: 10px 12px;
}

.reports-page-container .reports-table td:nth-child(1),
.reports-page-container .reports-table td:nth-child(3),
.reports-page-container .reports-table td:nth-child(4),
.reports-page-container .reports-table td:nth-child(7),
.reports-page-container .reports-table td:nth-child(8),
.reports-page-container #reports-container table td:nth-child(1),
.reports-page-container #reports-container table td:nth-child(3),
.reports-page-container #reports-container table td:nth-child(4),
.reports-page-container #reports-container table td:nth-child(7),
.reports-page-container #reports-container table td:nth-child(8) {
    white-space: nowrap;
}

.reports-pagination {
    margin-top: 20px;
    padding: 14px 20px;
    border: 1px solid rgba(138, 43, 226, 0.36);
    border-radius: 12px;
    background: rgba(18, 10, 45, 0.58);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.reports-pagination-summary {
    color: #c4b5fd;
    font-size: 14px;
}

.reports-pagination-summary strong {
    color: #ffffff;
}

.reports-pagination-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.reports-pagination-btn {
    min-width: 42px;
    height: 36px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1px solid rgba(196, 181, 253, 0.38);
    background: rgba(24, 12, 56, 0.75);
    color: #ede9fe;
    font-size: 14px;
    font-weight: 600;
    line-height: 1;
    cursor: pointer;
    transition: all 0.2s ease;
}

.reports-pagination-btn:hover:not(:disabled) {
    border-color: rgba(216, 180, 254, 0.8);
    background: rgba(37, 18, 78, 0.88);
}

.reports-pagination-btn.active {
    color: #ffffff;
    border-color: #8b5cf6;
    background: linear-gradient(160deg, #38206f 0%, #4b2f94 100%);
    box-shadow: 0 0 0 1px rgba(196, 181, 253, 0.3), 0 0 18px rgba(139, 92, 246, 0.35);
}

.reports-pagination-btn:disabled {
    opacity: 0.42;
    cursor: not-allowed;
}

.reports-pagination-size {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #c4b5fd;
    font-size: 14px;
}

.reports-pagination-size select {
    min-width: 96px;
    height: 38px;
    border-radius: 10px;
    border: 1px solid rgba(139, 92, 246, 0.56);
    background: #24134f;
    color: #f5f3ff;
    padding: 0 10px;
}

@media (max-width: 1366px) {
    .reports-page-container {
        width: calc(100% - var(--sidebar-width));
        max-width: calc(100% - var(--sidebar-width));
    }
}

@media (max-width: 992px) {
    .reports-page-container {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
    }

    .reports-page-container .card-header,
    .reports-page-container .card-body {
        padding-left: 16px;
        padding-right: 16px;
    }
}

@media (max-width: 640px) {
    .navbar .navbar-container,
    .reports-page-container {
        padding-left: 14px;
        padding-right: 14px;
    }

    .reports-pagination {
        padding: 12px;
    }

    .reports-pagination-summary,
    .reports-pagination-size {
        width: 100%;
    }

    .reports-pagination-controls {
        justify-content: flex-start;
    }
}

.report-edit-modal {
    position: fixed;
    inset: 0;
    background: rgba(10, 12, 20, 0.72);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2200;
    padding: 16px;
}

.report-edit-modal.show {
    display: flex;
}

.report-edit-modal-card {
    width: min(820px, 100%);
    max-height: calc(100vh - 32px);
    overflow: auto;
    border-radius: 14px;
    border: 1px solid rgba(138, 43, 226, 0.26);
    background: #f8f9fb;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
}

.report-edit-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    background: linear-gradient(180deg, #171028 0%, #120a22 100%);
    border-bottom: 1px solid rgba(138, 43, 226, 0.28);
}

.report-edit-modal-header h3 {
    margin: 0;
    color: #e8effa;
}

.report-edit-close {
    border: none;
    background: transparent;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    color: #e8effa;
}

.report-edit-modal-body {
    padding: 16px 18px 18px;
}

.print-report-modal-card {
    width: min(520px, 100%);
}

:root[data-theme-resolved='dark'] .report-edit-modal-card {
    background: #161a24;
    border-color: rgba(138, 43, 226, 0.4);
}

:root[data-theme-resolved='dark'] .report-edit-close {
    color: #e5e7eb;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
let allReports = [];
let currentPage = 1;
let rowsPerPage = 20;
let lastMonthOnly = false;
let selectedWeek = 0;
let statusGroupFilter = '';
const REPORTS_API = '/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php';
const LEGACY_REPORTS_API = '/School_Facility_Maintenance_System/backend/api/reports.php';
let activeEditReportId = null;

function applyStatusGroupFilter(reports) {
    if (!Array.isArray(reports) || !statusGroupFilter) {
        return Array.isArray(reports) ? reports : [];
    }

    if (statusGroupFilter === 'pending_tasks') {
        return reports.filter((report) => {
            const status = String(report.status || '').toLowerCase();
            return status === 'submitted' || status === 'assigned';
        });
    }

    return reports;
}

function getReportWeekNumber(createdAt) {
    const dateKey = extractReportDateKey(createdAt);
    if (!dateKey) return null;

    const date = new Date(`${dateKey}T00:00:00`);
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
    const groupedReports = applyStatusGroupFilter(allReports);
    renderWeekPagination(groupedReports);
    const filteredReports = getReportsBySelectedWeek(groupedReports);
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

function getLastMonthDateRange() {
    const today = new Date();
    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
    const lastMonthStart = new Date(lastMonthEnd.getFullYear(), lastMonthEnd.getMonth(), 1);

    return {
        start: lastMonthStart,
        end: lastMonthEnd
    };
}

function getCurrentMonthDateRange() {
    const today = new Date();
    const start = new Date(today.getFullYear(), today.getMonth(), 1);
    return {
        start,
        end: today
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
            const paginationEl = document.getElementById('pagination-container');
            if (paginationEl) paginationEl.innerHTML = '';
            return;
        }
        
        renderReportsView();
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
    html += '<div class="reports-table-wrap">';
    html += '<table class="reports-table">';
    html += '<thead>';
    html += '<tr>';
    html += '<th>ID</th>';
    html += '<th>Title</th>';
    html += '<th>Priority</th>';
    html += '<th>Status</th>';
    html += '<th>Location</th>';
    html += '<th>Created By</th>';
    html += '<th>Date</th>';
    html += '<th>Actions</th>';
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
        html += `<tr data-id="${reportId}">`;
        html += `<td>#${reportId}</td>`;
        html += `<td><strong>${title}</strong></td>`;
        html += `<td><span style="background: ${priorityColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${priority.toUpperCase()}</span></td>`;
        html += `<td><span style="background: ${statusColor}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${status.replace(/_/g, ' ').toUpperCase()}</span></td>`;
        html += `<td style="font-size: 13px;">${location}</td>`;
        html += `<td>${creatorName}</td>`;
        html += `<td style="font-size: 13px;">${createdAt}</td>`;
        html += `<td>
            <a href="report-detail.php?id=${reportId}" class="btn btn-sm btn-primary">View</a>
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

function showEditAlert(message, type = 'danger') {
    const alertBox = document.getElementById('report-edit-alert');
    if (!alertBox) return;
    if (!message) {
        alertBox.innerHTML = '';
        return;
    }
    alertBox.innerHTML = `<div class="alert alert-${type}">${escapeHtml(message)}</div>`;
}

function setEditLoading(isLoading) {
    const saveBtn = document.getElementById('report-edit-save-btn');
    const deleteBtn = document.getElementById('report-edit-delete-btn');
    if (saveBtn) {
        saveBtn.disabled = isLoading;
        saveBtn.textContent = isLoading ? 'Saving...' : 'Save Changes';
    }
    if (deleteBtn) {
        deleteBtn.disabled = isLoading;
    }
}

function closeEditModal() {
    const modal = document.getElementById('report-edit-modal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    activeEditReportId = null;
    const alertBox = document.getElementById('report-edit-alert');
    if (alertBox) alertBox.innerHTML = '';
}

function openEditModal() {
    const modal = document.getElementById('report-edit-modal');
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function normalizeGetResponse(payload) {
    if (!payload) return null;
    if (payload.report) return payload.report;
    if (payload.data && payload.data.report) return payload.data.report;
    if (payload.data && payload.data.report_id) return payload.data;
    if (payload.report_id) return payload;
    return null;
}

async function openEditReportModal(reportId) {
    try {
        activeEditReportId = Number(reportId);
        showEditAlert('', 'danger');
        const titleEl = document.getElementById('report-edit-title');
        if (titleEl) titleEl.textContent = `Edit Maintenance Report #${activeEditReportId}`;

        openEditModal();
        setEditLoading(true);

        const response = await fetch(`${LEGACY_REPORTS_API}?action=get&report_id=${activeEditReportId}`, {
            credentials: 'include'
        });
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load report details.');
        }

        const report = normalizeGetResponse(result);
        if (!report) {
            throw new Error('Invalid report payload.');
        }

        document.getElementById('report_edit_title').value = report.title || '';
        document.getElementById('report_edit_location').value = report.location || '';
        document.getElementById('report_edit_department').value = report.department_id || '';
        document.getElementById('report_edit_priority').value = (report.priority || 'medium').toLowerCase();
        document.getElementById('report_edit_status').value = (report.status || 'submitted').toLowerCase();
        document.getElementById('report_edit_description').value = report.description || '';

        const alertBox = document.getElementById('report-edit-alert');
        if (alertBox) alertBox.innerHTML = '';
    } catch (error) {
        showEditAlert(error.message || 'Could not load report.');
    } finally {
        setEditLoading(false);
    }
}

async function saveEditedReport(event) {
    event.preventDefault();
    if (!activeEditReportId) return;

    const payload = {
        report_id: activeEditReportId,
        title: document.getElementById('report_edit_title').value.trim(),
        location: document.getElementById('report_edit_location').value.trim(),
        department_id: document.getElementById('report_edit_department').value || null,
        priority: document.getElementById('report_edit_priority').value,
        status: document.getElementById('report_edit_status').value,
        description: document.getElementById('report_edit_description').value.trim()
    };

    if (!payload.title || !payload.location || !payload.description) {
        showEditAlert('Please fill in all required fields.');
        return;
    }

    try {
        setEditLoading(true);
        const response = await fetch(`${LEGACY_REPORTS_API}?action=update&report_id=${activeEditReportId}`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to update report.');
        }

        closeEditModal();
        filterReports();
    } catch (error) {
        showEditAlert(error.message || 'Failed to update report.');
    } finally {
        setEditLoading(false);
    }
}

async function deleteEditedReport() {
    if (!activeEditReportId) return;

    const confirmed = window.confirm('Delete this report? This action cannot be undone.');
    if (!confirmed) return;

    const saveBtn = document.getElementById('report-edit-save-btn');
    const deleteBtn = document.getElementById('report-edit-delete-btn');
    const originalDeleteText = deleteBtn ? deleteBtn.textContent : 'Delete Report';
    if (saveBtn) saveBtn.disabled = true;
    if (deleteBtn) {
        deleteBtn.disabled = true;
        deleteBtn.textContent = 'Deleting...';
    }

    try {
        const response = await fetch(`${LEGACY_REPORTS_API}?action=delete`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: activeEditReportId })
        });
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to delete report.');
        }

        closeEditModal();
        filterReports();
    } catch (error) {
        showEditAlert(error.message || 'Failed to delete report.');
    } finally {
        if (saveBtn) saveBtn.disabled = false;
        if (deleteBtn) {
            deleteBtn.disabled = false;
            deleteBtn.textContent = originalDeleteText;
        }
    }
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

function getPrintFilterSummary() {
    const statusValue = document.getElementById('filter-status')?.value || '';
    const priorityValue = document.getElementById('filter-priority')?.value || '';
    const dateFrom = document.getElementById('filter-date-from')?.value || '';
    const dateTo = document.getElementById('filter-date-to')?.value || '';

    const filters = [];
    if (statusValue) filters.push(`Status: ${statusValue.replace(/_/g, ' ')}`);
    if (priorityValue) filters.push(`Priority: ${priorityValue}`);
    if (dateFrom || dateTo) {
        filters.push(`Date: ${dateFrom || 'Any'} to ${dateTo || 'Any'}`);
    }
    if (selectedWeek) filters.push(`Week: ${selectedWeek}`);

    return filters.length ? filters.join(' | ') : 'No active filters';
}

function getPrintableReportsByMode(mode, weekValue, dateValue) {
    const sourceReports = [...allReports];

    if (mode === 'week') {
        const parsedWeek = Number(weekValue);
        if (Number.isNaN(parsedWeek) || parsedWeek < 1 || parsedWeek > 4) {
            return [];
        }
        return sourceReports.filter((report) => getReportWeekNumber(report.created_at) === parsedWeek);
    }

    if (mode === 'date') {
        if (!dateValue) return [];
        return sourceReports.filter((report) => extractReportDateKey(report.created_at) === dateValue);
    }

    return getReportsBySelectedWeek(sourceReports);
}

function togglePrintFilterFields() {
    const modeEl = document.getElementById('print-filter-mode');
    const weekGroup = document.getElementById('print-week-group');
    const dateGroup = document.getElementById('print-date-group');
    if (!modeEl || !weekGroup || !dateGroup) return;

    weekGroup.style.display = modeEl.value === 'week' ? 'block' : 'none';
    dateGroup.style.display = modeEl.value === 'date' ? 'block' : 'none';
}

function openPrintReportModal() {
    const modal = document.getElementById('print-report-modal');
    const modeEl = document.getElementById('print-filter-mode');
    const dateInput = document.getElementById('print-date-input');
    if (!modal || !modeEl || !dateInput) return;

    modeEl.value = 'current';
    dateInput.value = formatLocalDate(new Date());
    togglePrintFilterFields();

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closePrintReportModal() {
    const modal = document.getElementById('print-report-modal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function printSummaryReport() {
    const modeEl = document.getElementById('print-filter-mode');
    const weekEl = document.getElementById('print-week-select');
    const dateEl = document.getElementById('print-date-input');
    if (!modeEl || !weekEl || !dateEl) return;

    const mode = modeEl.value;
    const weekValue = weekEl.value;
    const dateValue = dateEl.value;
    const printableReports = getPrintableReportsByMode(mode, weekValue, dateValue);

    if (!Array.isArray(printableReports) || printableReports.length === 0) {
        alert('No reports available to print.');
        return;
    }

    let printSelectionSummary = 'Current filtered results';
    if (mode === 'week') {
        printSelectionSummary = `Specific week: Week ${weekValue}`;
    } else if (mode === 'date') {
        printSelectionSummary = `Specific date: ${dateValue}`;
    }

    const statusCounts = printableReports.reduce((acc, report) => {
        const key = (report.status || 'unknown').toLowerCase();
        acc[key] = (acc[key] || 0) + 1;
        return acc;
    }, {});

    const statusSummary = Object.keys(statusCounts)
        .sort()
        .map((status) => `${status.replace(/_/g, ' ')}: ${statusCounts[status]}`)
        .join(' | ');

    const rowsHtml = printableReports.map((report, index) => {
        const reportId = escapeHtml(String(report.report_id || ''));
        const title = escapeHtml(report.title || '');
        const priority = escapeHtml(String((report.priority || 'N/A')).toUpperCase());
        const status = escapeHtml(String((report.status || 'N/A')).replace(/_/g, ' ').toUpperCase());
        const location = escapeHtml(report.location || '');
        const createdBy = escapeHtml(report.creator_name || 'Unknown');
        const createdAt = escapeHtml(formatDate(report.created_at));

        return `
            <tr>
                <td>${index + 1}</td>
                <td>#${reportId}</td>
                <td>${title}</td>
                <td>${priority}</td>
                <td>${status}</td>
                <td>${location}</td>
                <td>${createdBy}</td>
                <td>${createdAt}</td>
            </tr>
        `;
    }).join('');

    const printedAt = new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });

    const printWindow = window.open('', '_blank', 'width=1200,height=900');
    if (!printWindow) {
        alert('Unable to open print preview. Please allow pop-ups for this site.');
        return;
    }

    closePrintReportModal();

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <title>Maintenance Reports Summary</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 24px;
                    color: #111827;
                }

                h1 {
                    margin: 0 0 6px;
                    font-size: 24px;
                }

                .meta {
                    margin-bottom: 18px;
                    color: #374151;
                    font-size: 13px;
                }

                .summary {
                    margin-bottom: 14px;
                    font-size: 13px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 12px;
                }

                th,
                td {
                    border: 1px solid #d1d5db;
                    padding: 8px;
                    text-align: left;
                    vertical-align: top;
                }

                th {
                    background: #f3f4f6;
                    font-weight: 700;
                }

                @media print {
                    body {
                        margin: 12mm;
                    }
                }
            </style>
        </head>
        <body>
            <h1>Maintenance Reports Summary</h1>
            <div class="meta">Printed at: ${escapeHtml(printedAt)}</div>
            <div class="summary"><strong>Filters:</strong> ${escapeHtml(getPrintFilterSummary())}</div>
            <div class="summary"><strong>Print scope:</strong> ${escapeHtml(printSelectionSummary)}</div>
            <div class="summary"><strong>Total Reports:</strong> ${printableReports.length}</div>
            <div class="summary"><strong>Status Breakdown:</strong> ${escapeHtml(statusSummary || 'N/A')}</div>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Report ID</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Created By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
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
    if (!hasManualDateRange) {
        if (lastMonthOnly) {
            const range = getLastMonthDateRange();
            filters.date_from = formatLocalDate(range.start);
            filters.date_to = formatLocalDate(range.end);
        } else {
            const range = getCurrentMonthDateRange();
            filters.date_from = formatLocalDate(range.start);
            filters.date_to = formatLocalDate(range.end);
        }
    } else {
        if (dateFrom) filters.date_from = dateFrom;
        if (dateTo) filters.date_to = dateTo;
    }

    if (status) filters.status = status;
    if (priority) filters.priority = priority;

    // Explicit status/priority selection disables group-based dashboard shortcuts.
    if (status || priority) {
        statusGroupFilter = '';
    }

    currentPage = 1;
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
    const range = getCurrentMonthDateRange();
    document.getElementById('filter-date-from').value = formatLocalDate(range.start);
    document.getElementById('filter-date-to').value = formatLocalDate(range.end);
    lastMonthOnly = false;
    selectedWeek = 0;
    currentPage = 1;

    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.delete('last_month');
    window.history.replaceState({}, '', nextUrl.toString());

    filterReports();
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

document.addEventListener('click', (event) => {
    const editButton = event.target.closest('.js-edit-report');
    if (editButton) {
        const reportId = Number(editButton.getAttribute('data-report-id'));
        if (!Number.isNaN(reportId) && reportId > 0) {
            openEditReportModal(reportId);
        }
        return;
    }

    const modal = document.getElementById('report-edit-modal');
    if (modal && event.target === modal) {
        closeEditModal();
    }

    const printModal = document.getElementById('print-report-modal');
    if (printModal && event.target === printModal) {
        closePrintReportModal();
    }
});

document.getElementById('report-edit-form').addEventListener('submit', saveEditedReport);
document.getElementById('report-edit-delete-btn').addEventListener('click', deleteEditedReport);
document.getElementById('report-edit-close').addEventListener('click', closeEditModal);
document.getElementById('report-edit-cancel-btn').addEventListener('click', closeEditModal);
document.getElementById('print-report-btn').addEventListener('click', openPrintReportModal);
document.getElementById('print-report-close').addEventListener('click', closePrintReportModal);
document.getElementById('print-modal-cancel').addEventListener('click', closePrintReportModal);
document.getElementById('print-filter-mode').addEventListener('change', togglePrintFilterFields);
document.getElementById('print-modal-generate').addEventListener('click', printSummaryReport);

document.getElementById('week-pagination').addEventListener('click', (event) => {
    const button = event.target.closest('.week-page-btn');
    if (!button || button.hasAttribute('disabled')) return;

    const nextWeek = Number(button.dataset.week);
    if (Number.isNaN(nextWeek)) return;

    selectedWeek = nextWeek;
    currentPage = 1;
    renderReportsView();
});

// Initialize - ensure API is available
function initializeReportsPage() {
    // Match maintenance dashboard behavior: always use server-side filters.
    filterReports();
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);

    const statusParam = (urlParams.get('status') || '').toLowerCase();
    const priorityParam = (urlParams.get('priority') || '').toLowerCase();
    const dateScopeParam = (urlParams.get('date_scope') || '').toLowerCase();
    const statusGroupParam = (urlParams.get('status_group') || '').toLowerCase();

    if (statusGroupParam === 'pending_tasks') {
        statusGroupFilter = 'pending_tasks';
    }

    if (urlParams.get('last_month') === '1') {
        lastMonthOnly = true;
        const range = getLastMonthDateRange();
        document.getElementById('filter-date-from').value = formatLocalDate(range.start);
        document.getElementById('filter-date-to').value = formatLocalDate(range.end);
        const btn = document.getElementById('last-month-report-link');
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-primary');
    } else if (dateScopeParam === 'today') {
        const today = formatLocalDate(new Date());
        document.getElementById('filter-date-from').value = today;
        document.getElementById('filter-date-to').value = today;
    } else {
        const range = getCurrentMonthDateRange();
        document.getElementById('filter-date-from').value = formatLocalDate(range.start);
        document.getElementById('filter-date-to').value = formatLocalDate(range.end);
    }

    if (statusParam) {
        document.getElementById('filter-status').value = statusParam;
    }

    if (priorityParam) {
        document.getElementById('filter-priority').value = priorityParam;
    }

    console.log('📄 DOM Content Loaded - starting initialization');
    selectedWeek = 0;
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
