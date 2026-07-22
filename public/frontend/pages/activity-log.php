<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
$currentRole = strtolower(trim((string)($user['role'] ?? '')));
if (!in_array($currentRole, ['super_admin', 'maintenance_admin'], true)) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

$pageTitle = 'Activity Logs - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;">
    <div class="card" style="margin-bottom:14px;">
        <div class="card-header">
            <div>
                <h2>Activity Logs</h2>
                <p class="text-muted mb-0">Audit user actions across authentication, inventory, dispatch, damage, repair, and replacement workflows.</p>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:10px;">
                <input type="search" id="activity-log-search" class="form-control" placeholder="Search description, action, module, or user...">
                <select id="activity-log-action" class="form-control">
                    <option value="">All Actions</option>
                </select>
                <select id="activity-log-module" class="form-control">
                    <option value="">All Modules</option>
                </select>
                <input type="date" id="activity-log-from" class="form-control">
                <input type="date" id="activity-log-to" class="form-control">
                <button type="button" class="btn btn-secondary" id="activity-log-clear">Clear</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h3>Audit Trail</h3>
                <p class="text-muted mb-0" id="activity-log-summary">Loading activity logs...</p>
            </div>
            <div class="text-muted" id="activity-log-total">Total: 0</div>
        </div>
        <div class="card-body">
            <div id="activity-log-table-container">
                <div class="ui-empty-state"><strong>Loading activity logs...</strong></div>
            </div>

            <div class="d-flex justify-between align-center" style="margin-top:12px;">
                <button type="button" class="btn btn-secondary" id="activity-log-prev">Previous</button>
                <div class="text-muted" id="activity-log-page-label">Page 1</div>
                <button type="button" class="btn btn-secondary" id="activity-log-next">Next</button>
            </div>
        </div>
    </div>
    </div>
</main>

<script>
const ACTIVITY_LOGS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/activity-logs') : '/api/activity-logs';
const ACTIVITY_LOGS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/activity-logs') : '/activity-logs';
let activityLogsState = { page: 1, totalPages: 1 };

function activityLogNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    window.alert(message);
}

function escapeActivityHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatActivityLabel(value) {
    return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatActivityDate(value) {
    if (!value) return 'N/A';
    return new Date(value).toLocaleString();
}

async function loadActivityLogOptions() {
    try {
        const { response, data } = await Components.fetchJson(`${ACTIVITY_LOGS_API_BASE}/support/options`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load activity log filters.');
        }

        const actions = Array.isArray(data.data?.actions) ? data.data.actions : [];
        const modules = Array.isArray(data.data?.modules) ? data.data.modules : [];

        document.getElementById('activity-log-action').innerHTML =
            '<option value="">All Actions</option>' +
            actions.map((action) => `<option value="${escapeActivityHtml(action)}">${escapeActivityHtml(formatActivityLabel(action))}</option>`).join('');

        document.getElementById('activity-log-module').innerHTML =
            '<option value="">All Modules</option>' +
            modules.map((module) => `<option value="${escapeActivityHtml(module)}">${escapeActivityHtml(formatActivityLabel(module))}</option>`).join('');
    } catch (error) {
        console.error('Activity log options error', error);
    }
}

async function loadActivityLogs(page = 1) {
    const params = new URLSearchParams({
        page: String(page),
        per_page: '15',
    });

    const search = document.getElementById('activity-log-search').value.trim();
    const action = document.getElementById('activity-log-action').value;
    const module = document.getElementById('activity-log-module').value;
    const from = document.getElementById('activity-log-from').value;
    const to = document.getElementById('activity-log-to').value;

    if (search) params.set('q', search);
    if (action) params.set('action', action);
    if (module) params.set('module', module);
    if (from) params.set('from', from);
    if (to) params.set('to', to);

    try {
        const { response, data } = await Components.fetchJson(`${ACTIVITY_LOGS_API_BASE}?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load activity logs.');
        }

        const logs = Array.isArray(data.data?.logs?.data) ? data.data.logs.data : [];
        const total = Number(data.data?.logs?.total || 0);
        const currentPage = Number(data.data?.logs?.current_page || page);
        const lastPage = Number(data.data?.logs?.last_page || 1);
        activityLogsState = { page: currentPage, totalPages: lastPage };

        document.getElementById('activity-log-total').textContent = `Total: ${total}`;
        document.getElementById('activity-log-summary').textContent = total
            ? `Showing ${logs.length} log entr${logs.length === 1 ? 'y' : 'ies'} from the current filter set.`
            : 'No matching activity logs were found.';
        document.getElementById('activity-log-page-label').textContent = `Page ${currentPage} of ${lastPage}`;
        document.getElementById('activity-log-prev').disabled = currentPage <= 1;
        document.getElementById('activity-log-next').disabled = currentPage >= lastPage;

        const container = document.getElementById('activity-log-table-container');
        if (!logs.length) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No activity logs found.</strong><span>Try adjusting your filters.</span></div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table"><thead><tr>';
        html += '<th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Module</th><th>Description</th><th>Details</th>';
        html += '</tr></thead><tbody>';

        logs.forEach((log) => {
            const userLabel = log.user?.full_name || (log.user_id ? `User #${log.user_id}` : 'System');
            html += '<tr>';
            html += `<td>${escapeActivityHtml(formatActivityDate(log.created_at))}</td>`;
            html += `<td>${escapeActivityHtml(userLabel)}</td>`;
            html += `<td>${escapeActivityHtml(formatActivityLabel(log.user_role || 'system'))}</td>`;
            html += `<td>${escapeActivityHtml(formatActivityLabel(log.action))}</td>`;
            html += `<td>${escapeActivityHtml(formatActivityLabel(log.module))}</td>`;
            html += `<td>${escapeActivityHtml(log.details || 'No description')}</td>`;
            html += `<td><a class="btn btn-sm btn-primary" href="${ACTIVITY_LOGS_PAGE_BASE}/${log.id}">View</a></td>`;
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (error) {
        document.getElementById('activity-log-table-container').innerHTML = '<div class="ui-empty-state"><strong>Failed to load activity logs.</strong></div>';
        activityLogNotify(error.message || 'Unable to load activity logs.');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadActivityLogOptions();
    await loadActivityLogs(1);

    document.getElementById('activity-log-search').addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            loadActivityLogs(1);
        }
    });
    document.getElementById('activity-log-action').addEventListener('change', () => loadActivityLogs(1));
    document.getElementById('activity-log-module').addEventListener('change', () => loadActivityLogs(1));
    document.getElementById('activity-log-from').addEventListener('change', () => loadActivityLogs(1));
    document.getElementById('activity-log-to').addEventListener('change', () => loadActivityLogs(1));
    document.getElementById('activity-log-clear').addEventListener('click', async () => {
        document.getElementById('activity-log-search').value = '';
        document.getElementById('activity-log-action').value = '';
        document.getElementById('activity-log-module').value = '';
        document.getElementById('activity-log-from').value = '';
        document.getElementById('activity-log-to').value = '';
        await loadActivityLogs(1);
    });
    document.getElementById('activity-log-prev').addEventListener('click', () => loadActivityLogs(Math.max(1, activityLogsState.page - 1)));
    document.getElementById('activity-log-next').addEventListener('click', () => loadActivityLogs(Math.min(activityLogsState.totalPages, activityLogsState.page + 1)));
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
