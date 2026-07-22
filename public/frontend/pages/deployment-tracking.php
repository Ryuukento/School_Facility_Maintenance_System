<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$_dtUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_dtRole = strtolower(trim((string)($_dtUser['role'] ?? '')));

if (!in_array($_dtRole, ['super_admin', 'maintenance_admin'], true)) {
    header('Location: ' . public_url('/dashboard'));
    exit;
}

$pageTitle = 'Deployment Tracking - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container deployment-tracking-page" style="margin-top:16px;">
    <section class="deployment-tracking-page__header">
        <div class="deployment-tracking-page__title-group">
            <p class="deployment-tracking-page__eyebrow">Released Inventory Visibility</p>
            <h1 class="deployment-tracking-page__title">Deployment Tracking</h1>
            <p class="deployment-tracking-page__description">Every released dispatch item, with its source purchase receipt where available.</p>
        </div>
    </section>

    <section class="deployment-tracking-panel">
        <div class="deployment-tracking-panel__header">
            <div>
                <h2 class="deployment-tracking-panel__title">All Deployed Items</h2>
                <p class="deployment-tracking-panel__description">Track deployed inventory by room, department, dispatch, and purchase source.</p>
            </div>
            <p id="dt-result-count" class="deployment-tracking-panel__count text-muted">Loading results...</p>
        </div>

        <div class="deployment-tracking-toolbar">
            <div class="deployment-tracking-toolbar__search">
                <label class="deployment-tracking-toolbar__label" for="dt-search">Search</label>
                <input type="search" id="dt-search" class="form-control deployment-tracking-toolbar__control" placeholder="Search deployed items...">
            </div>
            <div class="deployment-tracking-toolbar__filter">
                <label class="deployment-tracking-toolbar__label" for="dt-room">Room</label>
                <select id="dt-room" class="form-control deployment-tracking-toolbar__control">
                    <option value="">All Rooms</option>
                </select>
            </div>
            <div class="deployment-tracking-toolbar__filter">
                <label class="deployment-tracking-toolbar__label" for="dt-dept">Department</label>
                <select id="dt-dept" class="form-control deployment-tracking-toolbar__control">
                    <option value="">All Departments</option>
                </select>
            </div>
            <div class="deployment-tracking-toolbar__action">
                <button type="button" class="btn deployment-tracking-page__ghost-action" id="dt-filter-clear">Clear Filters</button>
            </div>
        </div>

        <div id="dt-deployed-container" class="deployment-tracking-table-shell">
            <div class="ui-empty-state"><strong>Loading deployed items...</strong></div>
        </div>
    </section>

    <section class="deployment-tracking-trace-panel">
        <div class="deployment-tracking-trace-panel__header">
            <div>
                <h2 class="deployment-tracking-trace-panel__title">Trace by OR Number</h2>
                <p class="deployment-tracking-trace-panel__description">Search receipts directly to see where received items were ultimately deployed.</p>
            </div>
        </div>

        <div class="deployment-tracking-trace-toolbar">
            <div class="deployment-tracking-trace-toolbar__search">
                <label class="deployment-tracking-toolbar__label" for="dt-or-input">OR Number</label>
                <input type="text" id="dt-or-input" class="form-control deployment-tracking-toolbar__control" placeholder="Enter OR number (e.g. OR-2026-001)">
            </div>
            <div class="deployment-tracking-trace-toolbar__action-group">
                <button type="button" class="btn deployment-tracking-page__primary-action" id="dt-or-search-btn">Search</button>
                <button type="button" class="btn deployment-tracking-page__ghost-action" id="dt-or-clear-btn">Clear</button>
            </div>
        </div>

        <p class="deployment-tracking-trace-panel__hint">Partial matches are supported. Searches by OR number only.</p>

        <div id="dt-or-results" class="deployment-tracking-trace-results">
            <div class="ui-empty-state">
                <strong>Enter an OR number above to trace items.</strong>
            </div>
        </div>
    </section>
</main>

<script>
const DT_API = '/api/deployment-tracking';
let dtFilterOptionsLoaded = false;

function dtEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function dtMutedDash() {
    return '<span class="deployment-tracking-muted-cell">&mdash;</span>';
}

