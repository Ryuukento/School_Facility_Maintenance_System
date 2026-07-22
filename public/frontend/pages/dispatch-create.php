<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$_dcUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_dcRole = strtolower(trim((string)($_dcUser['role'] ?? '')));

if (!in_array($_dcRole, ['super_admin', 'maintenance_admin'], true)) {
    header('Location: ' . public_url('/dispatches'));
    exit;
}

$pageTitle = 'Create Dispatch - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2>Create Dispatch Request</h2>
                <p class="text-muted mb-0">Request items to be moved from bodega stock to a room or lab.</p>
            </div>
            <a href="<?php echo htmlspecialchars(public_url('/dispatches')); ?>" class="btn btn-secondary">← Back to Dispatches</a>
        </div>
        <div class="card-body">
            <form id="dc-form" novalidate>

                <!-- Auto-generated ID -->
                <div class="form-group">
                    <label>Dispatch ID</label>
                    <input type="text" class="form-control" value="Auto-generated upon submit" readonly>
                </div>

                <!-- Department + Room -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label for="dc-dept">Department</label>
                        <select id="dc-dept" class="form-control">
                            <option value="">— Loading departments… —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dc-room">Room / Lab</label>
                        <select id="dc-room" class="form-control">
                            <option value="">— Loading rooms… —</option>
                        </select>
                    </div>
                </div>

                <!-- Source OR Number -->
                <div class="form-group">
                    <label for="dc-or-search">Source OR Number <span class="text-muted" style="font-weight:400;">(optional)</span></label>
                    <input type="text" id="dc-or-search" class="form-control" placeholder="Search by OR number or supplier…">
                    <input type="hidden" id="dc-or-id">
                    <small class="text-muted">Links this dispatch to a purchase receipt for deployment tracking.</small>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="dc-notes">Notes <span class="text-muted" style="font-weight:400;">(optional)</span></label>
                    <textarea id="dc-notes" class="form-control" rows="4" style="resize:vertical;" placeholder="Purpose of dispatch, special instructions, etc."></textarea>
                </div>

                <!-- Items section -->
                <div class="form-group">
                    <div class="d-flex justify-between align-center" style="margin-bottom:10px;">
                        <label style="margin:0;">Items to Dispatch <span style="color:#ef4444;">*</span></label>
                        <button type="button" id="dc-add-row" class="btn btn-secondary">+ Add Item</button>
                    </div>

                    <div id="dc-items-loading" class="text-muted" style="font-size:13px;margin-bottom:8px;">Loading inventory items…</div>
                    <div id="dc-items-rows"></div>
                    <small class="text-muted">At least one item is required. Available stock shown in parentheses.</small>
                </div>

                <!-- Error -->
                <p id="dc-form-error" style="color:#ef4444;margin:0 0 12px;display:none;font-size:14px;"></p>

                <!-- Submit row -->
                <div class="d-flex gap-sm">
                    <button type="submit" id="dc-submit-btn" class="btn btn-primary">Submit Dispatch Request</button>
                    <a href="<?php echo htmlspecialchars(public_url('/dispatches')); ?>" class="btn btn-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</main>

<script>
// NOTE: these are RAW paths — Components.fetchJson / resolveAppUrl will add
// the SFMS_BASE_PATH prefix automatically via SFMS_PUBLIC_URL, so do NOT
// pre-wrap them here (that would cause double-prefix: /base/base/...).
const DC_BACKEND   = '/backend/api';
const DC_ITEMS_API = '/api/items';

const DC_BASE      = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/dispatches')
    : '/dispatches';

let dcItems      = [];
let dcRowCounter = 0;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function dcEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function dcFetch(url, options) {
    const fetcher = window.Components && typeof Components.fetchJson === 'function'
        ? Components.fetchJson
        : async (u, o) => { const r = await fetch(u, o); return { response: r, data: await r.json() }; };
    return fetcher(url, options);
}

