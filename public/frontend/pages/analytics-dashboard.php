<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$_anUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_anRole = strtolower(trim((string)($_anUser['role'] ?? '')));
if ($_anRole !== 'super_admin' && $_anRole !== 'maintenance_admin' && $_anRole !== 'maintenance_staff') {
    $_anRole = 'maintenance_staff';
}

$pageTitle = 'Analytics Dashboard - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<style media="print">
    aside, nav, .sidebar, [class*="sidebar"], header { display: none !important; }
    .an-tabs, .an-filter-row, #an-print-btn          { display: none !important; }
    .an-tab-content                                  { display: block !important; page-break-inside: avoid; margin-bottom: 24px; }
    main, .container                                 { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .card                                            { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
</style>

<main class="container" style="margin-top:16px;">
    <div class="card" id="an-analytics-card" style="border-left:3px solid var(--primary-color);">
        <div class="card-header d-flex justify-between align-center" style="border-bottom:0.5px solid var(--border);">
            <div>
                <h2 style="font-size:18px;font-weight:500;margin:0 0 2px;">Analytics Dashboard</h2>
                <p class="text-muted mb-0" style="font-size:13px;">Inventory health, damage trends, semester comparisons, and dispatch reports.</p>
            </div>
        </div>
        <div class="card-body">

            <!-- Tab buttons -->
            <div class="an-tabs" style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:20px;">
                <button type="button" class="an-tab-btn" data-tab="an-tab1"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Overview
                </button>
                <button type="button" class="an-tab-btn" data-tab="an-tab2"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Damage Analytics
                </button>
                <button type="button" class="an-tab-btn" data-tab="an-tab3"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Semester Comparison
                </button>
                <button type="button" class="an-tab-btn" data-tab="an-tab4"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Dispatch &amp; Repair
                </button>
            </div>

            <!-- ─── TAB 1: Overview ──────────────────────────────────────── -->
            <div id="an-tab1" class="an-tab-content">

                <p style="font-size:14px;font-weight:500;margin:0 0 12px;">Inventory Health</p>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;">

                    <!-- Total Items — blue -->
                    <div style="border:1px solid rgba(37,99,235,0.2);border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,rgba(37,99,235,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(37,99,235,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Total Items</div>
                            <div id="an-h-total" style="font-size:32px;font-weight:700;color:#1d4ed8;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">In inventory</div>
                        </div>
                    </div>

                    <!-- Low Stock — orange -->
                    <div style="border:1px solid rgba(245,158,11,0.25);border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,rgba(245,158,11,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Low Stock</div>
                            <div id="an-h-lowstock" style="font-size:32px;font-weight:700;color:#d97706;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">Need restocking</div>
                        </div>
                    </div>

                    <!-- Out of Stock — red -->
                    <div style="border:1px solid rgba(239,68,68,0.22);border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,rgba(239,68,68,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Out of Stock</div>
                            <div id="an-h-outofstock" style="font-size:32px;font-weight:700;color:#dc2626;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">Depleted items</div>
                        </div>
                    </div>

                    <!-- Low Stock % — purple -->
                    <div style="border:1px solid rgba(139,92,246,0.22);border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,rgba(139,92,246,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(139,92,246,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="5" x2="5" y2="19"/>
                                <circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Low Stock %</div>
                            <div id="an-h-pct" style="font-size:32px;font-weight:700;color:#7c3aed;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">Stock health</div>
                        </div>
                    </div>

                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 8px;">Items Needing Attention</p>
                <div id="an-lowstock-container" class="table-responsive">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

            </div>

            <!-- ─── TAB 2: Damage Analytics ──────────────────────────────── -->
            <div id="an-tab2" class="an-tab-content" style="display:none;">

                <div class="an-filter-row"
                     style="display:grid;grid-template-columns:1fr 160px 160px auto;gap:10px;margin-bottom:16px;align-items:end;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Department</label>
                        <select id="an-dept-filter" class="form-control">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date From</label>
                        <input type="date" id="an-dmg-from" class="form-control">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date To</label>
                        <input type="date" id="an-dmg-to" class="form-control">
                    </div>
                    <div>
                        <button type="button" id="an-dmg-apply" class="btn btn-primary" style="width:100%;">Apply</button>
                    </div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 8px;">Most Damaged Items</p>
                <div style="height:280px;margin-bottom:24px;position:relative;">
                    <canvas id="chart-damaged" style="width:100%;height:100%;"></canvas>
                </div>
                <div id="an-damaged-container" class="table-responsive" style="margin-bottom:24px;">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 8px;">Inventory Movement by Department</p>
                <div id="an-deptusage-container" class="table-responsive">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

            </div>

            <!-- ─── TAB 3: Semester Comparison ───────────────────────────── -->
            <div id="an-tab3" class="an-tab-content" style="display:none;">

                <!-- Year selector -->
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
                    <label style="font-size:12px;color:var(--muted-text);margin:0;white-space:nowrap;">Academic Year</label>
                    <select id="an-sem-year" class="form-control" style="width:130px;font-size:13px;border-radius:8px;border:0.5px solid var(--border);padding:4px 8px;">
                        <!-- Populated by JS -->
                    </select>
                    <button type="button" id="an-sem-apply" class="btn" style="background:#185FA5;color:#fff;border:none;font-size:13px;border-radius:8px;padding:6px 14px;cursor:pointer;">Apply</button>
                    <span id="an-sem-year-label" style="font-size:13px;color:var(--muted-text);"></span>
                </div>

                <!-- Summary cards: 4 metrics, each showing S1 vs S2 -->
                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Semester Summary</p>
                <div id="an-sem-cards" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;">
                    <div class="ui-empty-state" style="grid-column:span 4;"><strong>Loading…</strong></div>
                </div>

                <!-- Semester grouped bar chart -->
                <div style="background:var(--card-color);border:0.5px solid var(--border);border-radius:12px;padding:1.25rem;margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
                        <div>
                            <p style="font-size:14px;font-weight:500;color:var(--text-light);margin:0 0 3px;">Semester Overview Chart</p>
                            <p style="font-size:12px;color:var(--muted-text);margin:0;">1st Sem (Jan–Jun) vs 2nd Sem (Jul–Dec)</p>
                        </div>
                        <div style="display:flex;gap:14px;align-items:center;flex-shrink:0;padding-top:2px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted-text);">
                                <span style="width:8px;height:8px;border-radius:2px;background:#185FA5;display:inline-block;flex-shrink:0;"></span>
                                1st Semester
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted-text);">
                                <span style="width:8px;height:8px;border-radius:2px;background:#1D9E75;display:inline-block;flex-shrink:0;"></span>
                                2nd Semester
                            </span>
                        </div>
                    </div>
                    <div style="height:280px;position:relative;">
                        <canvas id="chart-semester" style="width:100%;height:100%;"></canvas>
                    </div>
                </div>

                <!-- Metric comparison table -->
                <div style="background:var(--card-color);border:0.5px solid var(--border);border-radius:12px;padding:1.25rem;margin-bottom:20px;">
                    <p style="font-size:14px;font-weight:500;margin:0 0 2px;">Metric Comparison</p>
                    <p style="font-size:12px;color:var(--muted-text);margin:0 0 14px;">Compares 1st Semester (Jan–Jun) vs 2nd Semester (Jul–Dec). Trend shows change from S1 → S2.</p>
                    <div id="an-sem-table" class="table-responsive">
                        <div class="ui-empty-state"><strong>Loading…</strong></div>
                    </div>
                </div>

                <!-- Department breakdown -->
                <div style="background:var(--card-color);border:0.5px solid var(--border);border-radius:12px;padding:1.25rem;">
                    <p style="font-size:14px;font-weight:500;margin:0 0 2px;">Department Activity</p>
                    <p style="font-size:12px;color:var(--muted-text);margin:0 0 14px;">Damage reports and dispatches per department, split by semester. Top 20 by total activity.</p>
                    <div id="an-sem-dept" class="table-responsive">
                        <div class="ui-empty-state"><strong>Loading…</strong></div>
                    </div>
                </div>

            </div>

            <!-- ─── TAB 4: Dispatch & Repair Reports ─────────────────────── -->
            <div id="an-tab4" class="an-tab-content" style="display:none;">

                <div class="an-filter-row"
                     style="display:grid;grid-template-columns:160px 160px auto 1fr;gap:10px;margin-bottom:16px;align-items:end;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date From</label>
                        <input type="date" id="an-rpt-from" class="form-control">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date To</label>
                        <input type="date" id="an-rpt-to" class="form-control">
                    </div>
                    <div>
                        <button type="button" id="an-rpt-apply" class="btn btn-primary">Apply</button>
                    </div>
                    <div></div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 8px;">Inventory Activity — Last 12 Months</p>
                <div style="height:280px;margin-bottom:24px;position:relative;">
                    <canvas id="chart-monthly" style="width:100%;height:100%;"></canvas>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 8px;">Dispatch Report</p>
                <div id="an-dispatch-container" class="table-responsive" style="margin-bottom:24px;">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 8px;">Repair Report</p>
                <div id="an-repair-container" class="table-responsive">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

            </div>

        </div><!-- /.card-body -->
    </div><!-- /.card -->
</main>

<style>
/* ── Tab row hover ───────────────────────────────────────────────────── */
.an-tab-content .table tbody tr { transition: background 0.12s; }
.an-tab-content .table tbody tr:hover { background: var(--muted-card); }
/* ── No border on last table row ─────────────────────────────────────── */
#an-sem-table .table tbody tr:last-child td,
#an-sem-dept  .table tbody tr:last-child td  { border-bottom: none; }
</style>

<script>
const AN_API = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/analytics')
    : '/api/analytics';

const anLoadedTabs = new Set();

// Chart instances — destroyed and recreated on each data reload
let anChartDamaged  = null;
let anChartSemester = null;
let anChartMonthly  = null;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function anEsc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function anFetch(url, options) {
    const fetcher = window.Components && typeof Components.fetchJson === 'function'
        ? Components.fetchJson
        : async (u, o) => { const r = await fetch(u, o); return { response: r, data: await r.json() }; };
    return fetcher(url, options);
}

function anStockBadge(qty, threshold) {
    qty = Number(qty); threshold = Number(threshold);
    if (qty <= 0)
        return '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Out of Stock</span>';
    if (qty <= threshold)
        return '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Low Stock</span>';
    return '<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Available</span>';
}

function anRepairBadge(status) {
    const map = {
        pending:     'background:#fef3c7;color:#92400e',
        assigned:    'background:#dbeafe;color:#1e40af',
        in_progress: 'background:#ffedd5;color:#9a3412',
        completed:   'background:#d1fae5;color:#065f46',
        archived:    'background:#f3f4f6;color:#374151',
    };
    const style = map[status] || 'background:#f3f4f6;color:#374151';
    const label = String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase());
    return `<span style="${style};padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">${anEsc(label)}</span>`;
}

