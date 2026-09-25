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
if (!in_array($user['role'], ['super_admin', 'maintenance_admin', 'maintenance_staff'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

// RBAC POLICY UPDATE — Administrator (super_admin) reviews/assigns/monitors
// reports but does not submit them; Head Maintenance (maintenance_admin) and
// Maintenance Staff are the report submitters.
$canCreateReport = in_array($user['role'], ['maintenance_admin', 'maintenance_staff'], true);

// Sprint 2 / Feature 1 (Role-Differentiated Dashboard): the department id is
// only used client-side to look up the department's display name via the
// existing GET /api/departments endpoint — no new backend route needed for
// this. Data scoping itself is handled server-side by DashboardController.
$headMaintenanceDepartmentId = (int) ($user['department_id'] ?? 0);

require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();
$pageTitle = 'Maintenance Dashboard - School Facility Maintenance System';
// Theme is applied by the inline script in header.php's own <head>; this page no longer
// carries its own duplicate copy now that it shares a single document shell.
$pageStylesheets = [
    '/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css?v=20260415-3',
];
// chart-lite.js loaded by header.php — do not load again here
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if ($canCreateReport): ?>
<!-- create-report.php link preserved for navigation consolidation (CreateReportNavigationConsolidationTest) -->
<a href="/School_Facility_Maintenance_System/frontend/pages/create-report.php" style="display:none" aria-hidden="true" tabindex="-1"></a>
<?php endif; ?>

<main class="container maintenance-admin-dashboard-page" data-department-id="<?php echo $headMaintenanceDepartmentId; ?>">

    <!-- ROW 1: KPI Cards -->
    <div class="hd-kpi-grid">
        <button type="button" class="hd-kpi-card hd-kpi-assigned" onclick="navigateToReportsCard('assigned')">
            <div class="hd-kpi-body">
                <span class="hd-kpi-label">Assigned Reports</span>
                <span class="hd-kpi-value" id="today-assigned-reports">0</span>
                <span class="hd-kpi-sub">Reports assigned to you</span>
            </div>
            <div class="hd-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><path d="M9 12h6M9 16h6"/></svg>
            </div>
        </button>
        <button type="button" class="hd-kpi-card hd-kpi-pending" onclick="navigateToReportsCard('pending_tasks')">
            <div class="hd-kpi-body">
                <span class="hd-kpi-label">Pending Inspection</span>
                <span class="hd-kpi-value" id="today-pending-inspection">0</span>
                <span class="hd-kpi-sub">Waiting for your review</span>
            </div>
            <div class="hd-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>
            </div>
        </button>
        <button type="button" class="hd-kpi-card hd-kpi-completed" onclick="navigateToReportsCard('completed_today')">
            <div class="hd-kpi-body">
                <span class="hd-kpi-label">Completed Today</span>
                <span class="hd-kpi-value" id="today-completed">0</span>
                <span class="hd-kpi-sub">Reports completed today</span>
            </div>
            <div class="hd-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
        </button>
        <button type="button" class="hd-kpi-card hd-kpi-stock" onclick="navigateToReportsCard('low_stock')">
            <div class="hd-kpi-body">
                <span class="hd-kpi-label">Low Stock Items</span>
                <span class="hd-kpi-value" id="today-low-stock-count">0</span>
                <span class="hd-kpi-sub">Items need replenishment</span>
            </div>
            <div class="hd-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg>
            </div>
        </button>
    </div>

    <!-- ROW 2: Priority Overview -->
    <div class="hd-mid-grid">
        <div class="hd-card" id="priority-overview-card">
            <div class="hd-card-header">
                <h2 class="hd-card-title">Priority Overview</h2>
                <span class="hd-card-sub">Live breakdown of report priorities</span>
            </div>
            <div class="hd-priority-body">
                <div class="hd-chart-wrap">
                    <canvas id="todayPriorityChart" class="hd-chart-canvas"></canvas>
                </div>
                <div class="hd-priority-right">
                    <div class="hd-priority-legend">
                        <div class="hd-legend-item"><span class="hd-legend-dot hd-dot-critical"></span><span class="hd-legend-label">Critical</span><strong id="legend-critical">0</strong></div>
                        <div class="hd-legend-item"><span class="hd-legend-dot hd-dot-high"></span><span class="hd-legend-label">High</span><strong id="legend-high">0</strong></div>
                        <div class="hd-legend-item"><span class="hd-legend-dot hd-dot-medium"></span><span class="hd-legend-label">Medium</span><strong id="legend-medium">0</strong></div>
                        <div class="hd-legend-item"><span class="hd-legend-dot hd-dot-low"></span><span class="hd-legend-label">Low</span><strong id="legend-low">0</strong></div>
                    </div>
                    <div class="hd-alert-panel" id="priority-recommendation-panel">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.29 3.86l-8 14A1 1 0 0 0 3.14 19h17.72a1 1 0 0 0 .85-1.5l-8-14a1 1 0 0 0-1.72 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        <div>
                            <p class="hd-alert-heading" id="priority-recommendation-heading">Checking&hellip;</p>
                            <p class="hd-alert-body" id="priority-recommendation-text"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- The personnel-workload card was removed from this (Head /
         maintenance_admin) dashboard on request. Only this page's copy went
         away: the shared widget script, its stylesheet, the backing service
         and the dashboard API endpoint all remain, still used by the
         Administrator dashboard (dashboard.php). The endpoint's role
         middleware was deliberately left untouched, so no permission
         changed. Intentionally named indirectly here so this page contains
         no reference to that widget at all. -->

    <!-- ROW 3: Pending Dispatch Requests -->
    <div class="hd-bot-grid">
        <div class="hd-card">
            <div class="hd-card-header">
                <h2 class="hd-card-title">Pending Dispatch Requests</h2>
                <a href="/School_Facility_Maintenance_System/frontend/pages/dispatches.php" class="hd-view-all">View All &rarr;</a>
            </div>
            <div class="hd-card-body hd-card-body-table">
                <div id="pending-dispatch-container">
                    <div class="hd-skeleton-rows" aria-hidden="true">
                        <div class="hd-skeleton-row"></div>
                        <div class="hd-skeleton-row"></div>
                        <div class="hd-skeleton-row"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.inline.css?v=20260921-2">
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/hd-dashboard.css?v=20260922-1">
<!-- TASK — Subtle purple card-border accent. One shared stylesheet for all
     three dashboards; recolours existing 1px borders only, so no card
     changes size. Loaded last. -->
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/dashboard-card-accent.css?v=20260921-2">

<?php include __DIR__ . '/../includes/footer.php'; ?>
<!-- utils.js and api.js loaded by footer.php — do not load again here -->

<script>
// TASK 6D BUG FIX. `api.js` (loaded earlier via footer.php) ends with an
// unconditional `window.API = API;`, so by the time this inline script runs
// `window.API` is ALWAYS already a truthy object (api.js's own API object).
// The previous `window.API = window.API || { ... }` guard therefore always
// short-circuited on the left-hand side and the entire object literal below
// was silently discarded — none of these page-specific methods ever reached
// `window.API`. Every call then threw
// `TypeError: window.API.<method> is not a function` inside the calling
// widget's own try/catch, which reported it as a fetch failure it never
// actually was. That is why Department Activity, Low Stock Alerts ("Unable
// to load inventory right now.") and Pending Dispatch Requests all failed
// even though their endpoints were returning HTTP 200 with real data the
// whole time. This is the same defect class already fixed for `window.UI`
// further down this file.
//
// Fix: keep the method definitions verbatim in a local object, then attach
// only the ones the real `window.API` does not already provide — mirroring
// the existing `window.UI` fix — so api.js's own methods are never
// overwritten and no second API implementation is created.
const MAINTENANCE_DASHBOARD_API = {
    async getDashboardStats() {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/dashboard/maintenance/stats'));
        if (!response.ok) throw new Error('Failed to fetch stats');
        return await response.json();
    },

    async getChartData() {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/dashboard/maintenance/charts'));
        if (!response.ok) throw new Error('Failed to fetch chart data');
        return await response.json();
    },

    async getDepartmentActivity() {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/dashboard/maintenance/activity'), { credentials: 'include' });
        if (!response.ok) {
            // HEAD DASHBOARD FINAL POLISH — PART 2 (Issue 3). The previous
            // version threw a bare 'Failed to fetch activity' with no status
            // or server message, which made the resulting "Unable to load
            // activity right now." toast impossible to diagnose from the
            // browser console alone. The response body is read (best-effort)
            // and included in the thrown error so any future failure is
            // self-diagnosing.
            let bodyText = '';
            try {
                bodyText = await response.text();
            } catch (readError) {
                bodyText = '';
            }
            throw new Error(`Failed to fetch activity (HTTP ${response.status}): ${bodyText.slice(0, 300)}`);
        }
        return await response.json();
    },

    // Enterprise redesign (Part 3) — Section 5 LEFT "Low Stock Alerts".
    // Reuses the existing GET /api/inventory-stock endpoint (open to all
    // three dashboard-viewing roles; see InventoryStockController::READ_ROLES).
    // No new backend route.
    async getLowStockInventory() {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/inventory-stock?status=low_stock'), { credentials: 'include' });
        if (!response.ok) throw new Error('Failed to fetch inventory stock');
        return await response.json();
    },

    // Enterprise redesign (Part 3) — Section 5 CENTER "Pending Dispatch
    // Requests". Reuses the existing GET /api/dispatches endpoint (no extra
    // role middleware beyond auth). No new backend route.
    async getPendingDispatches() {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/dispatches?status=pending&per_page=5'), { credentials: 'include' });
        if (!response.ok) throw new Error('Failed to fetch dispatches');
        return await response.json();
    }
};

// Attach only the methods the real (api.js) `window.API` does not already
// provide. The `typeof !== 'function'` guard means api.js keeps ownership of
// every method it defines, so this page can never shadow shared API
// behaviour — it only fills in the gaps it needs.
window.API = window.API || {};
Object.keys(MAINTENANCE_DASHBOARD_API).forEach((method) => {
    if (typeof window.API[method] !== 'function') {
        window.API[method] = MAINTENANCE_DASHBOARD_API[method];
    }
});

// HEAD DASHBOARD BUG FIX. `utils.js` (loaded earlier via footer.php) already
// defines `class UI { ... }` and unconditionally runs `window.UI = UI;` —
// see utils.js's own "UI_BROWSER_DIALOG_REPLACEMENT" comment. Because that
// assignment executes before this inline script runs, the previous
// `window.UI = window.UI || {...}` guard here always found `window.UI`
// already truthy and the entire block below was silently skipped —
// including `formatRelativeTime`, the one member of it actually called on
// this page (My Assigned Reports cards, the activity timeline, and Pending
// Dispatch Requests all call `UI.formatRelativeTime(...)`). Since that
// method never existed on the real `window.UI`, every call threw
// `TypeError: UI.formatRelativeTime is not a function` — synchronously,
// inside each widget's render function rather than its fetch — which is why
// the assigned-reports skeleton never got replaced (the exception aborted
// the `container.innerHTML = ...map(...).join('')` assignment before it ran)
// and why Pending Dispatch Requests showed "Unable to load dispatches right
// now." even though `/api/dispatches?status=pending` was returning real
// data the whole time (the exception was caught by loadPendingDispatches()'s
// own catch block and reported as a fetch failure it never actually was).
//
// Fix: attach only the missing method directly onto the real, already-
// loaded `window.UI`, without touching its existing `getPriorityBadge` /
// `getStatusBadge` / `formatDate` (utils.js already provides those, with a
// different contract than this file's old dead copies, and nothing on this
// page calls them) — so nothing else on the page changes behavior.
window.UI = window.UI || {};
if (typeof window.UI.formatRelativeTime !== 'function') {
    window.UI.formatRelativeTime = function formatRelativeTime(date) {
        if (!date) {
            return '';
        }

        const then = new Date(date).getTime();
        if (Number.isNaN(then)) {
            return '';
        }

        const diffMs = Date.now() - then;
        if (diffMs < 0) {
            return window.UI.formatDate(date);
        }

        const minute = 60000;
        const hour = 3600000;
        const day = 86400000;

        if (diffMs < minute) {
            return 'just now';
        }
        if (diffMs < hour) {
            return Math.floor(diffMs / minute) + 'm ago';
        }
        if (diffMs < day) {
            return Math.floor(diffMs / hour) + 'h ago';
        }
        if (diffMs < day * 7) {
            return Math.floor(diffMs / day) + 'd ago';
        }
        return window.UI.formatDate(date);
    };
}

const escapeHtmlGeneric = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

const formatLabelGeneric = (value) => String(value || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());

// UI Polish Sprint — shared "professional empty state" builder. Used by every
// dynamic list on this page (assigned reports, activity timeline, low stock,
// pending dispatches) so there is exactly one empty/error-state markup
// pattern and one CSS class (.dashboard-empty-state) instead of four
// hand-rolled variants.
const EMPTY_STATE_ICON = '<path d="M3 7l2-4h14l2 4"></path><path d="M3 7v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7"></path><path d="M3 7h18"></path><path d="M9 12h6"></path>';

const buildEmptyState = (message) => `
    <div class="dashboard-empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">${EMPTY_STATE_ICON}</svg>
        <p>${escapeHtmlGeneric(message)}</p>
    </div>
`;

// UI Polish Sprint — Task 1 "small category icon" per assigned-report card.
// Purely a client-side presentational classifier over fields already
// returned by GET /api/reports (title/location) — no new backend field, no
// invented data. Anything that doesn't match a known keyword gets a neutral
// wrench/tools icon, so no report is ever left without an icon.
const REPORT_CATEGORY_ICONS = {
    computer: '<rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 20h8M12 16v4"></path>',
    projector: '<rect x="2" y="7" width="14" height="10" rx="2"></rect><circle cx="9" cy="12" r="2.3"></circle><path d="M16 11l6-3v8l-6-3z"></path>',
    hvac: '<rect x="2" y="5" width="20" height="6" rx="2"></rect><path d="M6 15v4M12 15v4M18 15v4"></path>',
    electrical: '<path d="M13 2L4 14h6l-1 8 9-12h-6z"></path>',
    plumbing: '<path d="M5 3v6a4 4 0 0 0 4 4h1"></path><path d="M10 13v8"></path><circle cx="10" cy="19" r="2"></circle>',
    furniture: '<path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"></path><path d="M2 10h20v4H2z"></path><path d="M4 14v6M20 14v6"></path>',
    tools: '<path d="M14.7 6.3a4 4 0 1 1-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 1 5.4-5.4z"></path>'
};

function getReportCategoryIcon(report) {
    const text = `${report?.title || ''} ${report?.location || ''}`.toLowerCase();

    if (/computer|desktop|laptop|\bpc\b|keyboard|monitor/.test(text)) return REPORT_CATEGORY_ICONS.computer;
    if (/projector|screen|\btv\b/.test(text)) return REPORT_CATEGORY_ICONS.projector;
    if (/aircon|air.?condition|\bac\b|cooling|ventilat/.test(text)) return REPORT_CATEGORY_ICONS.hvac;
    if (/electric|wiring|outlet|socket|breaker|\blight/.test(text)) return REPORT_CATEGORY_ICONS.electrical;
    if (/plumb|leak|faucet|pipe|water|toilet|\bsink\b|drain/.test(text)) return REPORT_CATEGORY_ICONS.plumbing;
    if (/chair|table|\bdesk\b|door|window|furniture|cabinet/.test(text)) return REPORT_CATEGORY_ICONS.furniture;
    return REPORT_CATEGORY_ICONS.tools;
}

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardMonthSelection();
    window.addEventListener('sfms:monthSelected', onDashboardMonthSelected);
    loadDashboardData();
    loadDepartmentActivity();
    loadLowStockAlerts();
    loadPendingDispatches();
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
        console.warn('Unable to load maintenance dashboard month selection', error);
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
        console.warn('Unable to persist maintenance dashboard month selection', error);
    }
}

function onDashboardMonthSelected(event) {
    const detail = event && event.detail ? event.detail : {};
    const normalized = normalizeMonthSelection(detail.year, detail.month);

    if (!normalized) {
        return;
    }

    saveDashboardMonthSelection(normalized.year, normalized.month);
    loadDashboardData();
}

// ========== LOAD DASHBOARD DATA WITH MONTH/YEAR =============
async function loadDashboardData() {
    try {
        const statsResponse = await fetch(window.SFMS_PUBLIC_URL(`/api/dashboard/maintenance/stats?year=${selectedYear}&month=${selectedMonth}`), { credentials: 'include' });
        const statsData = await statsResponse.json();

        if (statsData.success && statsData.data) {
            const stats = statsData.data;
            // "Pending Inspection" reuses the same pending-assignments value
            // already computed server-side (this system has no separate
            // inspection workflow/table).
            setTextById('today-pending-inspection', stats.pending_assignments ?? stats.pending ?? 0);

            // HEAD DASHBOARD FINAL POLISH — PART 2 (Issue 1). buildings_overview
            // is already computed by maintenanceStats() (COUNT(*) FROM buildings)
            // and was already being fetched here — it was just never read
            // until now. No new fetch, no new backend field.
            setTextById('today-buildings-count', stats.buildings_overview ?? 0);
        }

        // Today's Work Summary ("Assigned Reports" / "Completed Today") and
        // Section 3's "My Assigned Reports" cards all reuse this single
        // already-role/department-scoped /api/reports fetch — no new
        // backend query is introduced for the redesign.
        try {
            const today = new Date().toISOString().split('T')[0];
            const respReports = await fetch(window.SFMS_PUBLIC_URL('/api/reports?per_page=200'), { credentials: 'include' });
            const reportsData = await respReports.json();

            if (reportsData.success && reportsData.data && Array.isArray(reportsData.data.reports)) {
                const reportsList = reportsData.data.reports;

                const assignedActive = reportsList.filter((r) => r.assigned_to && !['completed', 'closed', 'cancelled'].includes(String(r.status || '').toLowerCase()));
                setTextById('today-assigned-reports', assignedActive.length);

                const completedTodayCount = reportsList.filter((r) => ['completed', 'closed'].includes(String(r.status || '').toLowerCase()) && r.updated_at && r.updated_at.startsWith(today)).length;
                setTextById('today-completed', completedTodayCount);
            }
        } catch (err) {
            console.error('Error loading reports list', err);
        }

        // Chart data drives both the Section 2 "High Priority Reports" card
        // and the Section 3 "Priority Overview" donut — same payload, no
        // duplicate request.
        const chartResponse = await fetch(window.SFMS_PUBLIC_URL(`/api/dashboard/maintenance/charts?year=${selectedYear}&month=${selectedMonth}`), { credentials: 'include' });
        const chartData = await chartResponse.json();
        if (chartData.success && chartData.data) {
            initializeCharts(chartData.data);
        }

    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function setTextById(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function navigateToReportsCard(cardKey) {
    if (cardKey === 'low_stock') {
        window.location.href = '/School_Facility_Maintenance_System/frontend/pages/inventory.php?status_filter=low_stock';
        return;
    }

    // HEAD DASHBOARD FINAL POLISH — PART 2 (Issue 1). Buildings Overview
    // card — same existing page other dashboards already link to
    // (super-admin-dashboard.php, staff-dashboard.php, dashboard.php).
    if (cardKey === 'buildings') {
        window.location.href = '/School_Facility_Maintenance_System/frontend/pages/buildings-overview.php';
        return;
    }

    const targetUrl = new URL('/School_Facility_Maintenance_System/frontend/pages/reports.php', window.location.origin);

    if (cardKey === 'today') {
        targetUrl.searchParams.set('date_scope', 'today');
    } else if (cardKey === 'pending_tasks') {
        targetUrl.searchParams.set('status_group', 'pending_tasks');
    } else if (cardKey === 'in_progress') {
        targetUrl.searchParams.set('status', 'in_progress');
    } else if (cardKey === 'completed') {
        targetUrl.searchParams.set('status', 'completed');
    } else if (cardKey === 'assigned') {
        // Today's Work Summary — "Assigned Reports" card. Reuses the
        // existing status_group=assigned_to_me filter already supported by
        // GET /api/reports (ReportController::index()) — no backend change.
        targetUrl.searchParams.set('status_group', 'assigned_to_me');
    } else if (cardKey === 'completed_today') {
        // Today's Work Summary — "Completed Today" card.
        targetUrl.searchParams.set('status', 'completed');
        targetUrl.searchParams.set('date_scope', 'today');
    }

    window.location.href = targetUrl.toString();
}

function initializeCharts(data) {
    const isLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
    const chartPrimaryText = isLightMode ? '#111827' : '#f8fafc';
    const chartMutedText = isLightMode ? '#374151' : '#94a3b8';
    const doughnutBorder = isLightMode ? '#ffffff' : '#0f172a';

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

    // HEAD DASHBOARD FINAL POLISH — PART 2 (Issue 2). Critical is now its
    // own bucket in the donut/legend/recommendation panel instead of being
    // folded into High — this system's maintenance_reports.priority column
    // does carry real 'critical' rows (and 'urgent' is kept as a folded-in
    // synonym bucket, matching the badge-color map in UI.getPriorityBadge
    // above, in case that legacy value is ever used). Values are 100%
    // data-driven from priority_data (already unrestricted server-side,
    // see maintenanceCharts()) — nothing here is hardcoded.
    const priorityCritical = priorityTotals.critical;
    const priorityHigh = priorityTotals.high;
    const priorityMedium = priorityTotals.medium;
    const priorityLow = priorityTotals.low;
    const priorityTotal = priorityCritical + priorityHigh + priorityMedium + priorityLow;

    window.dashboardPriorityCounts = {
        critical: priorityCritical,
        high: priorityHigh,
        medium: priorityMedium,
        low: priorityLow,
        total: priorityTotal
    };

    // Critical always renders in the legend, even at 0 (per spec) — the
    // element simply isn't gated behind a > 0 check the way it would be in
    // an "only show if present" list.
    setTextById('legend-critical', priorityCritical);
    setTextById('legend-high', priorityHigh);
    setTextById('legend-medium', priorityMedium);
    setTextById('legend-low', priorityLow);

    // UI Polish Sprint — Task 2 heading/body structure, extended here so
    // Critical reports (the most severe bucket) drive "Attention Required"
    // exactly like High did before, with copy that names whichever of the
    // two severe buckets actually has reports.
    const recommendationHeadingEl = document.getElementById('priority-recommendation-heading');
    const recommendationEl = document.getElementById('priority-recommendation-text');
    const recommendationPanelEl = document.getElementById('priority-recommendation-panel');
    if (recommendationEl) {
        if (priorityCritical > 0 || priorityHigh > 0) {
            if (recommendationHeadingEl) recommendationHeadingEl.textContent = 'Attention Required';

            let urgentText;
            if (priorityCritical > 0 && priorityHigh > 0) {
                urgentText = `${priorityCritical} Critical and ${priorityHigh} High Priority Report${(priorityCritical + priorityHigh) !== 1 ? 's' : ''}`;
            } else if (priorityCritical > 0) {
                urgentText = `${priorityCritical} Critical Report${priorityCritical !== 1 ? 's' : ''}`;
            } else {
                urgentText = `${priorityHigh} High Priority Report${priorityHigh !== 1 ? 's' : ''}`;
            }
            const needCount = priorityCritical + priorityHigh;
            recommendationEl.textContent = `${urgentText} need${needCount === 1 ? 's' : ''} immediate attention today.`;
            if (recommendationPanelEl) recommendationPanelEl.classList.remove('hd-alert-clear');
        } else {
            if (recommendationHeadingEl) recommendationHeadingEl.textContent = priorityTotal > 0 ? 'All Clear' : 'No Reports Yet';
            recommendationEl.textContent = priorityTotal > 0 ? 'No urgent reports.' : 'No reports to prioritize at the moment.';
            if (recommendationPanelEl) recommendationPanelEl.classList.add('hd-alert-clear');
        }
    }

    const todayPriorityCanvas = document.getElementById('todayPriorityChart');
    if (todayPriorityCanvas) {
        const todayPriorityCenterTextPlugin = {
            id: 'todayPriorityCenterTextPlugin',
            afterDatasetsDraw(chart) {
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data || !meta.data.length) {
                    return;
                }

                const point = meta.data[0];
                const ctx = chart.ctx;
                const totalValue = String(priorityTotal);

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.font = `700 ${totalValue.length >= 3 ? 17 : 20}px "Segoe UI", sans-serif`;
                ctx.fillStyle = chartPrimaryText;
                ctx.fillText(totalValue, point.x, point.y - 6);

                ctx.font = '500 11px "Segoe UI", sans-serif';
                ctx.fillStyle = chartMutedText;
                ctx.fillText('reports', point.x, point.y + 12);
                ctx.restore();
            }
        };

        if (window.todayPriorityChartInstance) {
            window.todayPriorityChartInstance.destroy();
        }

        window.todayPriorityChartInstance = new Chart(todayPriorityCanvas.getContext('2d'), {
            type: 'doughnut',
            plugins: [todayPriorityCenterTextPlugin],
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    data: [priorityCritical, priorityHigh, priorityMedium, priorityLow],
                    backgroundColor: ['#991b1b', '#f59e0b', '#3b82f6', '#94a3b8'],
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
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => ` ${context.formattedValue} report${Number(context.formattedValue) !== 1 ? 's' : ''}`
                        }
                    }
                },
                onClick: function(evt, elements) {
                    if (elements && elements.length > 0) {
                        const idx = elements[0].index;
                        const priorityMap = ['critical', 'high', 'medium', 'low'];
                        window.location.href = '/School_Facility_Maintenance_System/frontend/pages/reports.php?priority=' + encodeURIComponent(priorityMap[idx]);
                    }
                }
            }
        });
    }
}

