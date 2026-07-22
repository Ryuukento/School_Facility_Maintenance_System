<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$_dspUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_dspRole = strtolower(trim((string)($_dspUser['role'] ?? '')));

$canCreateDispatch = in_array($_dspRole, ['super_admin', 'maintenance_admin'], true);

$pageTitle = 'Dispatches - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container dispatches-page" style="margin-top:16px;">
    <section class="dispatches-page__header">
        <div class="dispatches-page__title-group">
            <p class="dispatches-page__eyebrow">Inventory Deployment</p>
            <h1 class="dispatches-page__title">Dispatches</h1>
            <p class="dispatches-page__description">Manage item deployment from inventory rooms to rooms and laboratories.</p>
        </div>
        <div class="dispatches-page__actions">
            <button type="button" class="btn dispatches-page__secondary-action" onclick="window.print()">Print</button>
            <?php if ($canCreateDispatch): ?>
            <a href="<?php echo htmlspecialchars(public_url('/dispatches/create')); ?>" class="btn dispatches-page__primary-action">+ Create Dispatch</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="dispatches-panel">
        <div class="dispatches-toolbar">
            <div class="dispatches-toolbar__search">
                <label class="dispatches-toolbar__label" for="dispatch-search">Search</label>
                <input type="search" id="dispatch-search" class="form-control dispatches-toolbar__control" placeholder="Search by dispatch code...">
            </div>
            <div class="dispatches-toolbar__filter">
                <label class="dispatches-toolbar__label" for="dispatch-status">Status</label>
                <select id="dispatch-status" class="form-control dispatches-toolbar__control">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="released">Released</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="dispatches-toolbar__action">
                <button type="button" id="dispatch-filter-clear" class="btn dispatches-page__ghost-action">Clear Filters</button>
            </div>
        </div>

        <div class="dispatches-table-panel">
            <div class="dispatches-table-panel__header">
                <div>
                    <h2 class="dispatches-table-panel__title">Dispatch Table</h2>
                    <p class="dispatches-table-panel__description">Review dispatch activity, approval history, and release status at a glance.</p>
                </div>
            </div>

            <div id="dispatch-list-container" class="dispatches-table-shell">
                <div class="ui-empty-state"><strong>Loading dispatches...</strong></div>
            </div>

            <div class="dispatches-pagination">
                <button type="button" class="btn dispatches-page__ghost-action" id="dispatch-prev">Previous</button>
                <span id="dispatch-page-info" class="dispatches-pagination__info">Page 1</span>
                <button type="button" class="btn dispatches-page__ghost-action" id="dispatch-next">Next</button>
            </div>
        </div>
    </section>
</main>

<script>
let dispatchPage = 1;
let dispatchLastPage = 1;

const DISPATCH_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/dispatches') : '/api/dispatches';
const DISPATCH_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/dispatches') : '/dispatches';

function dspEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function dspNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    const _div = document.createElement('div');
    _div.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;background:#dc2626;color:white;padding:12px 20px;border-radius:8px;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.3);max-width:350px;';
    _div.textContent = message;
    document.body.appendChild(_div);
    setTimeout(() => _div.remove(), 4000);
}

function dspMutedDash() {
    return '<span class="dispatches-muted-cell">&mdash;</span>';
}

function dspTextCell(value, { strong = false, fallback = dspMutedDash() } = {}) {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const content = dspEscapeHtml(value);
    return strong ? `<strong class="dispatches-emphasis-cell">${content}</strong>` : content;
}

function dspFormatDate(value) {
    if (!value) {
        return dspMutedDash();
    }

    try {
        return dspEscapeHtml(new Date(value).toLocaleDateString());
    } catch (error) {
        return dspEscapeHtml(value);
    }
}

function dspStatusBadge(status) {
    const normalized = String(status || '').toLowerCase().replace(/[^a-z0-9-]/g, '');
    const label = String(status || '').replace(/\b\w/g, (s) => s.toUpperCase());
    return `<span class="badge badge-${normalized} dispatches-status-badge">${dspEscapeHtml(label || 'Unknown')}</span>`;
}

