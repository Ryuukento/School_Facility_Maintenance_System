<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Repair Update - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;max-width:1020px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="repair-update-title">Update Repair Request</h2>
                <p class="text-muted mb-0" id="repair-update-subtitle">Update repair progress, cost, timeline, and failure outcomes.</p>
            </div>
            <div class="d-flex gap-sm">
                <a href="<?php echo htmlspecialchars(public_url('/repairs')); ?>" class="btn btn-secondary">Back to Repairs</a>
                <a href="#" id="repair-update-view-link" class="btn btn-primary">View Repair</a>
            </div>
        </div>
        <div class="card-body">
            <div id="repair-update-summary" style="margin-bottom:12px;"></div>
            <form id="repair-update-form">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label for="repair-update-status">Repair Status *</label>
                        <select id="repair-update-status" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="diagnosing">Diagnosing</option>
                            <option value="repairing">Repairing</option>
                            <option value="waiting_parts">Waiting Parts</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="repair-update-type">Repair Type</label>
                        <select id="repair-update-type" class="form-control">
                            <option value="corrective">Corrective</option>
                            <option value="diagnostic">Diagnostic</option>
                            <option value="electrical">Electrical</option>
                            <option value="mechanical">Mechanical</option>
                            <option value="parts_replacement">Parts Replacement</option>
                            <option value="preventive">Preventive</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label for="repair-update-date">Repair Date</label>
                        <input type="date" id="repair-update-date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="repair-update-estimated-date">Estimated Completion</label>
                        <input type="date" id="repair-update-estimated-date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="repair-update-completion-date">Completion Date</label>
                        <input type="date" id="repair-update-completion-date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="repair-update-cost">Repair Cost</label>
                        <input type="number" id="repair-update-cost" class="form-control" min="0" step="0.01">
                    </div>
                </div>

                <div class="form-group">
                    <label for="repair-update-description">Repair Description</label>
                    <textarea id="repair-update-description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="repair-update-notes">Notes</label>
                    <textarea id="repair-update-notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group" id="repair-update-failure-wrap" style="display:none;">
                    <label for="repair-update-failure-reason">Failure Reason</label>
                    <textarea id="repair-update-failure-reason" class="form-control" rows="3" placeholder="Explain why repair failed or item is beyond repair..."></textarea>
                </div>
                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="repair-update-save-btn">Save Update</button>
                    <a href="#" id="repair-update-cancel-link" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
const REPAIRS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/repairs') : '/api/repairs';
const REPAIRS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/repairs') : '/repairs';

function getRepairUpdateId() {
    return Number(new URLSearchParams(window.location.search).get('id') || 0);
}

function repairUpdateNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    window.alert(message);
}

function bindFailureToggle() {
    const statusSelect = document.getElementById('repair-update-status');
    const wrap = document.getElementById('repair-update-failure-wrap');
    const refresh = () => {
        wrap.style.display = statusSelect.value === 'failed' ? 'block' : 'none';
    };
    statusSelect.addEventListener('change', refresh);
    refresh();
}

async function loadRepairForUpdate() {
    const id = getRepairUpdateId();
    if (!id) {
        repairUpdateNotify('Invalid repair request ID.', 'warning');
        window.location.href = REPAIRS_PAGE_BASE;
        return;
    }

    document.getElementById('repair-update-view-link').href = `${REPAIRS_PAGE_BASE}/${id}`;
    document.getElementById('repair-update-cancel-link').href = `${REPAIRS_PAGE_BASE}/${id}`;

    try {
        const { response, data } = await Components.fetchJson(`${REPAIRS_API_BASE}/${id}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load repair request');
        }

        const repair = data.data?.repair;
        if (!repair) {
            throw new Error('Invalid repair payload.');
        }

        document.getElementById('repair-update-title').textContent = `Update ${repair.repair_code || 'Repair Request'}`;
        document.getElementById('repair-update-subtitle').textContent = `Damage report: ${repair.damage_report?.damage_report_code || 'N/A'} | Technician: ${repair.technician?.full_name || 'Unassigned'}`;
        document.getElementById('repair-update-summary').innerHTML = `<div class="text-muted">Item: <strong>${repair.damage_report?.item?.name || 'Unknown'}</strong> | Current status: <strong>${repair.repair_status || 'pending'}</strong></div>`;

        document.getElementById('repair-update-status').value = repair.repair_status || 'pending';
        document.getElementById('repair-update-type').value = repair.repair_type || 'corrective';
        document.getElementById('repair-update-date').value = repair.repair_date || '';
        document.getElementById('repair-update-estimated-date').value = repair.estimated_completion_date || '';
        document.getElementById('repair-update-completion-date').value = repair.completion_date || '';
        document.getElementById('repair-update-cost').value = Number(repair.repair_cost || 0);
        document.getElementById('repair-update-description').value = repair.repair_description || '';
        document.getElementById('repair-update-notes').value = repair.notes || '';
        document.getElementById('repair-update-failure-reason').value = repair.failure_reason || '';

        bindFailureToggle();
    } catch (error) {
        repairUpdateNotify(error.message || 'Unable to load repair request.');
    }
}

async function submitRepairUpdate(event) {
    event.preventDefault();

    const id = getRepairUpdateId();
    const saveBtn = document.getElementById('repair-update-save-btn');

    try {
        Components.setLoading(saveBtn, true);
        const { response, data } = await Components.fetchJson(`${REPAIRS_API_BASE}/${id}/update`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                repair_status: document.getElementById('repair-update-status').value,
                repair_type: document.getElementById('repair-update-type').value,
                repair_date: document.getElementById('repair-update-date').value || null,
                estimated_completion_date: document.getElementById('repair-update-estimated-date').value || null,
                completion_date: document.getElementById('repair-update-completion-date').value || null,
                repair_cost: Number(document.getElementById('repair-update-cost').value || 0),
                repair_description: document.getElementById('repair-update-description').value.trim() || null,
                notes: document.getElementById('repair-update-notes').value.trim() || null,
                failure_reason: document.getElementById('repair-update-failure-reason').value.trim() || null,
            }),
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to update repair request');
        }

        Components.toast(data.message || 'Repair request updated.', 'success');
        window.location.href = `${REPAIRS_PAGE_BASE}/${id}`;
    } catch (error) {
        repairUpdateNotify(error.message || 'Unable to update repair request.');
    } finally {
        Components.setLoading(saveBtn, false);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadRepairForUpdate();
    document.getElementById('repair-update-form').addEventListener('submit', submitRepairUpdate);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
