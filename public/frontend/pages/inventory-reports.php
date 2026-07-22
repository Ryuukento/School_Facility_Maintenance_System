<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$_irUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_irRole = strtolower(trim((string)($_irUser['role'] ?? '')));


$pageTitle = 'Inventory Reports - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<style media="print">
    aside, nav, .sidebar, [class*="sidebar"], header { display: none !important; }
    #ir-menu, #ir-room-list, .ir-print-btn           { display: none !important; }
    main, .container                                 { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .card                                            { box-shadow: none !important; border: none !important; }
    .card-body                                       { padding: 8px !important; }
    .ir-section-inner                                { grid-template-columns: 1fr !important; }
</style>

<main class="container" style="margin-top:16px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2>Inventory Reports</h2>
                <p class="text-muted mb-0">Per room reports, general inventory summary, and semestral/yearly reports.</p>
            </div>
        </div>

        <!-- Two-panel layout: left section menu + right content -->
        <div style="display:flex;min-height:520px;">

            <!-- ── Left section menu ───────────────────────────────────── -->
            <div id="ir-menu"
                 style="width:200px;flex-shrink:0;border-right:1px solid var(--border-color,#e5e7eb);padding:12px 0;">
                <button type="button" class="ir-menu-btn" data-section="ir-s1"
                        style="display:block;width:100%;text-align:left;background:transparent;border:none;
                               padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:#374151;">
                    Per Room Report
                </button>
                <button type="button" class="ir-menu-btn" data-section="ir-s2"
                        style="display:block;width:100%;text-align:left;background:transparent;border:none;
                               padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:#374151;">
                    General Inventory
                </button>
                <button type="button" class="ir-menu-btn" data-section="ir-s3"
                        style="display:block;width:100%;text-align:left;background:transparent;border:none;
                               padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:#374151;">
                    Semestral / Yearly
                </button>
            </div>

            <!-- ── Right content panels ───────────────────────────────── -->
            <div style="flex:1;overflow:auto;">

                <!-- ══════════════════════════════════════════════════════ -->
                <!-- SECTION 1 — Per Room Report                           -->
                <!-- ══════════════════════════════════════════════════════ -->
                <div id="ir-s1" class="ir-section">
                    <div class="ir-section-inner"
                         style="display:grid;grid-template-columns:240px 1fr;height:100%;min-height:520px;">

                        <!-- Room list (left sub-panel) -->
                        <div id="ir-room-list"
                             style="border-right:1px solid var(--border-color,#e5e7eb);overflow-y:auto;max-height:640px;">
                            <div style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;
                                        letter-spacing:.06em;color:#6b7280;border-bottom:1px solid var(--border-color,#e5e7eb);">
                                Rooms
                            </div>
                            <div id="ir-room-list-inner">
                                <div style="padding:16px;color:#6b7280;font-size:13px;">Loading rooms…</div>
                            </div>
                        </div>

                        <!-- Room detail (right sub-panel) -->
                        <div style="padding:20px;">
                            <div id="ir-room-detail-header" style="margin-bottom:12px;">
                                <p class="text-muted" style="margin:0;font-size:14px;">
                                    ← Select a room from the list to view its inventory.
                                </p>
                            </div>
                            <div id="ir-room-detail-actions" style="display:none;margin-bottom:14px;">
                                <button type="button" id="ir-print-room-btn" class="btn btn-secondary ir-print-btn">
                                    Print This Room Report
                                </button>
                            </div>
                            <div id="ir-room-items-container" class="table-responsive"></div>
                        </div>

                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════ -->
                <!-- SECTION 2 — General Inventory Report                  -->
                <!-- ══════════════════════════════════════════════════════ -->
                <div id="ir-s2" class="ir-section" style="display:none;padding:20px;">

                    <!-- Summary cards -->
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;">
                        <div style="border:1px solid var(--border-color,#e5e7eb);border-radius:8px;padding:16px;text-align:center;">
                            <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Items in Bodega</div>
                            <div id="ir-gen-bodega" style="font-size:28px;font-weight:700;color:#111827;">—</div>
                        </div>
                        <div style="border:1px solid var(--border-color,#e5e7eb);border-radius:8px;padding:16px;text-align:center;">
                            <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Items in Rooms</div>
                            <div id="ir-gen-rooms" style="font-size:28px;font-weight:700;color:#111827;">—</div>
                        </div>
                        <div style="border:1px solid var(--border-color,#e5e7eb);border-radius:8px;padding:16px;text-align:center;">
                            <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Total Items Overall</div>
                            <div id="ir-gen-total" style="font-size:28px;font-weight:700;color:#111827;">—</div>
                        </div>
                        <div style="border:1px solid var(--border-color,#e5e7eb);border-radius:8px;padding:16px;text-align:center;">
                            <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Low Stock Count</div>
                            <div id="ir-gen-lowstock" style="font-size:28px;font-weight:700;color:#92400e;">—</div>
                        </div>
                    </div>

                    <div class="d-flex justify-between align-center" style="margin-bottom:8px;">
                        <p style="font-size:15px;font-weight:600;margin:0;">Bodega / Stockrooms</p>
                        <div class="d-flex gap-sm">
                            <button type="button" id="ir-export-csv-btn" class="btn btn-secondary">Export to CSV</button>
                            <button type="button" id="ir-print-gen-btn" class="btn btn-secondary ir-print-btn">Print General Report</button>
                        </div>
                    </div>
                    <div id="ir-gen-bodega-table" class="table-responsive" style="margin-bottom:24px;">
                        <div class="ui-empty-state"><strong>Loading…</strong></div>
                    </div>

                    <p style="font-size:15px;font-weight:600;margin:0 0 8px;">All Rooms</p>
                    <div id="ir-gen-rooms-table" class="table-responsive">
                        <div class="ui-empty-state"><strong>Loading…</strong></div>
                    </div>

                </div>

                <!-- ══════════════════════════════════════════════════════ -->
                <!-- SECTION 3 — Semestral / Yearly Report                 -->
                <!-- ══════════════════════════════════════════════════════ -->
                <div id="ir-s3" class="ir-section" style="display:none;padding:20px;">

                    <!-- Filters -->
                    <div style="display:grid;grid-template-columns:120px 200px auto 1fr;gap:10px;margin-bottom:20px;align-items:end;">
                        <div>
                            <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Year</label>
                            <input type="number" id="ir-sem-year" class="form-control"
                                   min="2000" max="2099" style="width:100%;">
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Period</label>
                            <select id="ir-sem-period" class="form-control">
                                <option value="1">1st Semester (Jan – Jun)</option>
                                <option value="2">2nd Semester (Jul – Dec)</option>
                                <option value="full">Full Year</option>
                            </select>
                        </div>
                        <div>
                            <button type="button" id="ir-sem-generate" class="btn btn-primary">Generate Report</button>
                        </div>
                        <div></div>
                    </div>

                    <!-- Report output -->
                    <div id="ir-sem-output" style="display:none;">

                        <div class="d-flex justify-between align-center" style="margin-bottom:16px;">
                            <p id="ir-sem-period-label" style="font-size:15px;font-weight:600;margin:0;color:#374151;"></p>
                            <button type="button" id="ir-print-sem-btn" class="btn btn-secondary ir-print-btn">Print Semestral Report</button>
                        </div>

                        <!-- Report A: Items Received -->
                        <p style="font-size:14px;font-weight:600;margin:0 0 6px;">A — Items Received</p>
                        <div id="ir-sem-receipts" class="table-responsive" style="margin-bottom:20px;">
                            <div class="ui-empty-state"><strong>Loading…</strong></div>
                        </div>

                        <!-- Report B: Items Dispatched -->
                        <p style="font-size:14px;font-weight:600;margin:0 0 6px;">B — Items Dispatched</p>
                        <div id="ir-sem-dispatches" class="table-responsive" style="margin-bottom:20px;">
                            <div class="ui-empty-state"><strong>Loading…</strong></div>
                        </div>

                        <!-- Report C: Damage Reports -->
                        <p style="font-size:14px;font-weight:600;margin:0 0 6px;">C — Damage Reports</p>
                        <div id="ir-sem-damages" class="table-responsive" style="margin-bottom:20px;">
                            <div class="ui-empty-state"><strong>Loading…</strong></div>
                        </div>

                        <!-- Report D: Current Stock Summary -->
                        <p style="font-size:14px;font-weight:600;margin:0 0 6px;">D — Current Stock Summary</p>
                        <div id="ir-sem-stock-cards"
                             style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:12px;">
                            <div style="border:1px solid var(--border-color,#e5e7eb);border-radius:8px;padding:12px;text-align:center;">
                                <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Total Quantity</div>
                                <div id="ir-sem-qty" style="font-size:24px;font-weight:700;">—</div>
                            </div>
                            <div style="border:1px solid var(--border-color,#e5e7eb);border-radius:8px;padding:12px;text-align:center;">
                                <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Distinct Items</div>
                                <div id="ir-sem-distinct" style="font-size:24px;font-weight:700;">—</div>
                            </div>
                            <div style="border:1px solid var(--border-color,#e5e7eb);border-radius:8px;padding:12px;text-align:center;">
                                <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Low Stock Count</div>
                                <div id="ir-sem-ls" style="font-size:24px;font-weight:700;color:#92400e;">—</div>
                            </div>
                        </div>
                        <div id="ir-sem-stock-table" class="table-responsive"></div>

                    </div><!-- /#ir-sem-output -->

                </div>

            </div><!-- /.right content panels -->
        </div><!-- /.two-panel layout -->

    </div><!-- /.card -->
</main>

<script>
// ---------------------------------------------------------------------------
// API base URLs
// ---------------------------------------------------------------------------
const IR_ROOMS_API    = '/api/rooms';

const IR_INV_ROOMS_API = '/api/inventory-rooms';

const IR_ITEMS_API    = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/items')
    : '/api/items';

const IR_HEALTH_API   = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/analytics/inventory-health')
    : '/api/analytics/inventory-health';

const IR_SUMMARY_API  = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/analytics/inventory-summary')
    : '/api/analytics/inventory-summary';

const IR_RECEIPTS_API = window.SFMS_PUBLIC_URL('/api/purchase-receipts');

const IR_DMG_API      = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/damage-reports')
    : '/api/damage-reports';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function irEsc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function irFetch(url, options) {
    const response = await fetch(url, options);
    return { response, data: await response.json() };
}

function irStatusBadge(status) {
    const map = {
        available:    'background:#d1fae5;color:#065f46',
        low_stock:    'background:#fef3c7;color:#92400e',
        out_of_stock: 'background:#fee2e2;color:#991b1b',
        damaged:      'background:#ede9fe;color:#5b21b6',
        for_repair:   'background:#ffedd5;color:#9a3412',
    };
    const style = map[status] || 'background:#f3f4f6;color:#374151';
    const label = String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase());
    return `<span style="${style};padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">${irEsc(label)}</span>`;
}