// ---------------------------------------------------------------------------
// Tab switching (lazy-loads each tab on first activation)
// ---------------------------------------------------------------------------

function anSwitchTab(tabId) {
    document.querySelectorAll('.an-tab-content').forEach((el) => { el.style.display = 'none'; });
    document.getElementById(tabId).style.display = 'block';

    document.querySelectorAll('.an-tab-btn').forEach((btn) => {
        const active = btn.dataset.tab === tabId;
        btn.style.borderBottom = active ? '2px solid #185FA5' : '2px solid transparent';
        btn.style.color        = active ? '#185FA5' : 'var(--muted-text)';
        btn.style.fontWeight   = active ? '500'     : '400';
    });

    if (!anLoadedTabs.has(tabId)) {
        anLoadedTabs.add(tabId);
        switch (tabId) {
            case 'an-tab1': anLoadHealth(); anLoadLowStock(); break;
            case 'an-tab2': anLoadTab2Data(); break;
            case 'an-tab3': anLoadSemesterDetail(); break;
            case 'an-tab4': anLoadTab4Data(); break;
        }
    }
}

// ---------------------------------------------------------------------------
// TAB 1: Overview
// ---------------------------------------------------------------------------

async function anLoadHealth() {
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/inventory-health`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const d = payload.data;
        document.getElementById('an-h-total').textContent      = d.total_items        ?? '—';
        document.getElementById('an-h-lowstock').textContent   = d.low_stock_count    ?? '—';
        document.getElementById('an-h-outofstock').textContent = d.out_of_stock_count ?? '—';
        document.getElementById('an-h-pct').textContent =
            (d.low_stock_percent != null) ? d.low_stock_percent + '%' : '—';
    } catch (_) {
        // Cards stay as '—' — non-fatal; low-stock table will show its own error
    }
}

async function anLoadLowStock() {
    const container = document.getElementById('an-lowstock-container');
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/low-stock`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const items = Array.isArray(payload.data?.items) ? payload.data.items : [];

        if (items.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No low-stock items.</strong><span>All items are at healthy stock levels.</span></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Item Name</th><th>Quantity</th><th>Threshold</th><th>Status</th>'
            + '</tr></thead><tbody>';
        items.forEach((item) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(item.name)}</strong></td>`;
            html += `<td>${anEsc(item.quantity)}</td>`;
            html += `<td>${anEsc(item.threshold)}</td>`;
            html += `<td>${anStockBadge(item.quantity, item.threshold)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load low-stock data.</strong></div>';
    }
}

// ---------------------------------------------------------------------------
// Options — populates Tab 2 department dropdown (called once at init)
// ---------------------------------------------------------------------------

async function anLoadOptions() {
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/options`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) return;
        const departments = Array.isArray(payload.data?.departments) ? payload.data.departments : [];
        const sel = document.getElementById('an-dept-filter');
        departments.forEach((dept) => {
            const opt = document.createElement('option');
            opt.value       = dept.department_id;
            opt.textContent = dept.name;
            sel.appendChild(opt);
        });
    } catch (_) {
        // Fail silently — dropdown stays at "All Departments"
    }
}

// ---------------------------------------------------------------------------
// TAB 2: Damage Analytics
// ---------------------------------------------------------------------------

function anLoadTab2Data() {
    anLoadDamagedItems();
    anLoadDeptUsage();
}

async function anLoadDamagedItems() {
    const container = document.getElementById('an-damaged-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const deptId = document.getElementById('an-dept-filter').value;
        const from   = document.getElementById('an-dmg-from').value;
        const to     = document.getElementById('an-dmg-to').value;
        if (deptId) params.set('department_id', deptId);
        if (from)   params.set('date_from', from);
        if (to)     params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/damaged-items?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.most_damaged) ? payload.data.most_damaged : [];

        // ── Horizontal bar chart — top 10, reversed so highest bar is at top ──
        if (anChartDamaged) { anChartDamaged.destroy(); anChartDamaged = null; }
        const top10 = rows.slice(0, 10).slice().reverse();
        const cvsDmg = document.getElementById('chart-damaged');
        if (cvsDmg && top10.length > 0) {
            anChartDamaged = new Chart(cvsDmg, {
                type: 'bar',
                data: {
                    labels:   top10.map((r) => r.name),
                    datasets: [{
                        data:            top10.map((r) => Number(r.damage_count)),
                        backgroundColor: 'rgba(239,68,68,0.72)',
                        borderRadius:    6,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        y: { ticks: { color: '#b8aacc' } },
                        x: { grid:  { color: 'rgba(168,139,250,0.1)' }, ticks: { color: '#b8aacc' } },
                    },
                },
            });
        }

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No damage records found.</strong><span>Try adjusting the filters.</span></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Item Name</th><th>Damage Count</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.name)}</strong></td>`;
            html += `<td>${anEsc(row.damage_count)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load damage data.</strong></div>';
    }
}

async function anLoadDeptUsage() {
    const container = document.getElementById('an-deptusage-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const from = document.getElementById('an-dmg-from').value;
        const to   = document.getElementById('an-dmg-to').value;
        if (from) params.set('date_from', from);
        if (to)   params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/department-usage?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.department_usage) ? payload.data.department_usage : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No department usage data found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Department</th><th>Total Inventory Moved</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.department_name || '—')}</strong></td>`;
            html += `<td>${anEsc(row.total_moved)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load department usage data.</strong></div>';
    }
}

// ---------------------------------------------------------------------------
// TAB 3: Semester Detail
// ---------------------------------------------------------------------------

let anSemYear = new Date().getFullYear();

async function anLoadSemesterDetail() {
    const errHtml = (msg) => `<div class="ui-empty-state"><strong>${anEsc(msg)}</strong></div>`;

    document.getElementById('an-sem-cards').innerHTML =
        '<div class="ui-empty-state" style="grid-column:span 4;"><strong>Loading…</strong></div>';
    document.getElementById('an-sem-table').innerHTML = errHtml('Loading…');
    document.getElementById('an-sem-dept').innerHTML  = errHtml('Loading…');

    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/semester-detail?year=${anSemYear}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');

        const d  = payload.data;
        const s1 = d.semesters?.s1 || {};
        const s2 = d.semesters?.s2 || {};

        // Update year label
        const lbl = document.getElementById('an-sem-year-label');
        if (lbl) lbl.textContent = `Showing data for ${anSemYear}`;

        const metrics = [
            { key: 'maintenance_reports', label: 'Maintenance Reports' },
            { key: 'dispatches',          label: 'Dispatches'          },
            { key: 'damage_reports',      label: 'Damage Reports'      },
            { key: 'repairs',             label: 'Repair Requests'     },
        ];

        // ── Summary cards ──────────────────────────────────────────────────
        let cardsHtml = '';
        metrics.forEach((m) => {
            const v1         = s1[m.key] ?? 0;
            const v2         = s2[m.key] ?? 0;
            const total      = v1 + v2;
            const arrowColor = v2 > v1 ? '#1D9E75' : v2 < v1 ? '#A32D2D' : 'var(--muted-text)';
            const arrow      = v2 > v1 ? '↑' : v2 < v1 ? '↓' : '—';
            cardsHtml += `
                <div style="background:var(--bg-color);border:0.5px solid var(--border);border-radius:12px;padding:1rem 1.1rem;">
                    <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:12px;">${anEsc(m.label)}</div>
                    <div style="display:flex;justify-content:space-around;align-items:center;margin-bottom:10px;">
                        <div style="text-align:center;">
                            <div style="font-size:28px;font-weight:500;color:#185FA5;line-height:1;">${v1}</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">1st Sem</div>
                        </div>
                        <div style="font-size:20px;color:${arrowColor};font-weight:500;line-height:1;">${arrow}</div>
                        <div style="text-align:center;">
                            <div style="font-size:22px;font-weight:500;color:var(--muted-text);line-height:1;">${v2}</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">2nd Sem</div>
                        </div>
                    </div>
                    <div style="text-align:center;font-size:12px;color:var(--muted-text);border-top:1px solid var(--border);padding-top:8px;">
                        Year Total: <strong style="color:var(--text-light);">${total}</strong>
                    </div>
                </div>`;
        });
        document.getElementById('an-sem-cards').innerHTML = cardsHtml;

        // ── Alternating grouped bar chart (S1 blue, S2 green) ─────────────
        if (anChartSemester) { anChartSemester.destroy(); anChartSemester = null; }
        const cvsSem = document.getElementById('chart-semester');
        if (cvsSem) {
            const C_S1 = '#185FA5';
            const C_S2 = '#1D9E75';
            anChartSemester = new Chart(cvsSem, {
                type: 'bar',
                data: {
                    labels: ['Maint S1', 'Maint S2', 'Dispatch S1', 'Dispatch S2', 'Damage S1', 'Damage S2', 'Repair S1', 'Repair S2'],
                    datasets: [{
                        data: [
                            s1.maintenance_reports ?? 0, s2.maintenance_reports ?? 0,
                            s1.dispatches          ?? 0, s2.dispatches          ?? 0,
                            s1.damage_reports      ?? 0, s2.damage_reports      ?? 0,
                            s1.repairs             ?? 0, s2.repairs             ?? 0,
                        ],
                        backgroundColor: [C_S1, C_S2, C_S1, C_S2, C_S1, C_S2, C_S1, C_S2],
                        borderRadius: 4,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: 'rgba(24,95,165,0.08)' }, ticks: { color: '#9ca3af' } },
                        x: { ticks: { color: '#9ca3af' } },
                    },
                },
            });
        }

        // ── Metric comparison table ────────────────────────────────────────
        const _metricIcons = {
            maintenance_reports: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:5px;"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
            dispatches:          '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:5px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
            damage_reports:      '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:5px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            repairs:             '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:5px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        };
        const _thS = 'font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted-text);padding-bottom:8px;border-bottom:0.5px solid var(--border);';
        let tableHtml = '<table class="table" style="width:100%;"><thead><tr>'
            + `<th style="${_thS}">Metric</th>`
            + `<th style="${_thS}text-align:center;">1st Sem<br><span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted-text);">Jan – Jun</span></th>`
            + `<th style="${_thS}text-align:center;">2nd Sem<br><span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted-text);">Jul – Dec</span></th>`
            + `<th style="${_thS}text-align:center;">Change</th>`
            + `<th style="${_thS}text-align:center;">Trend</th>`
            + '</tr></thead><tbody>';

        metrics.forEach((m) => {
            const v1        = s1[m.key] ?? 0;
            const v2        = s2[m.key] ?? 0;
            const diff      = v2 - v1;
            const diffStr   = diff === 0
                ? '<span style="color:var(--muted-text);">—</span>'
                : diff > 0
                    ? `<span style="color:#1D9E75;font-weight:500;">+${diff}</span>`
                    : `<span style="color:#A32D2D;font-weight:500;">${diff}</span>`;
            const trendIcon = diff > 0
                ? '<span style="color:#1D9E75;display:inline-flex;align-items:center;" title="Increase in 2nd semester"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg></span>'
                : diff < 0
                    ? '<span style="color:#A32D2D;display:inline-flex;align-items:center;" title="Decrease in 2nd semester"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>'
                    : '<span style="color:var(--muted-text);" title="No change">—</span>';

            tableHtml += `<tr>
                <td>${_metricIcons[m.key] || ''}<strong style="font-weight:500;">${anEsc(m.label)}</strong></td>
                <td style="text-align:center;">${v1}</td>
                <td style="text-align:center;">${v2}</td>
                <td style="text-align:center;">${diffStr}</td>
                <td style="text-align:center;">${trendIcon}</td>
            </tr>`;
        });
        tableHtml += '</tbody></table>';
        document.getElementById('an-sem-table').innerHTML = tableHtml;

        // ── Department breakdown ───────────────────────────────────────────
        const depts = Array.isArray(d.department_breakdown) ? d.department_breakdown : [];
        if (depts.length === 0) {
            document.getElementById('an-sem-dept').innerHTML =
                '<div class="ui-empty-state"><strong>No department data for this year.</strong></div>';
        } else {
            const _thD = 'font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted-text);padding-bottom:8px;border-bottom:0.5px solid var(--border);';
            let deptHtml = '<table class="table" style="width:100%;"><thead><tr>'
                + `<th style="${_thD}">Department</th>`
                + `<th style="${_thD}text-align:center;">S1 Damage</th>`
                + `<th style="${_thD}text-align:center;">S2 Damage</th>`
                + `<th style="${_thD}text-align:center;">S1 Dispatches</th>`
                + `<th style="${_thD}text-align:center;">S2 Dispatches</th>`
                + `<th style="${_thD}text-align:center;">Total Activity</th>`
                + '</tr></thead><tbody>';

            const _numPill = (n) => `<span style="background:#E6F1FB;color:#0C447C;border-radius:99px;padding:2px 9px;font-size:11px;font-weight:500;">${n}</span>`;
            depts.forEach((dept) => {
                const total   = (dept.s1_damage || 0) + (dept.s2_damage || 0)
                              + (dept.s1_dispatches || 0) + (dept.s2_dispatches || 0);
                const _dName  = dept.department_name;
                const _isNone = !_dName || String(_dName).toLowerCase() === 'no department';
                const _dCell  = _isNone
                    ? `<td><em style="color:var(--muted-text);">${anEsc(_dName || 'No department')}</em></td>`
                    : `<td><strong style="font-weight:500;">${anEsc(_dName)}</strong></td>`;
                deptHtml += `<tr>
                    ${_dCell}
                    <td style="text-align:center;">${_numPill(dept.s1_damage     || 0)}</td>
                    <td style="text-align:center;">${_numPill(dept.s2_damage     || 0)}</td>
                    <td style="text-align:center;">${_numPill(dept.s1_dispatches || 0)}</td>
                    <td style="text-align:center;">${_numPill(dept.s2_dispatches || 0)}</td>
                    <td style="text-align:center;">${_numPill(total)}</td>
                </tr>`;
            });
            deptHtml += '</tbody></table>';
            document.getElementById('an-sem-dept').innerHTML = deptHtml;
        }

    } catch (err) {
        const html = `<div class="ui-empty-state"><strong>Failed to load semester data.</strong></div>`;
        document.getElementById('an-sem-cards').innerHTML =
            `<div class="ui-empty-state" style="grid-column:span 4;"><strong>${anEsc(err.message || 'Error loading data')}</strong></div>`;
        document.getElementById('an-sem-table').innerHTML = html;
        document.getElementById('an-sem-dept').innerHTML  = html;
    }
}

