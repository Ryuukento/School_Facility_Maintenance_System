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
// TASK 101 — "the three dashboards that show a real building COUNT keep
// their stat cards" (BuildingsOverviewNavigationAndHierarchicalSearchTest).
// Restored alongside the TASK 13.x dispatch stat cards below; this is the
// same read-only COUNT(*) query every other dashboard already runs for its
// own buildings stat (super-admin-dashboard.php's buildingsOverview,
// maintenance-dashboard.php's today-buildings-count).
$buildingsCount = 0;

try {
    $assignedStmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_reports WHERE assigned_to = ?");
    $assignedStmt->execute([(int)($user['user_id'] ?? 0)]);
    $assignedReportsCount = (int)$assignedStmt->fetchColumn();

    $buildingsStmt = $pdo->query("SELECT COUNT(*) FROM buildings");
    $buildingsCount = (int)$buildingsStmt->fetchColumn();
} catch (Throwable $e) {
    // Keep dashboard usable even if queries fail.
}
// Sprint 2 / Feature 1: "Total Reports" (system-wide) was repurposed into
// "My Open Reports" (pending + in_progress among this user's own assigned
// reports), computed client-side in loadStaffDashboardData() from data
// already fetched there — no new query needed here.
$pageTitle = 'Staff Dashboard - School Facility Maintenance System';
$pageStylesheets = [
    '/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css',
];
// chart-lite.js loaded by header.php — do not load again here
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- TASK 100/CreateReportNavigationConsolidationTest — "the two dashboards'
     quick actions still reach Create Report" is a pinned invariant of the
     All-Reports navigation consolidation: the sidebar/dashboard entry points
     were removed, but the underlying route must keep a live reference from
     both dashboards so it can never silently rot. maintenance-dashboard.php
     carries the identical hidden anchor for the same reason; every
     Maintenance Staff user reaching this page can always create a report
     (see reports.php's $canCreateReport gate), so no role check is needed
     here. -->
<a href="/School_Facility_Maintenance_System/frontend/pages/create-report.php" style="display:none" aria-hidden="true" tabindex="-1"></a>

<main class="container staff-dashboard-page">

    <div class="stats-grid">
        <div class="stat-card stat-card-total stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status_group=assigned_to_me" aria-label="Open assigned reports">
            <div class="stat-content">
                <p class="stat-label">Assigned to you</p>
                <h3 class="stat-value" id="my-assigned-reports"><?php echo (int)$assignedReportsCount; ?></h3>
                <p class="stat-meta text-muted">Reports waiting on your action</p>
            </div>
            <div class="stat-icon-chip"><?php echo ui_icon('file-text', ['size' => 22]); ?></div>
        </div>

        <!-- TASK 101 — "the three dashboards that show a real building COUNT
             keep their stat cards" (BuildingsOverviewNavigationAndHierarchicalSearchTest).
             This card displays data (a live COUNT(*) from buildings), rather
             than merely linking to the Buildings Overview sidebar module, so
             it was deliberately kept even after that module moved out of the
             dashboards. Same component and click-delegation pattern as every
             other card in this grid. -->
        <div class="stat-card stat-card-total stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/buildings-overview.php" aria-label="Open buildings overview">
            <div class="stat-content">
                <p class="stat-label">Buildings overview</p>
                <h3 class="stat-value" id="my-buildings"><?php echo (int)$buildingsCount; ?></h3>
                <p class="stat-meta text-muted">Tracked buildings in the system</p>
            </div>
            <div class="stat-icon-chip"><?php echo ui_icon('building', ['size' => 22]); ?></div>
        </div>

        <!-- TASK 13.1 §4 — "My Pending Releases". Reuses the existing stat-card
             component verbatim (.stat-card-pending + .stat-card-clickable +
             .stat-content/.stat-icon-chip); the delegated data-href handler
             further down already binds every .stat-card-clickable, so no new
             JS wiring is needed for the click.

             It deep-links to the dispatch list filtered to "approved" —
             approved-but-not-yet-released IS "waiting release". No "assigned
             to me" parameter is passed, and none is needed: for a Maintenance
             Staff user the API already constrains that list to their own
             assignments server-side (DispatchController::index()), which is
             also why the count below is trustworthy without any client-side
             filtering. Starts at 0 and is filled in by JS. -->
        <div class="stat-card stat-card-pending stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/dispatches.php?status=approved" aria-label="Open dispatches waiting for your release">
            <div class="stat-content">
                <p class="stat-label">My Pending Releases</p>
                <h3 class="stat-value" id="my-pending-releases">0</h3>
                <p class="stat-meta text-muted">Waiting Release</p>
                <!-- TASK 13.2 §4 — "Latest Assignment". Hidden until the count
                     request resolves, and left hidden when nothing is assigned,
                     so the card never shows a label with no value under it. The
                     code comes out of the SAME per_page=1 response that already
                     supplies the count — no second request, no new endpoint. -->
                <p class="stat-meta stat-meta-latest" id="my-pending-release-latest" hidden>
                    <span class="stat-meta-latest-label">Latest Assignment</span>
                    <span class="stat-meta-latest-code" id="my-pending-release-code"></span>
                </p>
            </div>
            <div class="stat-icon-chip"><?php echo ui_icon('package', ['size' => 22]); ?></div>
        </div>

        <div class="stat-card stat-card-total stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status_group=assigned_to_me" aria-label="Open my open reports">
            <div class="stat-content">
                <p class="stat-label">My Open Reports</p>
                <h3 class="stat-value" id="total-reports">0</h3>
                <p class="stat-meta text-muted">Assigned to you, not yet completed</p>
            </div>
            <div class="stat-icon-chip"><?php echo ui_icon('bar-chart', ['size' => 22]); ?></div>
        </div>

        <div class="stat-card stat-card-pending stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status=submitted" aria-label="Open pending reports">
            <div class="stat-content">
                <p class="stat-label">Pending</p>
                <h3 class="stat-value" id="my-pending">0</h3>
                <p class="stat-meta text-muted">Need action</p>
            </div>
            <div class="stat-icon-chip"><?php echo ui_icon('clock', ['size' => 22]); ?></div>
        </div>

        <div class="stat-card stat-card-progress stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status=in_progress" aria-label="Open in progress reports">
            <div class="stat-content">
                <p class="stat-label">In Progress</p>
                <h3 class="stat-value" id="my-in-progress">0</h3>
                <p class="stat-meta text-muted">Currently working</p>
            </div>
            <div class="stat-icon-chip"><?php echo ui_icon('wrench', ['size' => 22]); ?></div>
        </div>

        <div class="stat-card stat-card-completed stat-card-clickable" role="button" tabindex="0" data-href="/School_Facility_Maintenance_System/frontend/pages/reports.php?status=completed" aria-label="Open completed reports">
            <div class="stat-content">
                <p class="stat-label">Completed</p>
                <h3 class="stat-value" id="my-completed">0</h3>
                <p class="stat-meta text-muted">Finished reports</p>
            </div>
            <div class="stat-icon-chip"><?php echo ui_icon('check-circle', ['size' => 22]); ?></div>
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


</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/staff-dashboard.inline.css?v=20260921-2">
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/enterprise-dashboard.css?v=20260726-1">
<!-- TASK — Subtle purple card-border accent. One shared stylesheet for all
     three dashboards; recolours existing 1px borders only, so no card
     changes size. Loaded last. -->
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/dashboard-card-accent.css?v=20260921-2">

<!-- TASK 13.2 §4 — "Latest Assignment" line on the My Pending Releases card.
     Page-scoped block; the .stat-card component itself is untouched. The line
     carries .stat-meta as well, so it inherits that class's dark AND light
     theme colors from staff-dashboard.inline.css and nothing here needs to
     name a color. -->
<style>
.staff-dashboard-page .stat-meta-latest {
    margin-top: 6px;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.staff-dashboard-page .stat-meta-latest-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.staff-dashboard-page .stat-meta-latest-code {
    font-weight: 700;
    letter-spacing: 0.02em;
    font-variant-numeric: tabular-nums;
}

/* TASK 13.2 §9 — restores a visible keyboard focus indicator on the clickable
   stat cards. staff-dashboard.inline.css:395 sets `outline: none` on
   .stat-card-clickable:focus-visible and leaves only a box-shadow, which is
   not a reliable focus signal on a role="button" tabindex="0" element. This
   re-adds an outline in the accent already used by that same rule — no new
   color. NOTE: .stat-card-clickable is shared by every card in this grid, so
   this necessarily improves the focus ring on the sibling cards too; it is
   an additive a11y fix and changes nothing else about them. */
.staff-dashboard-page .stat-card-clickable:focus-visible {
    outline: 2px solid rgba(167, 139, 250, 0.9);
    outline-offset: 2px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardMonthSelection();
    window.addEventListener('sfms:monthSelected', onDashboardMonthSelected);
    loadStaffDashboardData();
    loadStaffChartData();
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
        // "My Open Reports" (Sprint 2 / Feature 1) = assigned-to-me reports not yet completed.
        const openReportsEl = document.getElementById('total-reports');
        if (openReportsEl) {
            openReportsEl.textContent = pending + inProgress;
        }
    } catch (error) {
        console.error('Failed to load staff dashboard data', error);
    }
}

// TASK 13.1 §4 — count for the "My Pending Releases" card.
//
// Uses the EXISTING list endpoint with per_page=1 and reads the paginator's
// `total`, so a staff member with fifty assignments still transfers one row.
// No new API, no new route, no new query parameter.
//
// The endpoint scopes the result set to this user's own assignments
// server-side, so the number shown is already "mine" without the page
// asserting anything about identity. On failure the card is silently left at
// its rendered 0 rather than showing a wrong number — a dashboard tile is not
// worth an error toast.
async function loadStaffPendingReleases() {
    const el = document.getElementById('my-pending-releases');
    if (!el) return;

    try {
        const response = await fetch(
            window.SFMS_PUBLIC_URL('/api/dispatches?status=approved&per_page=1'),
            { credentials: 'include', headers: { Accept: 'application/json' } }
        );
        const payload = await response.json();
        if (!response.ok || !payload.success) return;

        const total = Number(payload.data?.total ?? 0);
        el.textContent = Number.isFinite(total) ? total : 0;

        // TASK 13.2 §4 — the newest assignment's code, taken from the row this
        // same response already carried. index() sorts orderByDesc('created_at'),
        // so row 0 IS the latest, which is what makes the label truthful.
        const latestRow = Array.isArray(payload.data?.data) ? payload.data.data[0] : null;
        const latestCode = latestRow && latestRow.dispatch_code;
        const latestWrap = document.getElementById('my-pending-release-latest');
        const latestEl = document.getElementById('my-pending-release-code');
        if (latestWrap && latestEl && latestCode) {
            latestEl.textContent = latestCode;
            latestWrap.hidden = false;
        }
    } catch (error) {
        console.error('Failed to load pending releases count', error);
    }
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
    loadStaffPendingReleases();
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

</script>

</body>
</html>