async function loadDispatches() {
    const search = document.getElementById('dispatch-search').value.trim();
    const status = document.getElementById('dispatch-status').value;

    const params = new URLSearchParams({ page: String(dispatchPage), per_page: '20' });
    if (search) params.set('search', search);
    if (status) params.set('status', status);

    try {
        const fetcher = window.Components && typeof Components.fetchJson === 'function'
            ? Components.fetchJson
            : async (url, options) => {
                const response = await fetch(url, options);
                return { response, data: await response.json() };
            };

        const { response, data: payload } = await fetcher(`${DISPATCH_API_BASE}?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load dispatches');
        }

        const paginator = payload.data;
        const rows = Array.isArray(paginator?.data) ? paginator.data : [];
        dispatchPage = Number(paginator?.current_page || 1);
        dispatchLastPage = Number(paginator?.last_page || 1);

        const container = document.getElementById('dispatch-list-container');

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No dispatches found.</strong><span>Try adjusting your filters or create a new dispatch.</span></div>';
        } else {
            let html = '<div class="table-responsive"><table class="table dispatches-table"><thead><tr>'
                + '<th>Dispatch Code</th><th>Department</th><th>Room</th><th>Items</th>'
                + '<th>Status</th><th>Approved By</th><th>Date</th><th>Action</th>'
                + '</tr></thead><tbody>';

            rows.forEach((row) => {
                html += '<tr>';
                html += `<td>${dspTextCell(row.dispatch_code, { strong: true })}</td>`;
                html += `<td>${dspTextCell(row.department?.name)}</td>`;
                html += `<td>${dspTextCell(row.room?.name)}</td>`;
                html += `<td class="dispatches-table__qty">${dspTextCell(row.item_count)}</td>`;
                html += `<td>${dspStatusBadge(row.status)}</td>`;
                html += `<td>${dspTextCell(row.approved_by_name)}</td>`;
                html += `<td>${dspFormatDate(row.created_at)}</td>`;
                html += `<td><a class="btn btn-sm dispatches-table__view-btn" href="${DISPATCH_PAGE_BASE}/${row.id}">View</a></td>`;
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        document.getElementById('dispatch-page-info').textContent = `Page ${dispatchPage} / ${dispatchLastPage}`;
        document.getElementById('dispatch-prev').disabled = dispatchPage <= 1;
        document.getElementById('dispatch-next').disabled = dispatchPage >= dispatchLastPage;
    } catch (error) {
        document.getElementById('dispatch-list-container').innerHTML =
            '<div class="ui-empty-state"><strong>Failed to load dispatches.</strong></div>';
        dspNotify(error.message || 'Unable to load dispatches.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('dispatch-search').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            dispatchPage = 1;
            loadDispatches();
        }
    });

    document.getElementById('dispatch-status').addEventListener('change', () => {
        dispatchPage = 1;
        loadDispatches();
    });

    document.getElementById('dispatch-filter-clear').addEventListener('click', () => {
        document.getElementById('dispatch-search').value = '';
        document.getElementById('dispatch-status').value = '';
        dispatchPage = 1;
        loadDispatches();
    });

    document.getElementById('dispatch-prev').addEventListener('click', () => {
        if (dispatchPage > 1) {
            dispatchPage -= 1;
            loadDispatches();
        }
    });

    document.getElementById('dispatch-next').addEventListener('click', () => {
        if (dispatchPage < dispatchLastPage) {
            dispatchPage += 1;
            loadDispatches();
        }
    });

    loadDispatches();
});
</script>

<style>
.dispatches-page {
    --dispatch-bg: var(--app-bg);
    --dispatch-surface: #ffffff;
    --dispatch-surface-muted: #f8fafc;
    --dispatch-surface-header: #f8fafc;
    --dispatch-border: #dbe3f0;
    --dispatch-border-strong: #cbd5e1;
    --dispatch-text: #0f172a;
    --dispatch-text-muted: #64748b;
    --dispatch-row-odd: #ffffff;
    --dispatch-row-even: #f8fafc;
    --dispatch-row-hover: #f5f3ff;
    --dispatch-badge-bg: rgba(109, 40, 217, 0.1);
    --dispatch-badge-border: rgba(109, 40, 217, 0.16);
    --dispatch-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    color: var(--dispatch-text);
}

:root[data-theme-resolved='dark'] .dispatches-page {
    --dispatch-surface: #0f172a;
    --dispatch-surface-muted: #111c31;
    --dispatch-surface-header: #13203a;
    --dispatch-border: #23314b;
    --dispatch-border-strong: #31415d;
    --dispatch-text: #e2e8f0;
    --dispatch-text-muted: #94a3b8;
    --dispatch-row-odd: #0f172a;
    --dispatch-row-even: #111c31;
    --dispatch-row-hover: #16233d;
    --dispatch-badge-bg: rgba(139, 92, 246, 0.16);
    --dispatch-badge-border: rgba(139, 92, 246, 0.24);
    --dispatch-shadow: 0 22px 48px rgba(2, 8, 23, 0.28);
}

.dispatches-page__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.dispatches-page__title-group {
    max-width: 720px;
}

.dispatches-page__eyebrow {
    margin: 0 0 8px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #7c3aed;
}

.dispatches-page__title {
    margin: 0;
    font-size: clamp(1.75rem, 2.4vw, 2.35rem);
    line-height: 1.05;
    color: var(--dispatch-text);
}

.dispatches-page__description {
    margin: 10px 0 0;
    max-width: 62ch;
    color: var(--dispatch-text-muted);
    font-size: 0.98rem;
}

.dispatches-page__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12px;
}

.dispatches-panel {
    background: var(--dispatch-surface);
    border: 1px solid var(--dispatch-border);
    border-radius: 16px;
    box-shadow: var(--dispatch-shadow);
    overflow: hidden;
}

.dispatches-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(190px, 0.8fr) auto;
    gap: 14px;
    padding: 22px 24px;
    border-bottom: 1px solid var(--dispatch-border);
    background: color-mix(in srgb, var(--dispatch-surface) 86%, var(--dispatch-surface-muted));
}

.dispatches-toolbar__label {
    display: block;
    margin: 0 0 8px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--dispatch-text-muted);
}

.dispatches-toolbar__control,
.dispatches-page__primary-action,
.dispatches-page__secondary-action,
.dispatches-page__ghost-action {
    min-height: 44px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
}

.dispatches-toolbar__control {
    width: 100%;
    background: var(--dispatch-surface);
    color: var(--dispatch-text);
    border: 1px solid var(--dispatch-border-strong);
    box-shadow: none;
}

.dispatches-toolbar__control:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.12);
    transform: none;
}

.dispatches-page__primary-action {
    padding: 10px 18px;
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    border: 1px solid rgba(109, 40, 217, 0.38);
    color: #ffffff;
    box-shadow: none;
}

.dispatches-page__primary-action:hover {
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
}

.dispatches-page__secondary-action,
.dispatches-page__ghost-action {
    padding: 10px 16px;
    background: var(--dispatch-surface);
    color: var(--dispatch-text);
    border: 1px solid var(--dispatch-border-strong);
    box-shadow: none;
}

.dispatches-page__secondary-action:hover,
.dispatches-page__ghost-action:hover {
    background: var(--dispatch-surface-muted);
    border-color: #a78bfa;
}

.dispatches-toolbar__action {
    display: flex;
    align-items: end;
}

.dispatches-table-panel {
    padding: 0;
}

.dispatches-table-panel__header {
    padding: 24px 24px 16px;
    border-bottom: 1px solid var(--dispatch-border);
    background: var(--dispatch-surface);
}

.dispatches-table-panel__title {
    margin: 0;
    font-size: 1.05rem;
    color: var(--dispatch-text);
}

.dispatches-table-panel__description {
    margin: 8px 0 0;
    color: var(--dispatch-text-muted);
    font-size: 0.93rem;
}

.dispatches-table-shell {
    padding: 0 24px 24px;
}

.dispatches-table-shell .ui-empty-state {
    margin-top: 24px;
    background: color-mix(in srgb, var(--dispatch-surface-muted) 88%, transparent);
    border-color: var(--dispatch-border-strong);
    color: var(--dispatch-text-muted);
}

.dispatches-table-shell .ui-empty-state strong {
    color: var(--dispatch-text);
}

.dispatches-page .table-responsive {
    width: 100%;
    overflow-x: auto;
    padding-top: 24px;
}

.dispatches-page .dispatches-table {
    width: 100%;
    min-width: 940px;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--dispatch-surface);
    border: 1px solid var(--dispatch-border);
    border-radius: 14px;
    overflow: hidden;
}

.dispatches-page .dispatches-table thead,
.dispatches-page .dispatches-table thead tr,
.dispatches-page .dispatches-table thead th {
    display: table-header-group;
    background: var(--dispatch-surface-header);
}

.dispatches-page .dispatches-table thead tr {
    display: table-row;
}

.dispatches-page .dispatches-table th {
    padding: 14px 16px;
    border: 0;
    border-bottom: 1px solid var(--dispatch-border);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.01em;
    text-transform: none;
    color: var(--dispatch-text-muted);
    white-space: nowrap;
}

.dispatches-page .dispatches-table tbody tr {
    display: table-row;
    background: var(--dispatch-row-odd);
    transition: background-color 160ms ease;
}

.dispatches-page .dispatches-table tbody tr:nth-child(even) {
    background: var(--dispatch-row-even);
}

.dispatches-page .dispatches-table tbody tr:nth-child(odd) {
    background: var(--dispatch-row-odd);
}

.dispatches-page .dispatches-table tbody tr:hover {
    background: var(--dispatch-row-hover);
}

.dispatches-page .dispatches-table td {
    display: table-cell;
    padding: 15px 16px;
    border: 0;
    border-bottom: 1px solid var(--dispatch-border);
    background: transparent;
    color: var(--dispatch-text);
    vertical-align: middle;
}

.dispatches-page .dispatches-table tbody tr:last-child td {
    border-bottom: 0;
}

.dispatches-emphasis-cell {
    font-weight: 700;
    color: var(--dispatch-text);
}

.dispatches-muted-cell {
    color: var(--dispatch-text-muted);
}

.dispatches-table__qty {
    text-align: center;
    font-variant-numeric: tabular-nums;
}

.dispatches-status-badge {
    padding: 5px 10px !important;
    border-radius: 999px !important;
    font-size: 11px !important;
    letter-spacing: 0.02em !important;
    text-transform: none !important;
    box-shadow: none;
}

.dispatches-table__view-btn {
    min-height: 34px;
    padding: 6px 12px;
    border-radius: 10px;
    border: 1px solid var(--dispatch-badge-border);
    background: var(--dispatch-badge-bg);
    color: #7c3aed;
    box-shadow: none;
}

.dispatches-table__view-btn:hover {
    background: rgba(124, 58, 237, 0.16);
    border-color: rgba(124, 58, 237, 0.24);
    color: #6d28d9;
}

:root[data-theme-resolved='dark'] .dispatches-page .dispatches-table__view-btn {
    color: #c4b5fd;
}

:root[data-theme-resolved='dark'] .dispatches-page .dispatches-table__view-btn:hover {
    color: #ede9fe;
}

.dispatches-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 0 24px 24px;
}

.dispatches-pagination__info {
    color: var(--dispatch-text-muted);
    font-size: 0.95rem;
}

@media (max-width: 980px) {
    .dispatches-page__header {
        flex-direction: column;
    }

    .dispatches-page__actions {
        justify-content: flex-start;
    }

    .dispatches-toolbar {
        grid-template-columns: minmax(0, 1fr) minmax(180px, 0.7fr);
    }

    .dispatches-toolbar__action {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .dispatches-toolbar,
    .dispatches-table-panel__header,
    .dispatches-table-shell,
    .dispatches-pagination {
        padding-left: 16px;
        padding-right: 16px;
    }

    .dispatches-toolbar {
        grid-template-columns: 1fr;
    }

    .dispatches-page__actions {
        width: 100%;
    }

    .dispatches-page__actions .btn {
        flex: 1 1 180px;
    }

    .dispatches-pagination {
        flex-wrap: wrap;
        justify-content: flex-start;
    }

    .dispatches-page .dispatches-table {
        min-width: 820px;
    }

    .dispatches-page .dispatches-table thead {
        display: table-header-group !important;
    }

    .dispatches-page .dispatches-table tbody tr {
        display: table-row !important;
        margin-bottom: 0;
        border: 0;
        border-radius: 0;
    }

    .dispatches-page .dispatches-table td {
        display: table-cell !important;
        text-align: left !important;
        padding-left: 16px !important;
    }

    .dispatches-page .dispatches-table td::before,
    .dispatches-page .dispatches-table td::after {
        content: none !important;
        display: none !important;
    }
}

@media (max-width: 540px) {
    .dispatches-page__actions .btn {
        width: 100%;
    }
}
</style>

<style media="print">
  aside, nav, .sidebar, header,
  #dispatch-search, #dispatch-status,
  .btn { display: none !important; }
  .table { width: 100%; min-width: 0 !important; }
  body { background: white; color: black; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