function dtTextCell(value, options = {}) {
    const strong = Boolean(options.strong);
    const fallback = options.fallback || dtMutedDash();

    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const content = dtEscapeHtml(value);
    return strong ? `<strong class="deployment-tracking-emphasis-cell">${content}</strong>` : content;
}

function dtFormatDate(d) {
    if (!d) return dtMutedDash();
    try {
        return dtEscapeHtml(new Date(d).toLocaleDateString());
    } catch (e) {
        return dtEscapeHtml(String(d));
    }
}

function dtStatusBadge(status) {
    const normalized = String(status || 'unknown').toLowerCase().replace(/[^a-z0-9-]/g, '');
    const label = String(status || 'Unknown').replace(/\b\w/g, (c) => c.toUpperCase());
    return `<span class="deployment-status-badge" data-status="${dtEscapeHtml(normalized)}">${dtEscapeHtml(label)}</span>`;
}

function dtReceiptBadge(status) {
    const normalized = status === 'posted' ? 'posted' : 'draft';
    const label = normalized === 'posted' ? 'Posted' : 'Draft';
    return `<span class="deployment-receipt-badge" data-status="${normalized}">${label}</span>`;
}

function dtNotify(message, type = 'danger') {
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

async function dtFetch(url, options = {}) {
    const fetcher = window.Components && typeof Components.fetchJson === 'function'
        ? Components.fetchJson
        : async (u, o) => {
            const r = await fetch(u, o);
            return { response: r, data: await r.json() };
        };
    return fetcher(url, options);
}

function dtSetResultCount(text) {
    const target = document.getElementById('dt-result-count');
    if (target) {
        target.textContent = text;
    }
}

async function searchByOr() {
    const q = document.getElementById('dt-or-input').value.trim();
    const resultsEl = document.getElementById('dt-or-results');

    if (!q) {
        resultsEl.innerHTML = '<div class="ui-empty-state"><strong>Enter an OR number above to trace items.</strong></div>';
        return;
    }

    resultsEl.innerHTML = '<div class="ui-empty-state"><strong>Searching...</strong></div>';

    try {
        const params = new URLSearchParams({ q });
        const { data: payload } = await dtFetch(`${DT_API}/search?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (!payload || !payload.success) {
            throw new Error((payload && payload.message) || 'Failed to search');
        }

        const rows = Array.isArray(payload.data?.rows) ? payload.data.rows : [];
        const receipts = Array.isArray(payload.data?.receipts) ? payload.data.receipts : [];

        if (rows.length === 0) {
            const message = payload.data?.message || `No items found for OR number matching "${q}".`;
            resultsEl.innerHTML = `<div class="ui-empty-state"><strong>${dtEscapeHtml(message)}</strong></div>`;
            return;
        }

        let html = '';

        if (receipts.length > 0) {
            html += '<div class="deployment-tracking-receipt-grid">';
            receipts.forEach((pr) => {
                html += '<article class="deployment-tracking-receipt-card">';
                html += `<div class="deployment-tracking-receipt-card__code">${dtEscapeHtml(pr.or_number)}</div>`;
                html += '<div class="deployment-tracking-receipt-card__meta">';
                html += `<span>${dtEscapeHtml(pr.supplier_name || 'Unknown supplier')}</span>`;
                html += `<span>${dtFormatDate(pr.receipt_date)}</span>`;
                html += dtReceiptBadge(pr.status);
                html += '</div></article>';
            });
            html += '</div>';
        }

        html += '<div class="table-responsive"><table class="table deployment-tracking-table deployment-tracking-table--trace"><thead><tr>'
            + '<th>OR Number</th>'
            + '<th>Supplier</th>'
            + '<th>Date Received</th>'
            + '<th>Item Name</th>'
            + '<th>Qty Received</th>'
            + '<th>Deployed To</th>'
            + '<th>Dispatch Code</th>'
            + '<th>Dispatch Status</th>'
            + '</tr></thead><tbody>';

        rows.forEach((row) => {
            const deployedTo = row.room_name
                ? `<div class="deployment-tracking-location-cell">${dtEscapeHtml(row.room_name)}${row.department_name ? `<span>${dtEscapeHtml(row.department_name)}</span>` : ''}</div>`
                : '<span class="deployment-tracking-muted-cell deployment-tracking-muted-cell--italic">In Bodega / Not Deployed</span>';

            html += '<tr>';
            html += `<td>${dtTextCell(row.or_number, { strong: true })}</td>`;
            html += `<td>${dtTextCell(row.supplier_name)}</td>`;
            html += `<td>${dtFormatDate(row.receipt_date)}</td>`;
            html += `<td>${dtTextCell(row.receipt_item_name, { strong: true })}</td>`;
            html += `<td class="deployment-tracking-table__qty">${dtTextCell(row.quantity_received)} ${dtTextCell(row.unit || 'pc', { fallback: 'pc' })}</td>`;
            html += `<td>${deployedTo}</td>`;
            html += `<td>${dtTextCell(row.dispatch_code)}</td>`;
            html += `<td>${row.dispatch_status ? dtStatusBadge(row.dispatch_status) : dtMutedDash()}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        resultsEl.innerHTML = html;
    } catch (err) {
        resultsEl.innerHTML = '<div class="ui-empty-state"><strong>Search failed.</strong></div>';
        dtNotify(err.message || 'Unable to search.');
    }
}

async function loadDeployed() {
    const container = document.getElementById('dt-deployed-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';

    const q = document.getElementById('dt-search').value.trim();
    const roomId = document.getElementById('dt-room').value;
    const deptId = document.getElementById('dt-dept').value;

    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (roomId) params.set('room_id', roomId);
    if (deptId) params.set('department_id', deptId);

    try {
        const { data: payload } = await dtFetch(`${DT_API}?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (!payload || !payload.success) {
            throw new Error((payload && payload.message) || 'Failed to load');
        }

        const rows = Array.isArray(payload.data?.rows) ? payload.data.rows : [];

        if (!dtFilterOptionsLoaded && payload.data?.filter_options) {
            const opts = payload.data.filter_options;

            const roomSel = document.getElementById('dt-room');
            const currentRoom = roomSel.value;
            while (roomSel.options.length > 1) roomSel.remove(1);
            (opts.rooms || []).forEach((r) => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                if (String(r.id) === currentRoom) opt.selected = true;
                roomSel.appendChild(opt);
            });

            const deptSel = document.getElementById('dt-dept');
            const currentDept = deptSel.value;
            while (deptSel.options.length > 1) deptSel.remove(1);
            (opts.departments || []).forEach((d) => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.name;
                if (String(d.id) === currentDept) opt.selected = true;
                deptSel.appendChild(opt);
            });

            dtFilterOptionsLoaded = true;
        }

        dtSetResultCount(`${rows.length} result${rows.length === 1 ? '' : 's'}`);

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No deployed items found.</strong><span>Try adjusting the filters.</span></div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table deployment-tracking-table"><thead><tr>'
            + '<th>Item Name</th>'
            + '<th>Qty Deployed</th>'
            + '<th>Source OR</th>'
            + '<th>Supplier</th>'
            + '<th>Date Received</th>'
            + '<th>Room / Lab</th>'
            + '<th>Department</th>'
            + '<th>Dispatch Code</th>'
            + '<th>Dispatch Date</th>'
            + '<th>Status</th>'
            + '</tr></thead><tbody>';

        rows.forEach((row) => {
            html += '<tr>';
            html += `<td>${dtTextCell(row.item_name, { strong: true })}</td>`;
            html += `<td class="deployment-tracking-table__qty">${dtTextCell(row.dispatched_qty)}</td>`;
            html += `<td>${dtTextCell(row.source_or, { strong: true })}</td>`;
            html += `<td>${dtTextCell(row.source_supplier)}</td>`;
            html += `<td>${dtFormatDate(row.source_receipt_date)}</td>`;
            html += `<td>${dtTextCell(row.room_name)}</td>`;
            html += `<td>${dtTextCell(row.department_name)}</td>`;
            html += `<td>${dtTextCell(row.dispatch_code, { strong: true })}</td>`;
            html += `<td>${dtFormatDate(row.dispatch_date)}</td>`;
            html += `<td>${dtStatusBadge(row.dispatch_status)}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (err) {
        dtSetResultCount('Unable to load results');
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load deployed items.</strong></div>';
        dtNotify(err.message || 'Unable to load.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('dt-or-search-btn').addEventListener('click', searchByOr);
    document.getElementById('dt-or-input').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') searchByOr();
    });
    document.getElementById('dt-or-clear-btn').addEventListener('click', () => {
        document.getElementById('dt-or-input').value = '';
        document.getElementById('dt-or-results').innerHTML =
            '<div class="ui-empty-state"><strong>Enter an OR number above to trace items.</strong></div>';
    });

    document.getElementById('dt-search').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') loadDeployed();
    });
    document.getElementById('dt-room').addEventListener('change', loadDeployed);
    document.getElementById('dt-dept').addEventListener('change', loadDeployed);
    document.getElementById('dt-filter-clear').addEventListener('click', () => {
        document.getElementById('dt-search').value = '';
        document.getElementById('dt-room').value = '';
        document.getElementById('dt-dept').value = '';
        loadDeployed();
    });

    loadDeployed();
});
</script>

<style>
.deployment-tracking-page {
    --deployment-surface: #ffffff;
    --deployment-surface-muted: #f8fafc;
    --deployment-surface-header: #f8fafc;
    --deployment-border: #dbe3f0;
    --deployment-border-strong: #cbd5e1;
    --deployment-text: #0f172a;
    --deployment-text-muted: #64748b;
    --deployment-row-odd: #ffffff;
    --deployment-row-even: #f8fafc;
    --deployment-row-hover: #f5f3ff;
    --deployment-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    color: var(--deployment-text);
}

:root[data-theme-resolved='dark'] .deployment-tracking-page {
    --deployment-surface: #0f172a;
    --deployment-surface-muted: #111c31;
    --deployment-surface-header: #13203a;
    --deployment-border: #23314b;
    --deployment-border-strong: #31415d;
    --deployment-text: #e2e8f0;
    --deployment-text-muted: #94a3b8;
    --deployment-row-odd: #0f172a;
    --deployment-row-even: #111c31;
    --deployment-row-hover: #16233d;
    --deployment-shadow: 0 22px 48px rgba(2, 8, 23, 0.28);
}

.deployment-tracking-page__header {
    margin-bottom: 20px;
}

.deployment-tracking-page__eyebrow {
    margin: 0 0 8px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #7c3aed;
}

.deployment-tracking-page__title {
    margin: 0;
    font-size: clamp(1.75rem, 2.4vw, 2.35rem);
    line-height: 1.05;
    color: var(--deployment-text);
}

.deployment-tracking-page__description {
    margin: 10px 0 0;
    max-width: 62ch;
    color: var(--deployment-text-muted);
    font-size: 0.98rem;
}

.deployment-tracking-panel,
.deployment-tracking-trace-panel {
    background: var(--deployment-surface);
    border: 1px solid var(--deployment-border);
    border-radius: 16px;
    box-shadow: var(--deployment-shadow);
    overflow: hidden;
}

.deployment-tracking-trace-panel {
    margin-top: 18px;
}

.deployment-tracking-panel__header,
.deployment-tracking-trace-panel__header {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 18px;
    padding: 24px 24px 18px;
    border-bottom: 1px solid var(--deployment-border);
    background: var(--deployment-surface);
}

.deployment-tracking-panel__title,
.deployment-tracking-trace-panel__title {
    margin: 0;
    font-size: 1.08rem;
    color: var(--deployment-text);
}

.deployment-tracking-panel__description,
.deployment-tracking-trace-panel__description {
    margin: 8px 0 0;
    color: var(--deployment-text-muted);
    font-size: 0.93rem;
}

.deployment-tracking-panel__count {
    margin: 0;
    white-space: nowrap;
    color: var(--deployment-text-muted) !important;
}

.deployment-tracking-toolbar,
.deployment-tracking-trace-toolbar {
    display: grid;
    gap: 14px;
    padding: 22px 24px;
    border-bottom: 1px solid var(--deployment-border);
    background: color-mix(in srgb, var(--deployment-surface) 86%, var(--deployment-surface-muted));
}

.deployment-tracking-toolbar {
    grid-template-columns: minmax(0, 1.7fr) minmax(180px, 0.75fr) minmax(180px, 0.75fr) auto;
}

.deployment-tracking-trace-toolbar {
    grid-template-columns: minmax(0, 1fr) auto;
}

.deployment-tracking-toolbar__label {
    display: block;
    margin: 0 0 8px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--deployment-text-muted);
}

.deployment-tracking-toolbar__control,
.deployment-tracking-page__primary-action,
.deployment-tracking-page__ghost-action {
    min-height: 44px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
}

.deployment-tracking-toolbar__control {
    width: 100%;
    background: var(--deployment-surface);
    color: var(--deployment-text);
    border: 1px solid var(--deployment-border-strong);
    box-shadow: none;
}

.deployment-tracking-toolbar__control:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.12);
    transform: none;
}

