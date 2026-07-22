<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Technician Assignment - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;max-width:980px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="assign-page-title">Assign Technician</h2>
                <p class="text-muted mb-0" id="assign-page-subtitle">Assign or reassign a technician for the selected repair request.</p>
            </div>
            <div class="d-flex gap-sm">
                <a href="<?php echo htmlspecialchars(public_url('/repairs')); ?>" class="btn btn-secondary">Back to Repairs</a>
                <a href="#" id="assign-view-link" class="btn btn-primary">View Repair</a>
            </div>
        </div>
        <div class="card-body">
            <div id="assign-summary" style="margin-bottom:12px;"></div>
            <form id="repair-assign-form">
                <div class="form-group">
                    <label for="assign-tech-search">Technician *</label>
                    <input type="text" id="assign-tech-search" class="form-control" placeholder="Search technician..." required>
                    <input type="hidden" id="assign-tech-id">
                </div>
                <div class="form-group">
                    <label for="assign-estimated-date">Estimated Completion Date</label>
                    <input type="date" id="assign-estimated-date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="assign-notes">Assignment Notes</label>
                    <textarea id="assign-notes" class="form-control" rows="4" placeholder="Add assignment notes or reassignment reason..."></textarea>
                </div>
                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="repair-assign-save-btn">Save Assignment</button>
                    <a href="#" id="repair-assign-cancel-link" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
const REPAIRS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/repairs') : '/api/repairs';
const REPAIRS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/repairs') : '/repairs';

function getAssignRepairId() {
    const params = new URLSearchParams(window.location.search);
    return Number(params.get('id') || 0);
}

function assignNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    window.alert(message);
}

function initAssignTechnicianSelect() {
    new Components.SearchableSelect({
        inputId: 'assign-tech-search',
        hiddenId: 'assign-tech-id',
        endpoint: `${REPAIRS_API_BASE}/support/technicians`,
        displayKey: 'full_name',
    });
}

async function loadAssignRepair() {
    const id = getAssignRepairId();
    if (!id) {
        assignNotify('Invalid repair request ID.', 'warning');
        window.location.href = REPAIRS_PAGE_BASE;
        return;
    }

    document.getElementById('assign-view-link').href = `${REPAIRS_PAGE_BASE}/${id}`;
    document.getElementById('repair-assign-cancel-link').href = `${REPAIRS_PAGE_BASE}/${id}`;

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

        document.getElementById('assign-page-title').textContent = `Assign ${repair.repair_code || 'Repair Request'}`;
        document.getElementById('assign-page-subtitle').textContent = `Damage report: ${repair.damage_report?.damage_report_code || 'N/A'}`;
        document.getElementById('assign-summary').innerHTML = `<div class="text-muted">Item: <strong>${repair.damage_report?.item?.name || 'Unknown'}</strong> | Current technician: <strong>${repair.technician?.full_name || 'Unassigned'}</strong></div>`;

        if (repair.technician) {
            document.getElementById('assign-tech-id').value = String(repair.technician.user_id);
            document.getElementById('assign-tech-search').value = repair.technician.full_name || '';
        }
        document.getElementById('assign-estimated-date').value = repair.estimated_completion_date || '';
        document.getElementById('assign-notes').value = repair.notes || '';
    } catch (error) {
        assignNotify(error.message || 'Unable to load assignment form.');
    }
}

async function submitAssignment(event) {
    event.preventDefault();

    const id = getAssignRepairId();
    const technicianId = Number(document.getElementById('assign-tech-id').value || 0);
    if (!technicianId) {
        assignNotify('Please choose a technician.', 'warning');
        return;
    }

    const saveBtn = document.getElementById('repair-assign-save-btn');

    try {
        Components.setLoading(saveBtn, true);
        const { response, data } = await Components.fetchJson(`${REPAIRS_API_BASE}/${id}/assign`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                technician_user_id: technicianId,
                estimated_completion_date: document.getElementById('assign-estimated-date').value || null,
                notes: document.getElementById('assign-notes').value.trim() || null,
            }),
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to assign technician');
        }

        Components.toast(data.message || 'Technician assignment updated.', 'success');
        window.location.href = `${REPAIRS_PAGE_BASE}/${id}`;
    } catch (error) {
        assignNotify(error.message || 'Unable to save technician assignment.');
    } finally {
        Components.setLoading(saveBtn, false);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initAssignTechnicianSelect();
    loadAssignRepair();
    document.getElementById('repair-assign-form').addEventListener('submit', submitAssignment);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