// ---------------------------------------------------------------------------
// TAB 4: Dispatch & Repair Reports
// ---------------------------------------------------------------------------

async function anLoadMonthlyChart() {
    if (anChartMonthly) { anChartMonthly.destroy(); anChartMonthly = null; }
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/monthly-comparison`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) return;

        const months = Array.isArray(payload.data?.last_12_months) ? payload.data.last_12_months : [];
        if (!months.length) return;

        const MONTH_ABB = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const labels = months.map((m) => {
            const parts = String(m.ym || '').split('-');
            const abbr  = MONTH_ABB[parseInt(parts[1], 10) - 1] || parts[1];
            return `${abbr} '${String(parts[0] || '').slice(2)}`;
        });
        const values = months.map((m) => Number(m.val) || 0);

        const cvsMon = document.getElementById('chart-monthly');
        if (!cvsMon) return;

        anChartMonthly = new Chart(cvsMon, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data:            values,
                    backgroundColor: 'rgba(139,92,246,0.72)',
                    borderRadius:    6,
                }],
            },
            options: {
                scales: {
                    y: { grid:  { color: 'rgba(168,139,250,0.1)' }, ticks: { color: '#b8aacc' } },
                    x: { ticks: { color: '#b8aacc' } },
                },
            },
        });
    } catch (_) {
        // fail silently — chart is decorative
    }
}