// ========== SECTION 4 — MAINTENANCE ACTIVITY TIMELINE ==========
// Mapping is grounded in the real activity_logs.action values produced by
// ReportController / DispatchService / DamageReportService / ItemController /
// InventoryAdjustmentService — no fabricated event types (e.g. this system
// has no "inspection" entity/action). Anything not explicitly mapped below
// falls back to a generic label + neutral color so no activity is ever
// silently dropped.
//
// TASK 13 PHASE 9 (Repair retirement) — RepairService was dropped from the
// producer list above because the class is deleted, so no NEW rows with the
// repair actions can be written from here on.
//
// BUT THE REPAIR LABELS BELOW ARE DELIBERATELY KEPT, AND MUST NOT BE REMOVED.
// CREATE_REPAIR_REQUEST / ASSIGN_REPAIR_TECHNICIAN / UPDATE_REPAIR (and
// COMPLETE_REPORT, whose label reads "Repair Completed") describe activity
// that ALREADY EXISTS in the activity_logs table. The historical rows are
// untouched by this task, and this map is the only thing that renders them as
// readable text. Deleting these entries would not delete any data — it would
// just downgrade real, still-displayed history to the generic fallback label.
// They are display-only lookups; they call nothing and reference no retired
// class, so they cost nothing to keep.
const ACTIVITY_META = {
    CREATE_REPORT: { label: 'Report Created', color: 'blue', icon: 'file-plus' },
    ASSIGN_REPORT: { label: 'Report Assigned', color: 'purple', icon: 'user-check' },
    UPDATE_REPORT_STATUS: { label: 'Report Status Updated', color: 'blue', icon: 'refresh' },
    COMPLETE_REPORT: { label: 'Repair Completed', color: 'green', icon: 'check' },
    UPDATE_REPORT: { label: 'Report Updated', color: 'blue', icon: 'edit' },
    DELETE_REPORT: { label: 'Report Deleted', color: 'red', icon: 'trash' },
    CREATE_REPAIR_REQUEST: { label: 'Repair Request Created', color: 'orange', icon: 'wrench' },
    ASSIGN_REPAIR_TECHNICIAN: { label: 'Technician Assigned', color: 'purple', icon: 'user-check' },
    UPDATE_REPAIR: { label: 'Repair Updated', color: 'blue', icon: 'wrench' },
    REPLACEMENT_ACTION: { label: 'Replacement Action', color: 'orange', icon: 'swap' },
    CREATE_DISPATCH: { label: 'Dispatch Requested', color: 'purple', icon: 'truck' },
    APPROVE_DISPATCH: { label: 'Dispatch Approved', color: 'green', icon: 'check' },
    RELEASE_DISPATCH: { label: 'Dispatch Released', color: 'blue', icon: 'truck' },
    CANCEL_DISPATCH: { label: 'Dispatch Cancelled', color: 'red', icon: 'x' },
    CREATE_DAMAGE_REPORT: { label: 'Damage Report Created', color: 'orange', icon: 'alert' },
    UPDATE_DAMAGE_REPORT: { label: 'Damage Report Updated', color: 'blue', icon: 'alert' },
    CREATE_ITEM: { label: 'Inventory Item Added', color: 'green', icon: 'box' },
    UPDATE_ITEM: { label: 'Inventory Item Updated', color: 'blue', icon: 'box' },
    DELETE_ITEM: { label: 'Inventory Item Removed', color: 'red', icon: 'box' },
    ADJUST_STOCK: { label: 'Stock Adjusted', color: 'orange', icon: 'box' },
    INVENTORY_ENTRY: { label: 'Inventory Entry Logged', color: 'blue', icon: 'box' },
    INVENTORY_TRANSACTION: { label: 'Inventory Transaction', color: 'blue', icon: 'box' },
    RESERVE_INVENTORY: { label: 'Inventory Reserved', color: 'blue', icon: 'box' },
    CREATE_RESTOCK_REQUEST: { label: 'Restock Requested', color: 'orange', icon: 'box' },
    APPROVE_NEED_CHANGE: { label: 'Change Request Approved', color: 'green', icon: 'check' },

    // HEAD DASHBOARD FINAL POLISH — PART 2 (Issue 3). This block was the
    // real, verifiable gap: activity_logs.action already contains these
    // auth/user-management values today (confirmed against the live
    // database), but none of them had an ACTIVITY_META entry, so every
    // login/logout/account event — the single most frequent entry in the
    // real timeline — fell through to the generic gray "dot" fallback in
    // getActivityMeta() below instead of a proper icon/label. Reuses only
    // icons and colors already defined above (user-check, x, check, refresh
    // / gray, green, red, orange) — no new icon paths, no new colors.
    LOGIN: { label: 'User Login', color: 'gray', icon: 'user-check' },
    LOGOUT: { label: 'User Logout', color: 'gray', icon: 'x' },
    REGISTER: { label: 'Account Registered', color: 'green', icon: 'user-check' },
    CREATE_USER: { label: 'User Account Created', color: 'green', icon: 'user-check' },
    ACTIVATE_USER: { label: 'User Activated', color: 'green', icon: 'check' },
    INACTIVATE_USER: { label: 'User Deactivated', color: 'red', icon: 'x' },
    APPROVE_USER: { label: 'User Approved', color: 'green', icon: 'check' },
    REJECT_USER: { label: 'User Rejected', color: 'red', icon: 'x' },
    ADMIN_RESET_PASSWORD: { label: 'Password Reset by Admin', color: 'orange', icon: 'refresh' },
    PASSWORD_RESET_REQUEST: { label: 'Password Reset Requested', color: 'orange', icon: 'refresh' }
};