function irSeverityBadge(severity) {
    const map = {
        low:      'background:#d1fae5;color:#065f46',
        medium:   'background:#fef3c7;color:#92400e',
        high:     'background:#ffedd5;color:#9a3412',
        critical: 'background:#fee2e2;color:#991b1b',
    };
    const style = map[severity] || 'background:#f3f4f6;color:#374151';
    const label = String(severity || '').toUpperCase();
    return `<span style="${style};padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">${irEsc(label)}</span>`;
}

function irDmgStatusBadge(status) {
    const map = {
        pending:      'background:#fef3c7;color:#92400e',
        under_review: 'background:#ffedd5;color:#9a3412',
        repairing:    'background:#dbeafe;color:#1e40af',
        repaired:     'background:#d1fae5;color:#065f46',
        replaced:     'background:#ede9fe;color:#5b21b6',
        closed:       'background:#f3f4f6;color:#374151',
    };
    const style = map[status] || 'background:#f3f4f6;color:#374151';
    const label = String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase());
    return `<span style="${style};padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">${irEsc(label)}</span>`;
}

// ---------------------------------------------------------------------------
// Section switching (lazy-loads each section on first activation)
// ---------------------------------------------------------------------------

const irLoadedSections = new Set();

function irSwitchSection(sectionId) {
    document.querySelectorAll('.ir-section').forEach((el) => { el.style.display = 'none'; });
    document.getElementById(sectionId).style.display = 'block';

    document.querySelectorAll('.ir-menu-btn').forEach((btn) => {
        const active = btn.dataset.section === sectionId;
        btn.style.background  = active ? 'var(--hover-color, rgba(37,99,235,.08))' : 'transparent';
        btn.style.color       = active ? '#2563eb' : '#374151';
        btn.style.fontWeight  = active ? '600'     : '400';
        btn.style.borderLeft  = active ? '3px solid #2563eb' : '3px solid transparent';
    });

    if (!irLoadedSections.has(sectionId)) {
        irLoadedSections.add(sectionId);
        switch (sectionId) {
            case 'ir-s1': irLoadRoomList(); break;
            case 'ir-s2': irLoadGeneralReport(); break;
            case 'ir-s3': /* Section 3 loads on Generate click */ break;
        }
    }
}