function anLoadTab4Data() {
    anLoadMonthlyChart();
    anLoadDispatchReport();
    anLoadRepairReport();
}

async function anLoadDispatchReport() {
    const container = document.getElementById('an-dispatch-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const from = document.getElementById('an-rpt-from').value;
        const to   = document.getElementById('an-rpt-to').value;
        if (from) params.set('date_from', from);
        if (to)   params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/dispatch-report?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.dispatches) ? payload.data.dispatches : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No dispatch records found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Dispatch Code</th><th>Items Count</th><th>Total Quantity</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.dispatch_code)}</strong></td>`;
            html += `<td>${anEsc(row.items_count)}</td>`;
            html += `<td>${anEsc(row.total_quantity)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load dispatch report.</strong></div>';
    }
}

async function anLoadRepairReport() {
    const container = document.getElementById('an-repair-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const from = document.getElementById('an-rpt-from').value;
        const to   = document.getElementById('an-rpt-to').value;
        if (from) params.set('date_from', from);
        if (to)   params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/repair-report?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.repairs) ? payload.data.repairs : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No repair records found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Repair Code</th><th>Status</th><th>Count</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.repair_code)}</strong></td>`;
            html += `<td>${anRepairBadge(row.repair_status)}</td>`;
            html += `<td>${anEsc(row.count)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load repair report.</strong></div>';
    }
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    // Default dates: start of current year → today
    const today     = new Date();
    const yearStart = today.getFullYear() + '-01-01';
    const todayStr  = today.toISOString().slice(0, 10);
    document.getElementById('an-dmg-from').value = yearStart;
    document.getElementById('an-dmg-to').value   = todayStr;
    document.getElementById('an-rpt-from').value = yearStart;
    document.getElementById('an-rpt-to').value   = todayStr;

    // Tab buttons
    document.querySelectorAll('.an-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => anSwitchTab(btn.dataset.tab));
    });

    // Populate year dropdown (current year → 4 years back)
    const _curYear = new Date().getFullYear();
    anSemYear = _curYear;
    const _yearSel = document.getElementById('an-sem-year');
    for (let y = _curYear; y >= _curYear - 3; y--) {
        const opt = document.createElement('option');
        opt.value       = y;
        opt.textContent = y;
        if (y === _curYear) opt.selected = true;
        _yearSel.appendChild(opt);
    }

    // Apply buttons
    document.getElementById('an-dmg-apply').addEventListener('click', anLoadTab2Data);
    document.getElementById('an-rpt-apply').addEventListener('click', anLoadTab4Data);
    document.getElementById('an-sem-apply').addEventListener('click', () => {
        anSemYear = parseInt(document.getElementById('an-sem-year').value, 10) || new Date().getFullYear();
        anLoadSemesterDetail();
    });

    // Print button
    document.getElementById('an-print-btn').addEventListener('click', () => window.print());

    // Preload department dropdown (non-blocking; Tab 2 may not be visited)
    anLoadOptions();

    // Activate first tab
    anSwitchTab('an-tab1');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
