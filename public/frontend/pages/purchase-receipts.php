<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: ' . public_url('/frontend/pages/index.php'));
    exit;
}

$user     = $_SESSION['user'];
$userRole = $user['role'] ?? '';

$allowedRoles = ['super_admin', 'maintenance_admin', 'maintenance_staff'];
if (!in_array($userRole, $allowedRoles, true)) {
    header('Location: ' . public_url('/frontend/pages/dashboard.php'));
    exit;
}

$canPost   = in_array($userRole, ['super_admin', 'maintenance_admin', 'maintenance_staff'], true);
$receiptId = isset($_GET['id']) && (int)$_GET['id'] > 0 ? (int)$_GET['id'] : 0;
$isDetail  = $receiptId > 0;

$pageTitle = $isDetail ? 'Purchase Receipt Detail - SFMS' : 'Purchase Receipts - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<?php if (!$isDetail): ?>
<!-- ================================================================
     LIST VIEW
     ================================================================ -->
<main class="container" style="margin-top:16px;">
    <div class="card" style="border-left:3px solid var(--primary-color);">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2>Purchase Receipts</h2>
                <p class="text-muted mb-0">Record incoming stock with OR number and supplier</p>
            </div>
            <?php if ($canPost): ?>
            <button type="button" class="btn btn-primary" id="newReceiptBtn">+ New Receipt</button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div id="receipts-container" class="table-responsive">
                <div class="ui-empty-state"><strong>Loading receipts...</strong></div>
            </div>
        </div>
    </div>
</main>