// ===========================================================================
// SECTION 1 — Per Room Report
// ===========================================================================

let irActiveRoomId   = null;
let irActiveRoomName = null;

async function irLoadRoomList() {
    const inner = document.getElementById('ir-room-list-inner');
    try {
        const { response, data: payload } = await irFetch(
            `${IR_ROOMS_API}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');

        const rooms = Array.isArray(payload.data?.rooms) ? payload.data.rooms : [];
        if (rooms.length === 0) {
            inner.innerHTML = '<div style="padding:16px;color:#6b7280;font-size:13px;">No rooms found.</div>';
            return;
        }

        // Group rooms by building for readability
        const byBuilding = {};
        rooms.forEach((r) => {
            const bName = r.building_name || 'No Building';
            if (!byBuilding[bName]) byBuilding[bName] = [];
            byBuilding[bName].push(r);
        });

        let html = '';
        Object.keys(byBuilding).sort().forEach((bName) => {
            html += `<div style="padding:6px 14px 2px;font-size:11px;font-weight:700;text-transform:uppercase;
                                 letter-spacing:.06em;color:#9ca3af;">${irEsc(bName)}</div>`;
            byBuilding[bName].forEach((r) => {
                html += `<button type="button" class="ir-room-btn"
                                 data-room-id="${irEsc(r.id)}"
                                 data-room-name="${irEsc(r.name)}"
                                 style="display:block;width:100%;text-align:left;background:transparent;border:none;
                                        border-left:3px solid transparent;padding:8px 14px 8px 11px;
                                        cursor:pointer;font-size:13px;color:#374151;line-height:1.3;">
                            ${irEsc(r.name)}
                         </button>`;
            });
        });
        inner.innerHTML = html;

        // Bind room button clicks
        inner.querySelectorAll('.ir-room-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                irActiveRoomId   = btn.dataset.roomId;
                irActiveRoomName = btn.dataset.roomName;

                // Highlight active room
                inner.querySelectorAll('.ir-room-btn').forEach((b) => {
                    b.style.background   = 'transparent';
                    b.style.borderLeft   = '3px solid transparent';
                    b.style.color        = '#374151';
                    b.style.fontWeight   = '400';
                });
                btn.style.background = 'var(--hover-color, rgba(37,99,235,.08))';
                btn.style.borderLeft = '3px solid #2563eb';
                btn.style.color      = '#2563eb';
                btn.style.fontWeight = '600';

                irLoadRoomItems(irActiveRoomId, irActiveRoomName);
            });
        });

        // Auto-select first room
        const firstBtn = inner.querySelector('.ir-room-btn');
        if (firstBtn) firstBtn.click();

    } catch (err) {
        inner.innerHTML = `<div style="padding:16px;color:#ef4444;font-size:13px;">Failed to load rooms.</div>`;
    }
}