.deployment-tracking-toolbar__action,
.deployment-tracking-trace-toolbar__action-group {
    display: flex;
    align-items: end;
    gap: 10px;
}

.deployment-tracking-page__primary-action {
    padding: 10px 18px;
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    border: 1px solid rgba(109, 40, 217, 0.38);
    color: #ffffff;
    box-shadow: none;
}

.deployment-tracking-page__primary-action:hover {
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
}

.deployment-tracking-page__ghost-action {
    padding: 10px 16px;
    background: var(--deployment-surface);
    color: var(--deployment-text);
    border: 1px solid var(--deployment-border-strong);
    box-shadow: none;
}

.deployment-tracking-page__ghost-action:hover {
    background: var(--deployment-surface-muted);
    border-color: #a78bfa;
}

.deployment-tracking-table-shell,
.deployment-tracking-trace-results {
    padding: 0 24px 24px;
}

.deployment-tracking-trace-panel__hint {
    margin: 0;
    padding: 16px 24px 0;
    color: var(--deployment-text-muted);
    font-size: 0.88rem;
}

.deployment-tracking-table-shell .ui-empty-state,
.deployment-tracking-trace-results .ui-empty-state {
    margin-top: 24px;
    background: color-mix(in srgb, var(--deployment-surface-muted) 88%, transparent);
    border-color: var(--deployment-border-strong);
    color: var(--deployment-text-muted);
}