<!-- Modal: New Receipt -->
<div id="new-receipt-modal" class="modal" aria-hidden="true" role="dialog" aria-modal="true" style="display:none;">
    <div class="modal-content" style="max-width:600px;width:100%;padding:0;background:var(--card-color);color:var(--text-primary);border:1px solid var(--border);border-radius:12px;overflow:hidden;max-height:90vh;display:flex;flex-direction:column;">

        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:22px 24px 18px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(139,92,246,0.15);border:1px solid rgba(139,92,246,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 5H7C5.9 5 5 5.9 5 7V19C5 20.1 5.9 21 7 21H17C18.1 21 19 20.1 19 19V7C19 5.9 18.1 5 17 5H15"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"/>
                        <line x1="9" y1="12" x2="15" y2="12"/>
                        <line x1="9" y1="16" x2="13" y2="16"/>
                    </svg>
                </div>
                <div>
                    <h2 style="margin:0;font-size:16px;font-weight:700;line-height:1.3;">New Purchase Receipt</h2>
                    <p style="margin:2px 0 0;font-size:12px;opacity:0.55;line-height:1.4;">Record incoming stock with OR number and supplier</p>
                </div>
            </div>
            <button type="button" id="closeNewReceiptModal" aria-label="Close" style="background:none;border:none;cursor:pointer;padding:6px;opacity:0.5;color:inherit;font-size:22px;line-height:1;border-radius:6px;margin-left:12px;">×</button>
        </div>

        <!-- Form body -->
        <form id="new-receipt-form" style="overflow-y:auto;flex:1;">
            <div style="padding:24px;display:flex;flex-direction:column;gap:18px;">

                <!-- Row 1: OR Number + Receipt Date -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;opacity:0.65;">OR Number <span style="color:#ef4444;">*</span></label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;line-height:0;opacity:0.45;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                    <line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/>
                                    <line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>
                                </svg>
                            </span>
                            <input type="text" id="nr-or-number" class="form-control" placeholder="e.g. OR-2026-001" autocomplete="off" style="padding-left:32px;">
                        </div>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;opacity:0.65;">Receipt Date <span style="color:#ef4444;">*</span></label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;line-height:0;opacity:0.45;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                            </span>
                            <input type="date" id="nr-receipt-date" class="form-control" style="padding-left:32px;">
                        </div>
                    </div>
                </div>

                <!-- Row 2: Supplier Name (full width) -->
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;opacity:0.65;">Supplier Name <span style="color:#ef4444;">*</span></label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;line-height:0;opacity:0.45;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path d="M3 9l2.45-4.9A2 2 0 017.24 3h9.52a2 2 0 011.79 1.1L21 9"/>
                                <line x1="12" y1="9" x2="12" y2="21"/>
                            </svg>
                        </span>
                        <input type="text" id="nr-supplier-name" class="form-control" placeholder="Supplier or vendor name" autocomplete="off" style="padding-left:32px;">
                    </div>
                </div>

                <!-- Row 3: Department + Received By -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;opacity:0.65;">Department</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;line-height:0;opacity:0.45;z-index:1;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                                </svg>
                            </span>
                            <select id="nr-department-id" class="form-control" style="padding-left:32px;">
                                <option value="">— Select department —</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;opacity:0.65;">Received By</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;line-height:0;opacity:0.45;z-index:1;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                            <select id="nr-received-by" class="form-control" style="padding-left:32px;">
                                <option value="">Loading users...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Row 4: Remarks (full width) -->
                <div>
                    <label style="display:block;margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;opacity:0.65;">Remarks</label>
                    <textarea id="nr-remarks" class="form-control" rows="3" placeholder="Optional notes about this receipt"></textarea>
                </div>

                <p id="new-receipt-error" style="color:#ef4444;margin:0;font-size:13px;display:none;"></p>
            </div>

            <!-- Footer -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 24px;border-top:1px solid var(--border);flex-shrink:0;">
                <span style="font-size:12px;opacity:0.45;">* Required fields</span>
                <div style="display:flex;gap:10px;align-items:center;">
                    <button type="button" class="btn btn-secondary" id="cancelNewReceiptModal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitNewReceiptBtn" style="display:inline-flex;align-items:center;gap:6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                        Create Receipt
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ================================================================
     DETAIL VIEW
     ================================================================ -->
<main class="container" style="margin-top:16px;">

    <!-- Receipt header card -->
    <div class="card" style="border-left:3px solid var(--primary-color);">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="receipt-title">Purchase Receipt</h2>
                <p class="text-muted mb-0" id="receipt-subtitle">Loading...</p>
            </div>
            <a href="<?php echo htmlspecialchars(public_url('/frontend/pages/purchase-receipts.php')); ?>" class="btn btn-secondary">← Back to List</a>
        </div>
        <div class="card-body" id="receipt-header-container">
            <div class="ui-empty-state"><strong>Loading receipt details...</strong></div>
        </div>
    </div>

    <!-- Line items card -->
    <div class="card" style="margin-top:14px;border-left:3px solid var(--primary-color);">
        <div class="card-header d-flex justify-between align-center">
            <h3>Line Items</h3>
            <div class="d-flex gap-sm" id="draft-actions" style="display:none;">
                <button type="button" class="btn btn-secondary" id="addItemBtn">+ Add Item</button>
                <?php if ($canPost): ?>
                <button type="button" class="btn btn-primary" id="postReceiptBtn">Post Receipt</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div id="items-container" class="table-responsive">
                <div class="ui-empty-state"><strong>Loading items...</strong></div>
            </div>
        </div>
    </div>

</main>

<!-- Modal: Add Item -->
<div id="add-item-modal" class="modal" aria-hidden="true" role="dialog" aria-modal="true" style="display:none;">
    <div class="modal-content" style="max-width:520px;width:100%;background:var(--card-color);color:var(--text-primary);border:1px solid var(--border);">
        <div class="modal-header">
            <h2 class="modal-title">Add Line Item</h2>
            <button class="modal-close" type="button" id="closeAddItemModal" aria-label="Close">×</button>
        </div>
        <form id="add-item-form">
            <div style="padding:16px;display:grid;gap:14px;">
                <div>
                    <label style="display:block;margin-bottom:4px;font-weight:500;">Item Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="ai-item-name" class="form-control" placeholder="Type item name" autocomplete="off">
                    <p style="margin:4px 0 0;font-size:12px;" class="text-muted">If an item with this exact name already exists in the selected bodega, its quantity will be updated when posted.</p>
                </div>
                <div>
                    <label style="display:block;margin-bottom:4px;font-weight:500;">Category</label>
                    <select id="ai-category-id" class="form-control">
                        <option value="">— No category —</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;margin-bottom:4px;font-weight:500;">Bodega Room <span style="color:#ef4444;">*</span></label>
                    <select id="ai-inventory-room-id" class="form-control">
                        <option value="">— Select bodega room —</option>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label style="display:block;margin-bottom:4px;font-weight:500;">Quantity <span style="color:#ef4444;">*</span></label>
                        <input type="number" id="ai-quantity" class="form-control" min="1" value="1">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:4px;font-weight:500;">Unit</label>
                        <select id="ai-unit" class="form-control">
                            <option value="pc">pc</option>
                            <option value="set">set</option>
                            <option value="box">box</option>
                            <option value="unit">unit</option>
                        </select>
                    </div>
                </div>
                <p id="add-item-error" style="color:#ef4444;margin:0;display:none;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelAddItemModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitAddItemBtn">Add Item</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// ---------------------------------------------------------------------------
// Shared constants (PHP → JS)
// ---------------------------------------------------------------------------
const PR_RECEIPT_ID        = <?php echo $receiptId; ?>;
const PR_IS_DETAIL         = <?php echo $isDetail ? 'true' : 'false'; ?>;
const PR_CAN_POST          = <?php echo $canPost ? 'true' : 'false'; ?>;
const PR_SESSION_USER_ID   = <?php echo (int)($user['user_id'] ?? 0); ?>;
const PR_SESSION_USER_NAME = <?php echo json_encode($user['full_name'] ?? ''); ?>;

const PURCHASE_API  = window.SFMS_PUBLIC_URL('/api/purchase-receipts');

const PURCHASE_PAGE  = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/frontend/pages/purchase-receipts.php')
    : '/frontend/pages/purchase-receipts.php';

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------
function prEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function prNotify(msg, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(msg, type);
        return;
    }
    const _div = document.createElement('div');
    _div.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;background:#dc2626;color:white;padding:12px 20px;border-radius:8px;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.3);max-width:350px;';
    _div.textContent = msg;
    document.body.appendChild(_div);
    setTimeout(() => _div.remove(), 4000);
}