async function irLoadRoomItems(roomId, roomName) {
    const header    = document.getElementById('ir-room-detail-header');
    const actions   = document.getElementById('ir-room-detail-actions');
    const container = document.getElementById('ir-room-items-container');

    header.innerHTML    = `<h3 style="margin:0 0 4px;font-size:16px;font-weight:600;">${irEsc(roomName)}</h3>
                           <p class="text-muted" style="margin:0;font-size:13px;">Per Room Inventory Report</p>`;
    actions.style.display  = 'block';
    container.innerHTML    = '<div class="ui-empty-state"><strong>Loading items…</strong></div>';

    try {
        const { response, data: payload } = await irFetch(
            `${IR_ITEMS_API}?room_id=${encodeURIComponent(roomId)}&per_page=200`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');

        // Defensive shape handling (same pattern as dispatch-create.php)
        const d = payload.data;
        let items = [];
        if (Array.isArray(d?.data))         { items = d.data; }
        else if (Array.isArray(d?.items?.data)) { items = d.items.data; }
        else if (Array.isArray(d?.items))   { items = d.items; }
        else if (Array.isArray(d))          { items = d; }

        header.innerHTML = `<h3 style="margin:0 0 4px;font-size:16px;font-weight:600;">${irEsc(roomName)}</h3>
                            <p class="text-muted" style="margin:0;font-size:13px;">
                                <strong>${items.length}</strong> item${items.length !== 1 ? 's' : ''} in this room
                            </p>`;

        if (items.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No items found in this room.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Item Name</th><th>Category</th><th>Quantity</th><th>Status</th><th>Condition</th>'
            + '</tr></thead><tbody>';
        items.forEach((item) => {
            const catName = item.category?.name ?? item.category_name ?? '—';
            html += '<tr>';
            html += `<td><strong>${irEsc(item.name)}</strong></td>`;
            html += `<td>${irEsc(catName)}</td>`;
            html += `<td>${irEsc(item.quantity ?? '—')}</td>`;
            html += `<td>${irStatusBadge(item.status)}</td>`;
            html += `<td>${irEsc(item.condition || '—')}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;

    } catch (err) {
        container.innerHTML = `<div class="ui-empty-state"><strong>Failed to load items for this room.</strong></div>`;
    }
}

// ===========================================================================
// SECTION 2 — General Inventory Report
// ===========================================================================

async function irLoadGeneralReport() {
    irLoadHealthCards();
    irLoadBodegaTable();
    irLoadRoomsTable();
}

async function irLoadHealthCards() {
    try {
        const { response, data: payload } = await irFetch(
            IR_HEALTH_API,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const d = payload.data;

        // Derive bodega vs room totals from inventory-summary (two calls)
        const bRes = await irFetch(
            `${IR_ITEMS_API}?per_page=1&item_type=inventory_stock`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        const rRes = await irFetch(
            `${IR_ITEMS_API}?per_page=1&item_type=room_asset`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );

        const bTotal = bRes.data?.data?.total ?? bRes.data?.data?.meta?.total
            ?? bRes.data?.meta?.total ?? '—';
        const rTotal = rRes.data?.data?.total ?? rRes.data?.data?.meta?.total
            ?? rRes.data?.meta?.total ?? '—';

        document.getElementById('ir-gen-bodega').textContent   = bTotal;
        document.getElementById('ir-gen-rooms').textContent    = rTotal;
        document.getElementById('ir-gen-total').textContent    = d.total_items      ?? '—';
        document.getElementById('ir-gen-lowstock').textContent = d.low_stock_count  ?? '—';
    } catch (_) {
        // Cards stay as '—' — non-fatal
    }
}

async function irLoadBodegaTable() {
    const container = document.getElementById('ir-gen-bodega-table');
    try {
        const { response, data: payload } = await irFetch(
            `${IR_INV_ROOMS_API}?per_page=100`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rooms = Array.isArray(payload.data?.rooms) ? payload.data.rooms : [];

        if (rooms.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No bodega / stockrooms found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Bodega Name</th><th>Code</th><th>Item Count</th><th>Total Qty</th><th>Status</th>'
            + '</tr></thead><tbody>';
        rooms.forEach((r) => {
            const active = r.is_active ? '<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Active</span>'
                                       : '<span style="background:#f3f4f6;color:#374151;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Inactive</span>';
            html += '<tr>';
            html += `<td><strong>${irEsc(r.name)}</strong></td>`;
            html += `<td>${irEsc(r.code || '—')}</td>`;
            html += `<td>${irEsc(r.item_count)}</td>`;
            html += `<td>${irEsc(r.total_quantity)}</td>`;
            html += `<td>${active}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load bodega data.</strong></div>';
    }
}

async function irLoadRoomsTable() {
    const container = document.getElementById('ir-gen-rooms-table');
    try {
        const { response, data: payload } = await irFetch(
            `${IR_ROOMS_API}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rooms = Array.isArray(payload.data?.rooms) ? payload.data.rooms : [];

        if (rooms.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No rooms found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Room Name</th><th>Building</th><th>Floor</th><th>Capacity</th>'
            + '</tr></thead><tbody>';
        rooms.forEach((r) => {
            html += '<tr>';
            html += `<td><strong>${irEsc(r.name)}</strong></td>`;
            html += `<td>${irEsc(r.building_name || '—')}</td>`;
            html += `<td>${irEsc(r.floor_name || '—')}</td>`;
            html += `<td>${irEsc(r.capacity || '—')}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load rooms data.</strong></div>';
    }
}

// ===========================================================================
// SECTION 3 — Semestral / Yearly Report
// ===========================================================================

function irGetDateRange() {
    const year   = parseInt(document.getElementById('ir-sem-year').value, 10) || new Date().getFullYear();
    const period = document.getElementById('ir-sem-period').value;
    let dateFrom, dateTo, label;
    if (period === '1') {
        dateFrom = `${year}-01-01`;
        dateTo   = `${year}-06-30`;
        label    = `1st Semester ${year} (Jan – Jun)`;
    } else if (period === '2') {
        dateFrom = `${year}-07-01`;
        dateTo   = `${year}-12-31`;
        label    = `2nd Semester ${year} (Jul – Dec)`;
    } else {
        dateFrom = `${year}-01-01`;
        dateTo   = `${year}-12-31`;
        label    = `Full Year ${year}`;
    }
    return { dateFrom, dateTo, label };
}

async function irGenerateSemReport() {
    const { dateFrom, dateTo, label } = irGetDateRange();

    document.getElementById('ir-sem-output').style.display         = 'block';
    document.getElementById('ir-sem-period-label').textContent      = `Report Period: ${label}`;
    document.getElementById('ir-sem-receipts').innerHTML            = '<div class="ui-empty-state"><strong>Loading…</strong></div>';
    document.getElementById('ir-sem-dispatches').innerHTML          = '<div class="ui-empty-state"><strong>Loading…</strong></div>';
    document.getElementById('ir-sem-damages').innerHTML             = '<div class="ui-empty-state"><strong>Loading…</strong></div>';
    document.getElementById('ir-sem-stock-table').innerHTML         = '';
    document.getElementById('ir-sem-qty').textContent      = '—';
    document.getElementById('ir-sem-distinct').textContent = '—';
    document.getElementById('ir-sem-ls').textContent       = '—';

    // All 4 report sections fire in parallel
    irLoadSemReceipts(dateFrom, dateTo);
    irLoadSemDispatches(dateFrom, dateTo);
    irLoadSemDamages(dateFrom, dateTo);
    irLoadSemStockSummary();
}

async function irLoadSemReceipts(dateFrom, dateTo) {
    const container = document.getElementById('ir-sem-receipts');
    try {
        const { response, data: payload } = await irFetch(
            IR_RECEIPTS_API,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.receipts) ? payload.data.receipts : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No receipts in this period.</strong></div>';
            return;
        }
        let html = '<table class="table"><thead><tr>'
            + '<th>OR Number</th><th>Date</th><th>Supplier</th><th>Items</th><th>Received By</th>'
            + '</tr></thead><tbody>';
        rows.forEach((r) => {
            const d = r.receipt_date ? new Date(r.receipt_date).toLocaleDateString() : '—';
            html += '<tr>';
            html += `<td><strong>${irEsc(r.or_number)}</strong></td>`;
            html += `<td>${irEsc(d)}</td>`;
            html += `<td>${irEsc(r.supplier_name || '—')}</td>`;
            html += `<td>${irEsc(r.item_count ?? '—')}</td>`;
            html += `<td>${irEsc(r.received_by_name || '—')}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load receipts.</strong></div>';
    }
}

async function irLoadSemDispatches(dateFrom, dateTo) {
    const container = document.getElementById('ir-sem-dispatches');
    try {
        const res = await fetch(
            `/api/dispatches?per_page=200&date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
        );
        const payload = await res.json();
        if (!res.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.data) ? payload.data.data : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No dispatches found.</strong></div>';
            return;
        }
        let html = '<table class="table"><thead><tr>'
            + '<th>Dispatch Code</th><th>Department</th><th>Room</th><th>Items</th><th>Date</th>'
            + '</tr></thead><tbody>';
        rows.forEach((r) => {
            const d = r.created_at ? new Date(r.created_at).toLocaleDateString() : '—';
            html += '<tr>';
            html += `<td><strong>${irEsc(r.dispatch_code)}</strong></td>`;
            html += `<td>${irEsc(r.department_name || '—')}</td>`;
            html += `<td>${irEsc(r.room_name || '—')}</td>`;
            html += `<td>${irEsc(r.item_count ?? '—')}</td>`;
            html += `<td>${irEsc(d)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load dispatches.</strong></div>';
    }
}

async function irLoadSemDamages(dateFrom, dateTo) {
    const container = document.getElementById('ir-sem-damages');
    try {
        const params = new URLSearchParams({ per_page: '200', date_from: dateFrom, date_to: dateTo });
        const { response, data: payload } = await irFetch(
            `${IR_DMG_API}?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const paginator = payload.data?.reports;
        const rows      = Array.isArray(paginator?.data) ? paginator.data : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No damage reports in this period.</strong></div>';
            return;
        }
        let html = '<table class="table"><thead><tr>'
            + '<th>Report Code</th><th>Item</th><th>Room</th><th>Severity</th><th>Status</th><th>Date</th>'
            + '</tr></thead><tbody>';
        rows.forEach((r) => {
            const d = r.created_at ? new Date(r.created_at).toLocaleDateString() : '—';
            html += '<tr>';
            html += `<td><strong>${irEsc(r.damage_report_code)}</strong></td>`;
            html += `<td>${irEsc(r.item?.name || '—')}</td>`;
            html += `<td>${irEsc(r.room?.name || '—')}</td>`;
            html += `<td>${irSeverityBadge(r.severity_level)}</td>`;
            html += `<td>${irDmgStatusBadge(r.status)}</td>`;
            html += `<td>${irEsc(d)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load damage reports.</strong></div>';
    }
}

async function irLoadSemStockSummary() {
    try {
        const { response, data: payload } = await irFetch(
            IR_SUMMARY_API,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const d = payload.data;

        document.getElementById('ir-sem-qty').textContent      = d.total_quantity  ?? '—';
        document.getElementById('ir-sem-distinct').textContent = d.distinct_items  ?? '—';
        document.getElementById('ir-sem-ls').textContent       = d.low_stock_count ?? '—';

        const lowItems = Array.isArray(d.low_stock_items) ? d.low_stock_items : [];
        const tbl = document.getElementById('ir-sem-stock-table');
        if (lowItems.length === 0) {
            tbl.innerHTML = '<div class="ui-empty-state"><strong>No low-stock items.</strong></div>';
            return;
        }
        let html = '<table class="table"><thead><tr>'
            + '<th>Item Name</th><th>Quantity</th><th>Threshold</th>'
            + '</tr></thead><tbody>';
        lowItems.forEach((item) => {
            html += '<tr>';
            html += `<td><strong>${irEsc(item.name)}</strong></td>`;
            html += `<td>${irEsc(item.quantity)}</td>`;
            html += `<td>${irEsc(item.threshold)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        tbl.innerHTML = html;
    } catch (_) {
        // Stock cards stay as '—' — non-fatal
    }
}

// ---------------------------------------------------------------------------
// Export to CSV — Section 2 General Inventory
// ---------------------------------------------------------------------------

function exportInventoryCSV() {
    const rows = [
        ['Bodega Name', 'Code', 'Item Count', 'Total Quantity', 'Status']
    ];
    document.querySelectorAll('#ir-gen-bodega-table tr').forEach((row) => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            rows.push([...cells].map((td) => td.textContent.trim()));
        }
    });
    const csv  = rows.map((r) =>
        r.map((c) => '"' + c.replace(/"/g, '""') + '"').join(',')
    ).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'inventory-report-' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    // Section menu
    document.querySelectorAll('.ir-menu-btn').forEach((btn) => {
        btn.addEventListener('click', () => irSwitchSection(btn.dataset.section));
    });

    // Section 1 print button
    document.getElementById('ir-print-room-btn').addEventListener('click', () => window.print());

    // Section 2 export + print buttons
    document.getElementById('ir-export-csv-btn').addEventListener('click', exportInventoryCSV);
    document.getElementById('ir-print-gen-btn').addEventListener('click', () => window.print());

    // Section 3: year default + generate button
    document.getElementById('ir-sem-year').value = new Date().getFullYear();
    document.getElementById('ir-sem-generate').addEventListener('click', irGenerateSemReport);
    document.getElementById('ir-print-sem-btn').addEventListener('click', () => window.print());

    // Activate Section 1 by default
    irSwitchSection('ir-s1');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