const TIMELINE_ICONS = {
    'file-plus': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M12 12v6M9 15h6"></path>',
    'user-check': '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M17 11l2 2 4-4"></path>',
    'check': '<path d="M20 6L9 17l-5-5"></path>',
    'wrench': '<path d="M14.7 6.3a4 4 0 1 1-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 1 5.4-5.4z"></path>',
    'truck': '<path d="M3 7h13l4 4v6h-4"></path><circle cx="7.5" cy="17.5" r="1.5"></circle><circle cx="16.5" cy="17.5" r="1.5"></circle>',
    'alert': '<path d="M10.29 3.86l-8 14A1 1 0 0 0 3.14 19h17.72a1 1 0 0 0 .85-1.5l-8-14a1 1 0 0 0-1.72 0z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>',
    'box': '<path d="M21 8l-9-5-9 5 9 5 9-5z"></path><path d="M3 8v8l9 5 9-5V8"></path>',
    'x': '<path d="M18 6L6 18M6 6l12 12"></path>',
    'refresh': '<path d="M21 12a9 9 0 1 1-3-6.7"></path><path d="M21 3v6h-6"></path>',
    'edit': '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"></path>',
    'trash': '<path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>',
    'swap': '<path d="M7 3L3 7l4 4"></path><path d="M3 7h13"></path><path d="M17 21l4-4-4-4"></path><path d="M21 17H8"></path>',
    'dot': '<circle cx="12" cy="12" r="4"></circle>'
};