function dcShowError(msg) {
    const el = document.getElementById('dc-form-error');
    el.textContent   = msg;
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function dcHideError() {
    const el = document.getElementById('dc-form-error');
    el.style.display = 'none';
    el.textContent   = '';
}

// ---------------------------------------------------------------------------
// Load dropdowns
// ---------------------------------------------------------------------------

async function loadDepartments() {
    try {
        const { response, data: payload } = await dcFetch(
            `/api/departments`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        const sel = document.getElementById('dc-dept');
        if (!response.ok || !payload.success) {
            sel.innerHTML = '<option value="">— No departments available —</option>';
            return;
        }

        const list = Array.isArray(payload.data?.departments)
            ? payload.data.departments
            : (Array.isArray(payload.data) ? payload.data : []);

        sel.innerHTML = '<option value="">— None / Not specified —</option>';
        list.forEach((d) => {
            const opt = document.createElement('option');
            opt.value       = d.department_id;
            opt.textContent = d.name;
            sel.appendChild(opt);
        });
    } catch (_) {
        document.getElementById('dc-dept').innerHTML = '<option value="">— Could not load —</option>';
    }
}

async function loadRooms() {
    try {
        const { response, data: payload } = await dcFetch(
            `/api/rooms`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        const sel = document.getElementById('dc-room');
        if (!response.ok || !payload.success) {
            sel.innerHTML = '<option value="">— No rooms available —</option>';
            return;
        }

        const list = Array.isArray(payload.data?.rooms)
            ? payload.data.rooms
            : (Array.isArray(payload.data) ? payload.data : []);

        sel.innerHTML = '<option value="">— None / Not specified —</option>';
        list.forEach((r) => {
            const opt = document.createElement('option');
            opt.value       = r.id;
            opt.textContent = r.name;
            sel.appendChild(opt);
        });
    } catch (_) {
        document.getElementById('dc-room').innerHTML = '<option value="">— Could not load —</option>';
    }
}

async function loadItems() {
    try {
        const { response, data: payload } = await dcFetch(
            `${DC_ITEMS_API}?per_page=200&item_type=inventory_stock`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );

        if (!response.ok || !payload.success) return;

        // Handle both paginator shapes the Laravel API may return
        const d = payload.data;
        if (Array.isArray(d?.data)) {
            dcItems = d.data;                          // paginator at root
        } else if (Array.isArray(d?.items?.data)) {
            dcItems = d.items.data;                    // named key + paginator
        } else if (Array.isArray(d?.items)) {
            dcItems = d.items;                         // named key, plain array
        } else if (Array.isArray(d)) {
            dcItems = d;                               // plain array
        }
    } catch (_) {
        // fail silently — addItemRow will show empty select
    }
}

// ---------------------------------------------------------------------------
// Dynamic item rows
// ---------------------------------------------------------------------------

function addItemRow() {
    dcRowCounter++;

    const row = document.createElement('div');
    row.className = 'dc-item-row';
    row.style.cssText = 'display:grid;grid-template-columns:1fr 120px auto;gap:8px;margin-bottom:8px;align-items:center;';

    // Item select
    const sel = document.createElement('select');
    sel.className = 'dc-item-select form-control';
    const placeholder = document.createElement('option');
    placeholder.value       = '';
    placeholder.textContent = '— Select item —';
    sel.appendChild(placeholder);

    dcItems.forEach((item) => {
        const opt      = document.createElement('option');
        opt.value      = item.id;
        const avail    = (item.quantity != null)
            ? ` (${item.quantity} in stock)`
            : '';
        opt.textContent = dcEscapeHtml(item.name) + avail;
        sel.appendChild(opt);
    });

    // Quantity input
    const qty = document.createElement('input');
    qty.type      = 'number';
    qty.className = 'dc-qty-input form-control';
    qty.min       = '1';
    qty.value     = '1';
    qty.setAttribute('aria-label', 'Quantity');

    // Remove button
    const rem = document.createElement('button');
    rem.type        = 'button';
    rem.className   = 'btn btn-secondary';
    rem.textContent = 'Remove';
    rem.addEventListener('click', () => {
        if (document.querySelectorAll('.dc-item-row').length <= 1) return;
        row.remove();
    });

    row.appendChild(sel);
    row.appendChild(qty);
    row.appendChild(rem);

    document.getElementById('dc-items-rows').appendChild(row);
}

// ---------------------------------------------------------------------------
// Form submit
// ---------------------------------------------------------------------------

document.getElementById('dc-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    dcHideError();

    const deptId = document.getElementById('dc-dept').value;
    const roomId = document.getElementById('dc-room').value;
    const notes  = document.getElementById('dc-notes').value.trim();

    const rows  = Array.from(document.querySelectorAll('.dc-item-row'));
    const items = rows
        .map((row) => ({
            item_id:  parseInt(row.querySelector('.dc-item-select').value, 10) || 0,
            quantity: parseInt(row.querySelector('.dc-qty-input').value, 10)   || 0,
        }))
        .filter((it) => it.item_id > 0 && it.quantity > 0);

    if (items.length === 0) {
        dcShowError('At least one item must be selected with a quantity of 1 or more.');
        return;
    }

    const orId = parseInt(document.getElementById('dc-or-id').value || '0', 10) || null;

    const submitBtn  = document.getElementById('dc-submit-btn');
    const origText   = submitBtn.textContent;
    submitBtn.disabled    = true;
    submitBtn.textContent = 'Submitting…';

    try {
        // NOTE: Use dcFetch (→ Components.fetchJson → resolveAppUrl) so the
        // SFMS_BASE_PATH prefix is added automatically. Raw fetch('/api/…')
        // would resolve to the wrong origin on a subdirectory install.
        const { response, data: payload } = await dcFetch(
            '/api/dispatches',
            {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    department_id:       deptId ? parseInt(deptId, 10) : null,
                    room_id:             roomId ? parseInt(roomId, 10) : null,
                    purchase_receipt_id: orId,
                    notes:               notes  || null,
                    items,
                }),
            }
        );

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to create dispatch');
        }

        const newId = payload.data?.dispatch_id;
        window.location.href = newId ? `${DC_BASE}/${newId}` : DC_BASE;
    } catch (err) {
        dcShowError(err.message || 'An error occurred. Please try again.');
        submitBtn.disabled    = false;
        submitBtn.textContent = origText;
    }
});

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', async () => {
    // Departments and rooms load in parallel (independent of each other)
    loadDepartments();
    loadRooms();

    // OR number searchable select — raw path, resolveAppUrl adds base prefix
    if (window.Components && typeof Components.SearchableSelect === 'function') {
        new Components.SearchableSelect({
            inputId:    'dc-or-search',
            hiddenId:   'dc-or-id',
            endpoint:   '/api/purchase-receipts/search',
            displayKey: 'name',   // name = or_number, code = supplier_name (shown as "OR — Supplier")
        });
    }

    // Items must load before the first row is added so the select is populated
    await loadItems();

    document.getElementById('dc-items-loading').style.display = 'none';
    addItemRow();

    document.getElementById('dc-add-row').addEventListener('click', addItemRow);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