.deployment-tracking-table-shell .ui-empty-state strong,
.deployment-tracking-trace-results .ui-empty-state strong {
    color: var(--deployment-text);
}

.deployment-tracking-page .table-responsive {
    width: 100%;
    overflow-x: auto;
    padding-top: 24px;
}

.deployment-tracking-page .deployment-tracking-table {
    width: 100%;
    min-width: 1100px;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--deployment-surface);
    border: 1px solid var(--deployment-border);
    border-radius: 14px;
    overflow: hidden;
}

.deployment-tracking-page .deployment-tracking-table thead,
.deployment-tracking-page .deployment-tracking-table thead tr,
.deployment-tracking-page .deployment-tracking-table thead th {
    display: table-header-group;
    background: var(--deployment-surface-header);
}

.deployment-tracking-page .deployment-tracking-table thead tr {
    display: table-row;
}

.deployment-tracking-page .deployment-tracking-table th {
    padding: 14px 16px;
    border: 0;
    border-bottom: 1px solid var(--deployment-border);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.01em;
    text-transform: none;
    color: var(--deployment-text-muted);
    white-space: nowrap;
}

.deployment-tracking-page .deployment-tracking-table tbody tr {
    display: table-row;
    background: var(--deployment-row-odd);
    transition: background-color 160ms ease;
}