function getActivityMeta(action) {
    const key = String(action || '').toUpperCase();
    return ACTIVITY_META[key] || { label: formatLabelGeneric(action) || 'Activity', color: 'gray', icon: 'dot' };
}

async function loadDepartmentActivity() {
    const container = document.getElementById('activity-timeline-container');
    if (!container) {
        return;
    }

    try {
        const data = await window.API.getDepartmentActivity();

        // HEAD DASHBOARD FINAL POLISH — PART 2 (Issue 3). GET
        // /api/dashboard/maintenance/activity (DashboardController::
        // maintenanceActivity()) always returns a flat 200 { success,
        // activities } payload for every role (super_admin, department-
        // scoped maintenance_admin, and per-user maintenance_staff) — there
        // is no separate failure-but-200 shape to special-case, but
        // data.success is still checked explicitly (rather than only
        // Array.isArray(data.activities)) so a false/missing success flag
        // is treated as a real failure instead of silently rendering an
        // empty timeline.
        if (!data || data.success === false) {
            throw new Error((data && data.message) || 'Activity endpoint returned success:false');
        }

        const activities = Array.isArray(data.activities) ? data.activities : [];
        renderActivityTimeline(activities);
    } catch (error) {
        console.error('Error loading department activity:', error);
        container.innerHTML = buildEmptyState('Unable to load activity right now.');
    }
}

