<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$_ddUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_ddRole = strtolower(trim((string)($_ddUser['role'] ?? '')));

$canAct     = in_array($_ddRole, ['super_admin', 'maintenance_admin'], true);
$dispatchId = isset($_GET['id']) && (int)$_GET['id'] > 0 ? (int)$_GET['id'] : 0;
$pageTitle  = 'Dispatch Detail - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;">

    <!-- Section 1: Dispatch Header Card -->
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="dd-title">Dispatch</h2>
                <p class="text-muted mb-0" id="dd-subtitle">Loading...</p>
            </div>
            <div class="d-flex gap-sm">
                <button type="button" class="btn btn-secondary" id="dd-print-btn">Print Dispatch Report</button>
                <a href="<?php echo htmlspecialchars(public_url('/dispatches')); ?>" class="btn btn-secondary">← Back to Dispatches</a>
            </div>
        </div>
        <div class="card-body">
            <div id="dd-header-container">
                <div class="ui-empty-state"><strong>Loading dispatch details...</strong></div>
            </div>
        </div>
    </div>

    <!-- Section 2: Items Table -->
    <div class="card" style="margin-top:14px;">
        <div class="card-header">
            <h3 style="margin:0;">Line Items</h3>
        </div>
        <div class="card-body">
            <div id="dd-items-container" class="table-responsive">
                <div class="ui-empty-state"><strong>Loading items...</strong></div>
            </div>
        </div>
    </div>

    <!-- Section 3: Action Buttons — PHP role-gate ensures this block only renders for admins -->
    <?php if ($canAct): ?>
    <div id="dd-action-section" class="card" style="margin-top:14px;display:none;">
        <div class="card-body" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button type="button" id="dd-approve-btn"
                    class="btn btn-primary"
                    style="display:none;background:#16a34a;border-color:#16a34a;">
                Approve Dispatch
            </button>
            <button type="button" id="dd-release-btn"
                    class="btn btn-primary"
                    style="display:none;background:#2563eb;border-color:#2563eb;">
                Release / Deploy Items
            </button>
            <button type="button" id="dd-cancel-btn"
                    class="btn btn-danger"
                    style="display:none;background:#dc2626;border-color:#dc2626;">
                Cancel Dispatch
            </button>
            <p id="dd-action-error" style="color:#ef4444;margin:0;display:none;font-size:14px;"></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canAct): ?>
    <!-- Cancel Dispatch Modal -->
    <div id="dd-cancel-modal" class="modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="dd-cancel-modal-title">
        <div class="modal" style="max-width:480px;">
            <div class="modal-header">
                <h3 class="modal-title" id="dd-cancel-modal-title">Cancel Dispatch</h3>
                <button type="button" class="modal-close" id="dd-cancel-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:12px;color:#374151;">Please provide a reason for cancelling this dispatch. This action cannot be undone.</p>
                <label for="dd-cancel-reason" style="font-weight:600;display:block;margin-bottom:6px;">Reason <span style="color:#ef4444;">*</span></label>
                <textarea id="dd-cancel-reason" rows="3" class="form-control" placeholder="Enter cancellation reason..." style="width:100%;resize:vertical;"></textarea>
                <p id="dd-cancel-error" style="color:#ef4444;font-size:13px;margin-top:6px;display:none;"></p>
            </div>
            <div class="modal-footer" style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" id="dd-cancel-modal-dismiss">Keep Dispatch</button>
                <button type="button" class="btn btn-danger" id="dd-cancel-confirm-btn" style="background:#dc2626;border-color:#dc2626;">Confirm Cancel</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<script>
const DSP_ID      = <?php echo $dispatchId; ?>;
const DSP_CAN_ACT = <?php echo $canAct ? 'true' : 'false'; ?>;
const DSP_USER_ID = <?php echo (int)($_ddUser['user_id'] ?? $_ddUser['id'] ?? 0); ?>;

const DD_PRINT_BASE = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/dispatches')
    : '/api/dispatches';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function ddEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function ddNotify(message, type = 'danger') {
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

function ddFormatDate(d) {
    if (!d) return '—';
    try { return new Date(d).toLocaleString(); } catch (e) { return String(d); }
}

function ddDispatchBadge(status) {
    const styles = {
        pending:   'background:#fef3c7;color:#92400e',
        approved:  'background:#d1fae5;color:#065f46',
        released:  'background:#dbeafe;color:#1e40af',
        cancelled: 'background:#fee2e2;color:#991b1b',
    };
    const style = styles[status] || 'background:#f3f4f6;color:#374151';
    const label = String(status || '').replace(/\b\w/g, (s) => s.toUpperCase());
    return `<span style="${style};padding:2px 10px;border-radius:12px;font-size:12px;font-weight:500;">${ddEscapeHtml(label)}</span>`;
}

function ddItemStatusBadge(status) {
    const styles = {
        available:    'background:#d1fae5;color:#065f46',
        low_stock:    'background:#fef3c7;color:#92400e',
        out_of_stock: 'background:#fee2e2;color:#991b1b',
    };
    const style = styles[status] || 'background:#f3f4f6;color:#374151';
    const label = String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase());
    return `<span style="${style};padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">${ddEscapeHtml(label)}</span>`;
}

