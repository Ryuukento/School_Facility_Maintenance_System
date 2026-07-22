<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Repair Requests - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;">
    <div class="card" style="margin-bottom:14px;border-left:3px solid var(--primary-color);">
        <div class="card-header">
            <div>
                <h2>Repair Requests</h2>
                <p class="text-muted mb-0">Create and track repair work for damaged deployed items.</p>
            </div>
        </div>
        <div class="card-body">
            <form id="repair-create-form">
                <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label for="repair-damage-search">Damage Report *</label>
                        <input type="text" id="repair-damage-search" class="form-control" placeholder="Search damage report..." required>
                        <input type="hidden" id="repair-damage-id">
                    </div>
                    <div class="form-group">
                        <label for="repair-tech-search">Technician Assigned</label>
                        <input type="text" id="repair-tech-search" class="form-control" placeholder="Search technician...">
                        <input type="hidden" id="repair-tech-id">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label for="repair-type">Repair Type *</label>
                        <select id="repair-type" class="form-control" required>
                            <option value="corrective">Corrective</option>
                            <option value="diagnostic">Diagnostic</option>
                            <option value="electrical">Electrical</option>
                            <option value="mechanical">Mechanical</option>
                            <option value="parts_replacement">Parts Replacement</option>
                            <option value="preventive">Preventive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="repair-date">Repair Date</label>
                        <input type="date" id="repair-date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="repair-estimated-date">Estimated Completion</label>
                        <input type="date" id="repair-estimated-date" class="form-control">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1.4fr .6fr;gap:12px;">
                    <div class="form-group">
                        <label for="repair-description">Repair Description *</label>
                        <textarea id="repair-description" class="form-control" rows="3" placeholder="Describe the repair scope..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="repair-cost">Repair Cost</label>
                        <input type="number" id="repair-cost" class="form-control" min="0" step="0.01" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="repair-notes">Notes</label>
                    <textarea id="repair-notes" class="form-control" rows="2" placeholder="Optional assignment or intake notes..."></textarea>
                </div>

                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="repair-create-btn">Create Repair Request</button>
                    <button type="button" class="btn btn-secondary" id="repair-create-reset">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="border-left:3px solid var(--primary-color);">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h3>Repair Queue</h3>
                <p class="text-muted mb-0">Monitor assignments, repair progress, costs, and replacement outcomes.</p>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:10px;margin-bottom:12px;">
                <input type="search" id="repair-search" class="form-control" placeholder="Search repair code, damage report, item, or technician...">
                <select id="repair-status-filter" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="assigned">Assigned</option>
                    <option value="diagnosing">Diagnosing</option>
                    <option value="repairing">Repairing</option>
                    <option value="waiting_parts">Waiting Parts</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="archived">Archived</option>
                </select>
                <button type="button" class="btn btn-secondary" id="repair-filter-clear">Clear</button>
            </div>

            <div id="repair-list-container" class="table-responsive">
                <div class="ui-empty-state"><strong>Loading repair requests...</strong></div>
            </div>
        </div>
    </div>
</main>

<script>
const REPAIRS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/repairs') : '/api/repairs';
const REPAIRS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/repairs') : '/repairs';
const DAMAGE_REPORTS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/damage-reports') : '/api/damage-reports';

function repairNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    window.alert(message);
}

function escapeRepairHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatRepairStatus(value) {
    return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function initRepairCreateSelects() {
    new Components.SearchableSelect({
        inputId: 'repair-damage-search',
        hiddenId: 'repair-damage-id',
        endpoint: `${REPAIRS_API_BASE}/support/damage-reports`,
        displayKey: 'label',
    });

    new Components.SearchableSelect({
        inputId: 'repair-tech-search',
        hiddenId: 'repair-tech-id',
        endpoint: `${REPAIRS_API_BASE}/support/technicians`,
        displayKey: 'full_name',
    });
}

async function prefillDamageReportFromQuery() {
    const id = Number(new URLSearchParams(window.location.search).get('damage_report_id') || 0);
    if (!id) return;

    try {
        const { response, data } = await Components.fetchJson(`${DAMAGE_REPORTS_API_BASE}/${id}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        if (!response.ok || !data.success) {
            return;
        }

        const report = data.data?.report;
        if (!report) return;

        document.getElementById('repair-damage-id').value = String(report.id);
        document.getElementById('repair-damage-search').value = `${report.damage_report_code} - ${report.item?.name || 'Unknown item'}`;
    } catch (error) {
        console.error('Repair prefill error', error);
    }
}

async function submitRepairCreate(event) {
    event.preventDefault();

    const damageReportId = Number(document.getElementById('repair-damage-id').value || 0);
    const technicianUserId = Number(document.getElementById('repair-tech-id').value || 0);
    const payload = {
        damage_report_id: damageReportId,
        technician_user_id: technicianUserId || null,
        repair_type: document.getElementById('repair-type').value,
        repair_description: document.getElementById('repair-description').value.trim(),
        repair_cost: Number(document.getElementById('repair-cost').value || 0),
        repair_date: document.getElementById('repair-date').value || null,
        estimated_completion_date: document.getElementById('repair-estimated-date').value || null,
        notes: document.getElementById('repair-notes').value.trim() || null,
    };

    if (!payload.damage_report_id || !payload.repair_description) {
        repairNotify('Damage report and repair description are required.', 'warning');
        return;
    }

    const saveBtn = document.getElementById('repair-create-btn');

    try {
        Components.setLoading(saveBtn, true);
        const { response, data } = await Components.fetchJson(REPAIRS_API_BASE, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to create repair request');
        }

        Components.toast(data.message || 'Repair request created successfully.', 'success');
        document.getElementById('repair-create-form').reset();
        document.getElementById('repair-damage-id').value = '';
        document.getElementById('repair-tech-id').value = '';
        await loadRepairRequests();

        const repairId = data.data?.repair_id;
        if (repairId) {
            window.location.href = `${REPAIRS_PAGE_BASE}/${repairId}`;
        }
    } catch (error) {
        repairNotify(error.message || 'Unable to create repair request.');
    } finally {
        Components.setLoading(saveBtn, false);
    }
}

async function loadRepairRequests() {
    const params = new URLSearchParams({ per_page: '20' });
    const search = document.getElementById('repair-search').value.trim();
    const status = document.getElementById('repair-status-filter').value;

    if (search) params.set('q', search);
    if (status) params.set('repair_status', status);

    try {
        const { response, data } = await Components.fetchJson(`${REPAIRS_API_BASE}?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load repair requests');
        }

        const repairs = Array.isArray(data.data?.repairs?.data) ? data.data.repairs.data : [];
        const container = document.getElementById('repair-list-container');

        if (repairs.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No repair requests found.</strong><span>Create a new repair request to begin the workflow.</span></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>';
        html += '<th>Repair ID</th><th>Damage Report</th><th>Item</th><th>Technician</th><th>Status</th><th>Cost</th><th>Replacement</th><th>Actions</th>';
        html += '</tr></thead><tbody>';

        repairs.forEach((repair) => {
            html += '<tr>';
            html += `<td><strong>${escapeRepairHtml(repair.repair_code)}</strong></td>`;
            html += `<td>${escapeRepairHtml(repair.damage_report?.damage_report_code || 'N/A')}</td>`;
            html += `<td>${escapeRepairHtml(repair.damage_report?.item?.name || 'Unknown')}</td>`;
            html += `<td>${escapeRepairHtml(repair.technician?.full_name || 'Unassigned')}</td>`;
            html += `<td><span class="badge badge-${String(repair.repair_status || '').toLowerCase().replace(/_/g, '-').replace(/[^a-z0-9-]/g, '')}">${escapeRepairHtml(formatRepairStatus(repair.repair_status))}</span></td>`;
            html += `<td>${escapeRepairHtml(Number(repair.repair_cost || 0).toFixed(2))}</td>`;
            html += `<td>${escapeRepairHtml(repair.replacement_dispatch?.dispatch_code || (repair.replacement_item?.name || '-'))}</td>`;
            html += `<td><a class="btn btn-sm btn-primary" href="${REPAIRS_PAGE_BASE}/${repair.id}">View</a></td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        document.getElementById('repair-list-container').innerHTML = '<div class="ui-empty-state"><strong>Failed to load repair requests.</strong></div>';
        repairNotify(error.message || 'Unable to load repair requests.');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    initRepairCreateSelects();
    document.getElementById('repair-date').value = new Date().toISOString().slice(0, 10);
    await prefillDamageReportFromQuery();
    await loadRepairRequests();

    document.getElementById('repair-create-form').addEventListener('submit', submitRepairCreate);
    document.getElementById('repair-create-reset').addEventListener('click', () => {
        document.getElementById('repair-create-form').reset();
        document.getElementById('repair-damage-id').value = '';
        document.getElementById('repair-tech-id').value = '';
    });
    document.getElementById('repair-search').addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            loadRepairRequests();
        }
    });
    document.getElementById('repair-status-filter').addEventListener('change', loadRepairRequests);
    document.getElementById('repair-filter-clear').addEventListener('click', () => {
        document.getElementById('repair-search').value = '';
        document.getElementById('repair-status-filter').value = '';
        loadRepairRequests();
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