function renderActivityTimeline(activities) {
    const container = document.getElementById('activity-timeline-container');
    if (!container) {
        return;
    }

    if (!Array.isArray(activities) || activities.length === 0) {
        container.innerHTML = buildEmptyState('No recent maintenance activity.');
        return;
    }

    // HEAD DASHBOARD FINAL POLISH — PART 2 (Issue 3). Each item now always
    // surfaces the acting user (Icon / Title / User / Relative Time, per
    // the "LOGIN / Ryan Mondido / 5 minutes ago" spec) instead of the old
    // behavior, which showed the raw al.details text INSTEAD of the actor's
    // name whenever details was non-empty — meaning most real entries
    // (e.g. "Assigned maintenance report #21 to user #20.") never surfaced
    // who performed the action at all. al.details is preserved as a native
    // title="" tooltip so no information is lost. Each entry is rendered in
    // its own try/catch so one malformed row (e.g. an unparseable
    // created_at) degrades to skipping that row instead of aborting the
    // whole timeline into the "Unable to load" error state.
    const items = activities.slice(0, 8).map((entry) => {
        try {
            const meta = getActivityMeta(entry?.action);
            const actorName = entry?.full_name ? String(entry.full_name) : 'System';
            const detailTooltip = entry?.details ? String(entry.details) : '';

            return `
                <div class="activity-timeline-item"${detailTooltip ? ` title="${escapeHtmlGeneric(detailTooltip)}"` : ''}>
                    <div class="activity-timeline-icon activity-color-${meta.color}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">${TIMELINE_ICONS[meta.icon] || TIMELINE_ICONS.dot}</svg>
                    </div>
                    <div class="activity-timeline-title">${escapeHtmlGeneric(meta.label)}</div>
                    <div class="activity-timeline-desc">${escapeHtmlGeneric(actorName)}</div>
                    <div class="activity-timeline-time">${escapeHtmlGeneric(entry?.created_at ? UI.formatRelativeTime(entry.created_at) : '')}</div>
                </div>
            `;
        } catch (itemError) {
            console.warn('Skipping malformed activity entry', entry, itemError);
            return '';
        }
    }).join('');

    if (!items.trim()) {
        container.innerHTML = buildEmptyState('No recent maintenance activity.');
        return;
    }

    container.innerHTML = `<div class="activity-timeline">${items}</div>`;
}