// ---------------------------------------------------------------------------
// Load dispatch detail
// ---------------------------------------------------------------------------

async function loadDispatchDetail() {
    if (DSP_ID <= 0) {
        document.getElementById('dd-header-container').innerHTML =
            '<div class="ui-empty-state"><strong>Dispatch not found.</strong><span>No valid ID was provided in the URL.</span></div>';
        document.getElementById('dd-items-container').innerHTML = '';
        return;
    }

    try {
        const response = await fetch(
            `/api/dispatches/${DSP_ID}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
        );
        const payload = await response.json();

        if (response.status === 404 || (payload && !payload.success && /not found/i.test(payload.message || ''))) {
            document.getElementById('dd-header-container').innerHTML =
                '<div class="ui-empty-state"><strong>Dispatch not found.</strong><span>This dispatch does not exist or has been removed.</span></div>';
            document.getElementById('dd-items-container').innerHTML = '';
            document.getElementById('dd-title').textContent = 'Dispatch Not Found';
            document.getElementById('dd-subtitle').textContent = '';
            return;
        }

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load dispatch');
        }

        const dispatch = payload.data?.dispatch;
        const items    = Array.isArray(dispatch?.items) ? dispatch.items : [];

        if (!dispatch) {
            throw new Error('Invalid response: missing dispatch data');
        }

        // --- Header card ---
        document.getElementById('dd-title').textContent   = dispatch.dispatch_code || 'Dispatch';
        document.getElementById('dd-subtitle').innerHTML  = `Status: ${ddDispatchBadge(dispatch.status)}`;

        document.getElementById('dd-header-container').innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><strong>Dispatch Code:</strong> ${ddEscapeHtml(dispatch.dispatch_code)}</div>
                <div><strong>Status:</strong> ${ddDispatchBadge(dispatch.status)}</div>
                <div><strong>Department:</strong> ${ddEscapeHtml(dispatch.department_name || '—')}</div>
                <div><strong>Room / Lab:</strong> ${ddEscapeHtml(dispatch.room_name || '—')}</div>
                <div><strong>Approved By:</strong> ${ddEscapeHtml(dispatch.approved_by_name || '—')}</div>
                <div><strong>Released By:</strong> ${ddEscapeHtml(dispatch.released_by_name || '—')}</div>
                <div><strong>Receiver:</strong> ${ddEscapeHtml(dispatch.receiver_name || '—')}</div>
                <div><strong>Created:</strong> ${ddEscapeHtml(ddFormatDate(dispatch.created_at))}</div>
                ${dispatch.notes
                    ? `<div style="grid-column:1/-1;"><strong>Notes:</strong> ${ddEscapeHtml(dispatch.notes)}</div>`
                    : ''}
            </div>`;

        // --- Items table ---
        const itemsContainer = document.getElementById('dd-items-container');
        if (items.length === 0) {
            itemsContainer.innerHTML =
                '<div class="ui-empty-state"><strong>No items in this dispatch.</strong></div>';
        } else {
            let html = '<table class="table"><thead><tr>'
                + '<th>Item Name</th><th>Quantity</th><th>Item Status</th><th>Available Stock</th>'
                + '</tr></thead><tbody>';
            items.forEach((item) => {
                html += '<tr>';
                html += `<td><strong>${ddEscapeHtml(item.item?.name ?? '—')}</strong></td>`;
                html += `<td>${ddEscapeHtml(item.quantity)}</td>`;
                html += `<td>${ddItemStatusBadge(item.item?.status ?? '')}</td>`;
                html += `<td>${ddEscapeHtml(item.item?.quantity ?? '—')}</td>`;
                html += '</tr>';
            });
            html += '</tbody></table>';
            itemsContainer.innerHTML = html;
        }

        // --- Action buttons (already PHP role-gated; JS gates on status) ---
        if (DSP_CAN_ACT) {
            if (dispatch.status === 'pending') {
                document.getElementById('dd-action-section').style.display = 'block';
                document.getElementById('dd-approve-btn').style.display    = 'inline-flex';
                document.getElementById('dd-cancel-btn').style.display     = 'inline-flex';
            } else if (dispatch.status === 'approved') {
                document.getElementById('dd-action-section').style.display = 'block';
                document.getElementById('dd-release-btn').style.display    = 'inline-flex';
                document.getElementById('dd-cancel-btn').style.display     = 'inline-flex';
            }
            // released / cancelled → action section stays hidden (read-only)
        }
    } catch (err) {
        document.getElementById('dd-header-container').innerHTML =
            '<div class="ui-empty-state"><strong>Failed to load dispatch.</strong></div>';
        ddNotify(err.message || 'Unable to load dispatch details.');
    }
}

// ---------------------------------------------------------------------------
// Approve
// ---------------------------------------------------------------------------

async function doApprove() {
    const confirmed = window.UI && typeof UI.systemConfirm === 'function'
        ? await UI.systemConfirm('Approve this dispatch request?', 'Approve', 'Cancel')
        : window.confirm('Approve this dispatch request?');
    if (!confirmed) return;

    const btn      = document.getElementById('dd-approve-btn');
    const errEl    = document.getElementById('dd-action-error');
    const origText = btn.textContent;

    btn.disabled    = true;
    btn.textContent = 'Approving...';
    errEl.style.display = 'none';

    try {
        const response = await fetch(
            `/api/dispatches/${DSP_ID}/approve`,
            {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body:        JSON.stringify({ approved_by: DSP_USER_ID }),
            }
        );
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to approve dispatch');
        }

        window.location.reload();
    } catch (err) {
        errEl.textContent   = err.message || 'An error occurred while approving.';
        errEl.style.display = 'block';
        btn.disabled        = false;
        btn.textContent     = origText;
    }
}