function prFormatDate(d) {
    if (!d) return '—';
    try { return new Date(d + 'T00:00:00').toLocaleDateString(); } catch(e) { return String(d); }
}

function prStatusBadge(status) {
    if (status === 'posted') {
        return '<span style="background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:500;">Posted</span>';
    }
    return '<span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:500;">Draft</span>';
}

function prOpenModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'flex';
    el.removeAttribute('aria-hidden');
}

function prCloseModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'none';
    el.setAttribute('aria-hidden', 'true');
}

async function prFetch(url, options) {
    const fetcher = window.Components && typeof Components.fetchJson === 'function'
        ? Components.fetchJson
        : async (u, o) => { const r = await fetch(u, o); return { response: r, data: await r.json() }; };
    return fetcher(url, options);
}

// ---------------------------------------------------------------------------
// LIST VIEW
// ---------------------------------------------------------------------------
<?php if (!$isDetail): ?>

async function loadReceipts() {
    try {
        const { response, data: payload } = await prFetch(
            PURCHASE_API,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load receipts');
        }

        const receipts = Array.isArray(payload.data?.receipts) ? payload.data.receipts : [];
        const container = document.getElementById('receipts-container');

        if (receipts.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No purchase receipts yet.</strong><span>Create one to start recording incoming stock.</span></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>OR Number</th><th>Date</th><th>Supplier</th><th>Department</th>'
            + '<th>Received By</th><th>Items</th><th>Status</th><th>Action</th>'
            + '</tr></thead><tbody>';

        receipts.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${prEscapeHtml(row.or_number)}</strong></td>`;
            html += `<td>${prEscapeHtml(prFormatDate(row.receipt_date))}</td>`;
            html += `<td>${prEscapeHtml(row.supplier_name)}</td>`;
            html += `<td>${prEscapeHtml(row.department_name || '—')}</td>`;
            html += `<td>${prEscapeHtml(row.received_by_name)}</td>`;
            html += `<td>${prEscapeHtml(row.item_count)}</td>`;
            html += `<td>${prStatusBadge(row.status)}</td>`;
            html += `<td><a class="btn btn-sm btn-primary" href="${prEscapeHtml(PURCHASE_PAGE)}?id=${row.id}">View</a></td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        document.getElementById('receipts-container').innerHTML =
            '<div class="ui-empty-state"><strong>Failed to load receipts.</strong></div>';
        prNotify(err.message || 'Unable to load receipts.');
    }
}

async function loadDepartmentsIntoSelect(selectId) {
    try {
        const { response, data: payload } = await prFetch(
            window.SFMS_PUBLIC_URL('/api/departments'),
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) return;

        const departments = Array.isArray(payload.data?.departments ?? payload.data)
            ? (payload.data?.departments ?? payload.data)
            : [];

        const sel = document.getElementById(selectId);
        if (!sel) return;

        departments.forEach((dept) => {
            const opt = document.createElement('option');
            opt.value = dept.department_id;
            opt.textContent = dept.name;
            sel.appendChild(opt);
        });
    } catch (_) { /* dropdown is optional — fail silently */ }
}

async function loadUsersIntoReceivedBySelect() {
    const sel = document.getElementById('nr-received-by');
    if (!sel) return;
    try {
        const { response, data: payload } = await prFetch(
            window.SFMS_PUBLIC_URL('/api/users'),
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error();

        const users = Array.isArray(payload.data?.users ?? payload.data)
            ? (payload.data?.users ?? payload.data)
            : [];

        sel.innerHTML = '';
        users.forEach((u) => {
            const opt = document.createElement('option');
            opt.value = u.user_id;
            opt.textContent = u.full_name;
            if (Number(u.user_id) === PR_SESSION_USER_ID) opt.selected = true;
            sel.appendChild(opt);
        });

        // Ensure session user is selectable even if absent from list
        if (PR_SESSION_USER_ID && !sel.value) {
            const opt = document.createElement('option');
            opt.value = PR_SESSION_USER_ID;
            opt.textContent = PR_SESSION_USER_NAME || 'Me';
            opt.selected = true;
            sel.insertBefore(opt, sel.firstChild);
        }
    } catch (_) {
        // Fallback: show only the session user
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = PR_SESSION_USER_ID;
        opt.textContent = PR_SESSION_USER_NAME || 'Me';
        opt.selected = true;
        sel.appendChild(opt);
    }
}

function initListView() {
    loadReceipts();
    loadDepartmentsIntoSelect('nr-department-id');
    loadUsersIntoReceivedBySelect();

    <?php if ($canPost): ?>
    const newReceiptBtn = document.getElementById('newReceiptBtn');
    if (newReceiptBtn) {
        newReceiptBtn.addEventListener('click', () => {
            // Reset receiver to the logged-in user each time the modal opens
            const receivedBySel = document.getElementById('nr-received-by');
            if (receivedBySel && PR_SESSION_USER_ID) receivedBySel.value = PR_SESSION_USER_ID;
            prOpenModal('new-receipt-modal');
        });
    }

    document.getElementById('closeNewReceiptModal').addEventListener('click',  () => prCloseModal('new-receipt-modal'));
    document.getElementById('cancelNewReceiptModal').addEventListener('click', () => prCloseModal('new-receipt-modal'));

    document.getElementById('new-receipt-modal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) prCloseModal('new-receipt-modal');
    });

    document.getElementById('new-receipt-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const orNumber       = document.getElementById('nr-or-number').value.trim();
        const receiptDate    = document.getElementById('nr-receipt-date').value.trim();
        const supplierName   = document.getElementById('nr-supplier-name').value.trim();
        const departmentId   = document.getElementById('nr-department-id').value;
        const remarks        = document.getElementById('nr-remarks').value.trim();
        const receivedByRaw  = document.getElementById('nr-received-by').value;
        const receivedById   = receivedByRaw ? parseInt(receivedByRaw, 10) : null;
        const errEl        = document.getElementById('new-receipt-error');
        const submitBtn    = document.getElementById('submitNewReceiptBtn');

        errEl.style.display = 'none';

        if (!orNumber || !receiptDate || !supplierName) {
            errEl.textContent = 'OR number, receipt date, and supplier name are required.';
            errEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';

        try {
            const { response, data: payload } = await prFetch(
                PURCHASE_API,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        or_number:             orNumber,
                        receipt_date:          receiptDate,
                        supplier_name:         supplierName,
                        department_id:         departmentId || null,
                        remarks:               remarks || null,
                        received_by_user_id:   receivedById,
                    }),
                }
            );

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Failed to create receipt');
            }

            const newId = payload.data?.id;
            window.location.href = `${PURCHASE_PAGE}?id=${newId}`;
        } catch (err) {
            errEl.textContent = err.message || 'An error occurred.';
            errEl.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Receipt';
        }
    });
    <?php endif; ?>
}