.deployment-tracking-page .deployment-tracking-table tbody tr:nth-child(even) {
    background: var(--deployment-row-even);
}

.deployment-tracking-page .deployment-tracking-table tbody tr:nth-child(odd) {
    background: var(--deployment-row-odd);
}

.deployment-tracking-page .deployment-tracking-table tbody tr:hover {
    background: var(--deployment-row-hover);
}

.deployment-tracking-page .deployment-tracking-table td {
    display: table-cell;
    padding: 15px 16px;
    border: 0;
    border-bottom: 1px solid var(--deployment-border);
    background: transparent;
    color: var(--deployment-text);
    vertical-align: middle;
}

.deployment-tracking-page .deployment-tracking-table tbody tr:last-child td {
    border-bottom: 0;
}

.deployment-tracking-emphasis-cell {
    color: var(--deployment-text);
    font-weight: 700;
}

.deployment-tracking-muted-cell {
    color: var(--deployment-text-muted);
}

.deployment-tracking-muted-cell--italic {
    font-style: italic;
}

.deployment-tracking-table__qty {
    text-align: center;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.deployment-tracking-location-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
    line-height: 1.35;
}

.deployment-tracking-location-cell span {
    color: var(--deployment-text-muted);
    font-size: 0.82rem;
}

.deployment-status-badge,
.deployment-receipt-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.01em;
    white-space: nowrap;
    border: 1px solid transparent;
}