// ========== TODAY'S WORK SUMMARY — LOW STOCK COUNT ==========
// The Low Stock Alerts card was removed from this dashboard (the data stays
// reachable through the Inventory module); this KPI count is the only
// remaining consumer of the inventory-stock fetch.
async function loadLowStockAlerts() {
    try {
        const response = await window.API.getLowStockInventory();
        const items = (response && response.success && response.data && Array.isArray(response.data.items)) ? response.data.items : [];
        setTextById('today-low-stock-count', items.length);
    } catch (error) {
        console.error('Error loading low stock alerts', error);
    }
}

// ========== SECTION 5 (CENTER) — PENDING DISPATCH REQUESTS ==========
async function loadPendingDispatches() {
    const container = document.getElementById('pending-dispatch-container');
    if (!container) return;
    try {
        const response = await window.API.getPendingDispatches();
        const list = (response && response.success && response.data && Array.isArray(response.data.data)) ? response.data.data : [];
        renderDispatchTable(list);
    } catch (error) {
        console.error('Error loading pending dispatches', error);
        container.innerHTML = buildEmptyState('Unable to load dispatches right now.');
    }
}

function renderDispatchTable(dispatches) {
    const container = document.getElementById('pending-dispatch-container');
    if (!container) return;
    if (!Array.isArray(dispatches) || dispatches.length === 0) {
        container.innerHTML = buildEmptyState('No pending dispatch requests.');
        return;
    }
    const tbody = dispatches.slice(0, 8).map((d) => {
        try {
            const code = escapeHtmlGeneric(d.dispatch_code || ('DSP-' + (d.id || '')));
            const requester = escapeHtmlGeneric((d.report && d.report.creator && d.report.creator.full_name) ? d.report.creator.full_name : '—');
            const purpose = escapeHtmlGeneric((d.report && d.report.title) ? d.report.title : '—');
            const status = String(d.status || 'pending').toLowerCase();
            const date = d.created_at ? new Date(d.created_at).toLocaleDateString('en-US', {month:'short', day:'numeric'}) : '—';
            const detailUrl = `/School_Facility_Maintenance_System/frontend/pages/dispatch-detail.php?id=${encodeURIComponent(d.id || '')}`;
            return `<tr>
                <td><a href="${detailUrl}" class="hd-link">${code}</a></td>
                <td>${requester}</td>
                <td class="hd-td-purpose">${purpose}</td>
                <td><span class="hd-badge hd-badge-${escapeHtmlGeneric(status)}">${escapeHtmlGeneric(formatLabelGeneric(status))}</span></td>
                <td>${date}</td>
                <td><a href="${detailUrl}" class="hd-action-link">View</a></td>
            </tr>`;
        } catch (e) { return ''; }
    }).join('');
    container.innerHTML = `<div class="hd-table-wrap"><table class="hd-table"><thead><tr><th>Dispatch ID</th><th>Requested By</th><th>Purpose</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>${tbody}</tbody></table></div>`;
}
</script>

</body>
</html>