document.addEventListener('DOMContentLoaded', initListView);

<?php else: ?>
// ---------------------------------------------------------------------------
// DETAIL VIEW
// ---------------------------------------------------------------------------

let prDropdownsLoaded = false;

async function loadReceiptDetail() {
    try {
        const { response, data: payload } = await prFetch(
            `${PURCHASE_API}/${PR_RECEIPT_ID}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load receipt');
        }

        const receipt = payload.data?.receipt;
        if (!receipt) throw new Error('Invalid receipt response.');

        document.getElementById('receipt-title').textContent = 'OR# ' + (receipt.or_number || '—');
        document.getElementById('receipt-subtitle').textContent =
            'Status: ' + (receipt.status === 'posted' ? 'Posted' : 'Draft') +
            ' • Supplier: ' + (receipt.supplier_name || '—');

        const date       = prFormatDate(receipt.receipt_date);
        const createdAt  = receipt.created_at ? new Date(receipt.created_at).toLocaleString() : '—';

        document.getElementById('receipt-header-container').innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><strong>OR Number:</strong> ${prEscapeHtml(receipt.or_number)}</div>
                <div><strong>Receipt Date:</strong> ${prEscapeHtml(date)}</div>
                <div><strong>Supplier:</strong> ${prEscapeHtml(receipt.supplier_name)}</div>
                <div><strong>Department:</strong> ${prEscapeHtml(receipt.department_name || '—')}</div>
                <div><strong>Received By:</strong> ${prEscapeHtml(receipt.received_by_name)}</div>
                <div><strong>Status:</strong> ${prStatusBadge(receipt.status)}</div>
                ${receipt.remarks ? `<div style="grid-column:1/-1;"><strong>Remarks:</strong> ${prEscapeHtml(receipt.remarks)}</div>` : ''}
                <div class="text-muted" style="grid-column:1/-1;font-size:12px;">Created: ${prEscapeHtml(createdAt)}</div>
            </div>`;

        // Show draft action buttons only when status is draft
        if (receipt.status === 'draft') {
            const draftActions = document.getElementById('draft-actions');
            if (draftActions) draftActions.style.display = 'flex';
        }

        await loadReceiptItems();
    } catch (err) {
        document.getElementById('receipt-header-container').innerHTML =
            '<div class="ui-empty-state"><strong>Failed to load receipt.</strong></div>';
        prNotify(err.message || 'Unable to load receipt details.');
    }
}

async function loadReceiptItems() {
    const container = document.getElementById('items-container');

    try {
        const { response, data: payload } = await prFetch(
            `${PURCHASE_API}/${PR_RECEIPT_ID}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load items');
        }

        const items = Array.isArray(payload.data?.items) ? payload.data.items : [];

        if (items.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No items added yet.</strong><span>Use the Add Item button to add line items.</span></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>#</th><th>Item Name</th><th>Category</th><th>Bodega Room</th><th>Qty</th><th>Unit</th>'
            + '</tr></thead><tbody>';

        items.forEach((item, idx) => {
            html += '<tr>';
            html += `<td>${idx + 1}</td>`;
            html += `<td><strong>${prEscapeHtml(item.item_name)}</strong></td>`;
            html += `<td>${prEscapeHtml(item.category_name || '—')}</td>`;
            html += `<td>${prEscapeHtml(item.inventory_room_name || '—')}</td>`;
            html += `<td>${prEscapeHtml(item.quantity_received)}</td>`;
            html += `<td>${prEscapeHtml(item.unit)}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load items.</strong></div>';
        prNotify(err.message || 'Unable to load line items.');
    }
}

async function loadAddItemDropdowns() {
    if (prDropdownsLoaded) return;
    prDropdownsLoaded = true;

    // Categories
    try {
        const { response, data: payload } = await prFetch(
            `/api/inventory-categories`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (response.ok && payload.success) {
            const cats = Array.isArray(payload.data?.categories ?? payload.data)
                ? (payload.data?.categories ?? payload.data)
                : [];
            const sel = document.getElementById('ai-category-id');
            cats.forEach((c) => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                sel.appendChild(opt);
            });
        }
    } catch (_) {}

    // Inventory rooms
    try {
        const { response, data: payload } = await prFetch(
            `/api/inventory-rooms`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (response.ok && payload.success) {
            const rooms = Array.isArray(payload.data?.rooms ?? payload.data)
                ? (payload.data?.rooms ?? payload.data)
                : [];
            const sel = document.getElementById('ai-inventory-room-id');
            rooms.forEach((r) => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                sel.appendChild(opt);
            });
        }
    } catch (_) {}
}

function initDetailView() {
    loadReceiptDetail();

    // Add Item button
    const addItemBtn = document.getElementById('addItemBtn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', async () => {
            await loadAddItemDropdowns();
            document.getElementById('add-item-form').reset();
            document.getElementById('add-item-error').style.display = 'none';
            prOpenModal('add-item-modal');
        });
    }

    document.getElementById('closeAddItemModal').addEventListener('click',  () => prCloseModal('add-item-modal'));
    document.getElementById('cancelAddItemModal').addEventListener('click', () => prCloseModal('add-item-modal'));

    document.getElementById('add-item-modal').addEventListener('click', (e) => {
        if (e.target === e.currentTarget) prCloseModal('add-item-modal');
    });

    document.getElementById('add-item-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const itemName       = document.getElementById('ai-item-name').value.trim();
        const categoryId     = document.getElementById('ai-category-id').value;
        const inventoryRoomId = document.getElementById('ai-inventory-room-id').value;
        const quantity       = parseInt(document.getElementById('ai-quantity').value, 10);
        const unit           = document.getElementById('ai-unit').value;
        const errEl          = document.getElementById('add-item-error');
        const submitBtn      = document.getElementById('submitAddItemBtn');

        errEl.style.display = 'none';

        if (!itemName) {
            errEl.textContent = 'Item name is required.';
            errEl.style.display = 'block';
            return;
        }
        if (!inventoryRoomId) {
            errEl.textContent = 'Please select a bodega room.';
            errEl.style.display = 'block';
            return;
        }
        if (!quantity || quantity < 1) {
            errEl.textContent = 'Quantity must be at least 1.';
            errEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Adding...';

        try {
            const { response, data: payload } = await prFetch(
                `${PURCHASE_API}/${PR_RECEIPT_ID}/items`,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        item_name:         itemName,
                        category_id:       categoryId || null,
                        inventory_room_id: parseInt(inventoryRoomId, 10),
                        quantity_received: quantity,
                        unit:              unit,
                    }),
                }
            );

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Failed to add item');
            }

            prCloseModal('add-item-modal');
            if (window.UI && typeof UI.toast === 'function') {
                UI.toast('Item added successfully.', 'success');
            }
            await loadReceiptItems();
        } catch (err) {
            errEl.textContent = err.message || 'An error occurred.';
            errEl.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Item';
        }
    });

    // Post Receipt button (only rendered in DOM when $canPost is true)
    <?php if ($canPost): ?>
    const postBtn = document.getElementById('postReceiptBtn');
    if (postBtn) {
        postBtn.addEventListener('click', async () => {
            const confirmed = window.UI && typeof UI.systemConfirm === 'function'
                ? await UI.systemConfirm('This will add stock to inventory. Are you sure you want to post this receipt?', 'Post Receipt', 'Cancel')
                : window.confirm('This will add stock to inventory. Continue?');

            if (!confirmed) return;

            postBtn.disabled = true;
            postBtn.textContent = 'Posting...';

            try {
                const { response, data: payload } = await prFetch(
                    `${PURCHASE_API}/${PR_RECEIPT_ID}/post`,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({}),
                    }
                );

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Failed to post receipt');
                }

                if (window.UI && typeof UI.toast === 'function') {
                    UI.toast('Receipt posted. Inventory has been updated.', 'success');
                }

                // Reload so PHP re-renders with current state from API
                window.location.reload();
            } catch (err) {
                prNotify(err.message || 'Failed to post receipt.');
                postBtn.disabled = false;
                postBtn.textContent = 'Post Receipt';
            }
        });
    }
    <?php endif; ?>
}

document.addEventListener('DOMContentLoaded', initDetailView);

<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