.deployment-status-badge[data-status='released'] {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
    border-color: rgba(37, 99, 235, 0.2);
}

.deployment-status-badge[data-status='approved'] {
    background: rgba(5, 150, 105, 0.12);
    color: #059669;
    border-color: rgba(5, 150, 105, 0.2);
}

.deployment-status-badge[data-status='pending'] {
    background: rgba(217, 119, 6, 0.14);
    color: #b45309;
    border-color: rgba(217, 119, 6, 0.2);
}

.deployment-status-badge[data-status='cancelled'] {
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
    border-color: rgba(220, 38, 38, 0.2);
}

.deployment-status-badge:not([data-status='released']):not([data-status='approved']):not([data-status='pending']):not([data-status='cancelled']) {
    background: rgba(100, 116, 139, 0.14);
    color: var(--deployment-text-muted);
    border-color: rgba(100, 116, 139, 0.22);
}

.deployment-receipt-badge[data-status='posted'] {
    background: rgba(5, 150, 105, 0.12);
    color: #059669;
    border-color: rgba(5, 150, 105, 0.2);
}

.deployment-receipt-badge[data-status='draft'] {
    background: rgba(217, 119, 6, 0.14);
    color: #b45309;
    border-color: rgba(217, 119, 6, 0.2);
}

.deployment-tracking-receipt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    padding-top: 24px;
}

.deployment-tracking-receipt-card {
    background: color-mix(in srgb, var(--deployment-surface-muted) 84%, var(--deployment-surface));
    border: 1px solid var(--deployment-border);
    border-radius: 14px;
    padding: 16px;
}

.deployment-tracking-receipt-card__code {
    font-size: 1rem;
    font-weight: 700;
    color: var(--deployment-text);
}

.deployment-tracking-receipt-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 10px;
    margin-top: 8px;
    color: var(--deployment-text-muted);
    font-size: 0.86rem;
}

@media (max-width: 1080px) {
    .deployment-tracking-toolbar {
        grid-template-columns: minmax(0, 1fr) minmax(180px, 0.7fr) minmax(180px, 0.7fr);
    }

    .deployment-tracking-toolbar__action {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .deployment-tracking-panel__header,
    .deployment-tracking-trace-panel__header,
    .deployment-tracking-toolbar,
    .deployment-tracking-trace-toolbar,
    .deployment-tracking-table-shell,
    .deployment-tracking-trace-results,
    .deployment-tracking-trace-panel__hint {
        padding-left: 16px;
        padding-right: 16px;
    }

    .deployment-tracking-panel__header,
    .deployment-tracking-trace-panel__header {
        flex-direction: column;
        align-items: flex-start;
    }

    .deployment-tracking-toolbar,
    .deployment-tracking-trace-toolbar {
        grid-template-columns: 1fr;
    }

    .deployment-tracking-trace-toolbar__action-group {
        align-items: stretch;
        flex-wrap: wrap;
    }

    .deployment-tracking-page .deployment-tracking-table {
        min-width: 980px;
    }

    .deployment-tracking-page .deployment-tracking-table thead {
        display: table-header-group !important;
    }

    .deployment-tracking-page .deployment-tracking-table tbody tr {
        display: table-row !important;
        margin-bottom: 0;
        border: 0;
        border-radius: 0;
    }

    .deployment-tracking-page .deployment-tracking-table td {
        display: table-cell !important;
        text-align: left !important;
        padding-left: 16px !important;
    }

    .deployment-tracking-page .deployment-tracking-table td::before,
    .deployment-tracking-page .deployment-tracking-table td::after {
        content: none !important;
        display: none !important;
    }
}

@media (max-width: 540px) {
    .deployment-tracking-trace-toolbar__action-group .btn {
        width: 100%;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
