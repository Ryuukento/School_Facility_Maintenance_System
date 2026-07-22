<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Repair Request Details - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;max-width:1180px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="repair-detail-title">Repair Request</h2>
                <p class="text-muted mb-0" id="repair-detail-subtitle">Loading repair workflow...</p>
            </div>
            <div class="d-flex gap-sm">
                <a href="<?php echo htmlspecialchars(public_url('/repairs')); ?>" class="btn btn-secondary">Back to Repairs</a>
                <a href="#" id="repair-detail-assign-link" class="btn btn-secondary">Assign Technician</a>
                <a href="#" id="repair-detail-update-link" class="btn btn-primary">Update Workflow</a>
                <a href="#" id="repair-detail-replacement-link" class="btn btn-warning">Replacement</a>
            </div>
        </div>
        <div class="card-body" id="repair-detail-container">
            <div class="ui-empty-state"><strong>Loading repair request...</strong></div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <div class="card-header">
            <h3>Repair History</h3>
        </div>
        <div class="card-body" id="repair-history-container">
            <div class="ui-empty-state"><strong>Loading history...</strong></div>
        </div>
    </div>
</main>

<script>
const REPAIRS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/repairs') : '/api/repairs';
const REPAIRS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/repairs') : '/repairs';

function getRepairId() {
    const params = new URLSearchParams(window.location.search);
    const id = Number(params.get('id') || 0);
    return id > 0 ? id : 0;
}

function repairDetailNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    window.alert(message);
}

function escapeRepairDetailHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatRepairDetailStatus(value) {
    return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

async function loadRepairDetail() {
    const id = getRepairId();
    if (!id) {
        repairDetailNotify('Invalid repair request ID.', 'warning');
        window.location.href = REPAIRS_PAGE_BASE;
        return;
    }

    document.getElementById('repair-detail-assign-link').href = `${REPAIRS_PAGE_BASE}/${id}/assign`;
    document.getElementById('repair-detail-update-link').href = `${REPAIRS_PAGE_BASE}/${id}/update`;
    document.getElementById('repair-detail-replacement-link').href = `${REPAIRS_PAGE_BASE}/${id}/replacement`;

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
            throw new Error('Repair payload is invalid.');
        }

        document.getElementById('repair-detail-title').textContent = repair.repair_code || 'Repair Request';
        document.getElementById('repair-detail-subtitle').textContent = `Status: ${formatRepairDetailStatus(repair.repair_status)} | Damage Report: ${repair.damage_report?.damage_report_code || 'N/A'}`;

        const html = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><strong>Damage Report:</strong> ${escapeRepairDetailHtml(repair.damage_report?.damage_report_code || 'N/A')}</div>
                <div><strong>Item:</strong> ${escapeRepairDetailHtml(repair.damage_report?.item?.name || 'Unknown')}</div>
                <div><strong>Room:</strong> ${escapeRepairDetailHtml(repair.damage_report?.room?.name || 'N/A')}</div>
                <div><strong>Department:</strong> ${escapeRepairDetailHtml(repair.damage_report?.department?.name || 'N/A')}</div>
                <div><strong>Technician:</strong> ${escapeRepairDetailHtml(repair.technician?.full_name || 'Unassigned')}</div>
                <div><strong>Repair Type:</strong> ${escapeRepairDetailHtml(formatRepairDetailStatus(repair.repair_type))}</div>
                <div><strong>Repair Date:</strong> ${escapeRepairDetailHtml(repair.repair_date || 'N/A')}</div>
                <div><strong>Estimated Completion:</strong> ${escapeRepairDetailHtml(repair.estimated_completion_date || 'N/A')}</div>
                <div><strong>Completion Date:</strong> ${escapeRepairDetailHtml(repair.completion_date || 'N/A')}</div>
                <div><strong>Repair Cost:</strong> ${escapeRepairDetailHtml(Number(repair.repair_cost || 0).toFixed(2))}</div>
                <div><strong>Replacement Item:</strong> ${escapeRepairDetailHtml(repair.replacement_item?.name || 'Not yet replaced')}</div>
                <div><strong>Replacement Dispatch:</strong> ${repair.replacement_dispatch?.dispatch_code ? `<a href="${window.SFMS_PUBLIC_URL('/dispatches/' + repair.replacement_dispatch.id)}">${escapeRepairDetailHtml(repair.replacement_dispatch.dispatch_code)}</a>` : 'None'}</div>
            </div>
            <div style="margin-top:12px;"><strong>Repair Description:</strong><br>${escapeRepairDetailHtml(repair.repair_description || '')}</div>
            <div style="margin-top:12px;"><strong>Notes:</strong><br>${escapeRepairDetailHtml(repair.notes || 'No notes provided.')}</div>
            <div style="margin-top:12px;"><strong>Failure Reason:</strong><br>${escapeRepairDetailHtml(repair.failure_reason || 'N/A')}</div>
        `;

        document.getElementById('repair-detail-container').innerHTML = html;
    } catch (error) {
        document.getElementById('repair-detail-container').innerHTML = '<div class="ui-empty-state"><strong>Failed to load repair request.</strong></div>';
        repairDetailNotify(error.message || 'Unable to load repair details.');
    }
}

async function loadRepairHistory() {
    const id = getRepairId();
    if (!id) return;

    try {
        const { response, data } = await Components.fetchJson(`${REPAIRS_API_BASE}/${id}/history`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load repair history');
        }

        const histories = Array.isArray(data.data?.histories) ? data.data.histories : [];
        if (!histories.length) {
            document.getElementById('repair-history-container').innerHTML = '<div class="ui-empty-state"><strong>No repair history yet.</strong></div>';
            return;
        }

        let html = '<div style="display:grid;gap:10px;">';
        histories.forEach((entry) => {
            html += '<div style="border:1px solid rgba(148,163,184,.25);border-radius:8px;padding:10px;">';
            html += `<div><strong>${escapeRepairDetailHtml(formatRepairDetailStatus(entry.action_type || 'updated'))}</strong> | ${escapeRepairDetailHtml(entry.created_at ? new Date(entry.created_at).toLocaleString() : 'N/A')}</div>`;
            if (entry.from_status || entry.to_status) {
                html += `<div class="text-muted" style="font-size:13px;">${escapeRepairDetailHtml(formatRepairDetailStatus(entry.from_status || ''))} -> ${escapeRepairDetailHtml(formatRepairDetailStatus(entry.to_status || ''))}</div>`;
            }
            html += `<div style="margin-top:6px;">${escapeRepairDetailHtml(entry.notes || 'No notes')}</div>`;
            html += `<div class="text-muted" style="margin-top:4px;font-size:12px;">Technician: ${escapeRepairDetailHtml(entry.technician?.full_name || 'N/A')} | By: ${escapeRepairDetailHtml(entry.changed_by?.full_name || entry.changedBy?.full_name || 'System')}</div>`;
            html += '</div>';
        });
        html += '</div>';
        document.getElementById('repair-history-container').innerHTML = html;
    } catch (error) {
        document.getElementById('repair-history-container').innerHTML = '<div class="ui-empty-state"><strong>Failed to load repair history.</strong></div>';
        repairDetailNotify(error.message || 'Unable to load repair history.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadRepairDetail();
    loadRepairHistory();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