// ---------------------------------------------------------------------------
// Release
// ---------------------------------------------------------------------------

async function doRelease() {
    const confirmed = window.UI && typeof UI.systemConfirm === 'function'
        ? await UI.systemConfirm('This will deduct items from bodega stock. Continue?', 'Release', 'Cancel')
        : window.confirm('This will deduct items from bodega stock. Continue?');
    if (!confirmed) return;

    const btn      = document.getElementById('dd-release-btn');
    const errEl    = document.getElementById('dd-action-error');
    const origText = btn.textContent;

    btn.disabled    = true;
    btn.textContent = 'Releasing...';
    errEl.style.display = 'none';

    try {
        const response = await fetch(
            `/api/dispatches/${DSP_ID}/release`,
            {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body:        JSON.stringify({ released_by: DSP_USER_ID }),
            }
        );
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to release dispatch');
        }

        window.location.reload();
    } catch (err) {
        // Surface insufficient-stock errors prominently
        errEl.textContent   = err.message || 'An error occurred while releasing.';
        errEl.style.display = 'block';
        btn.disabled        = false;
        btn.textContent     = origText;
    }
}

// ---------------------------------------------------------------------------
// Cancel
// ---------------------------------------------------------------------------

function ddOpenCancelModal() {
    document.getElementById('dd-cancel-reason').value = '';
    document.getElementById('dd-cancel-error').style.display = 'none';
    document.getElementById('dd-cancel-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('dd-cancel-reason').focus(), 50);
}

function ddCloseCancelModal() {
    document.getElementById('dd-cancel-modal').style.display = 'none';
}

async function doCancel() {
    const reason  = document.getElementById('dd-cancel-reason').value.trim();
    const errEl   = document.getElementById('dd-cancel-error');

    if (reason.length < 3) {
        errEl.textContent   = 'Please enter a reason (at least 3 characters).';
        errEl.style.display = 'block';
        return;
    }

    const confirmBtn      = document.getElementById('dd-cancel-confirm-btn');
    const origText        = confirmBtn.textContent;
    confirmBtn.disabled   = true;
    confirmBtn.textContent = 'Cancelling...';
    errEl.style.display   = 'none';

    try {
        const response = await fetch(
            `/api/dispatches/${DSP_ID}/cancel`,
            {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body:        JSON.stringify({ reason }),
            }
        );
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to cancel dispatch');
        }

        ddCloseCancelModal();
        loadDispatchDetail();
    } catch (err) {
        errEl.textContent       = err.message || 'An error occurred while cancelling.';
        errEl.style.display     = 'block';
        confirmBtn.disabled     = false;
        confirmBtn.textContent  = origText;
    }
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    loadDispatchDetail();

    document.getElementById('dd-print-btn').addEventListener('click', () => {
        if (DSP_ID > 0) {
            window.open(`${DD_PRINT_BASE}/${DSP_ID}/print`, '_blank');
        }
    });

    <?php if ($canAct): ?>
    const approveBtn = document.getElementById('dd-approve-btn');
    const releaseBtn = document.getElementById('dd-release-btn');
    const cancelBtn  = document.getElementById('dd-cancel-btn');
    if (approveBtn) approveBtn.addEventListener('click', doApprove);
    if (releaseBtn) releaseBtn.addEventListener('click', doRelease);
    if (cancelBtn)  cancelBtn.addEventListener('click', ddOpenCancelModal);
    document.getElementById('dd-cancel-modal-close').addEventListener('click', ddCloseCancelModal);
    document.getElementById('dd-cancel-modal-dismiss').addEventListener('click', ddCloseCancelModal);
    document.getElementById('dd-cancel-confirm-btn').addEventListener('click', doCancel);
    document.getElementById('dd-cancel-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('dd-cancel-modal')) ddCloseCancelModal();
    });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
