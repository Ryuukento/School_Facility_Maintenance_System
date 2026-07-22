<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Replacement Request - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;max-width:980px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="replacement-page-title">Replacement Request</h2>
                <p class="text-muted mb-0" id="replacement-page-subtitle">Fulfill replacement after a failed repair using the dispatch and stock transaction flow.</p>
            </div>
            <div class="d-flex gap-sm">
                <a href="<?php echo htmlspecialchars(public_url('/repairs')); ?>" class="btn btn-secondary">Back to Repairs</a>
                <a href="#" id="replacement-view-link" class="btn btn-primary">View Repair</a>
            </div>
        </div>
        <div class="card-body">
            <div id="replacement-summary" style="margin-bottom:12px;"></div>
            <form id="replacement-form">
                <div class="form-group">
                    <label for="replacement-item-search-module">Replacement Item *</label>
                    <input type="text" id="replacement-item-search-module" class="form-control" placeholder="Search replacement stock item..." required>
                    <input type="hidden" id="replacement-item-id-module">
                </div>
                <div class="form-group">
                    <label for="replacement-quantity-module">Replacement Quantity *</label>
                    <input type="number" id="replacement-quantity-module" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label for="replacement-notes">Replacement Notes</label>
                    <textarea id="replacement-notes" class="form-control" rows="4" placeholder="Reference dispatch notes, stock remarks, or completion remarks..."></textarea>
                </div>
                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="replacement-save-btn">Fulfill Replacement</button>
                    <a href="#" id="replacement-cancel-link" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
const REPAIRS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/repairs') : '/api/repairs';
const REPAIRS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/repairs') : '/repairs';

function getReplacementRepairId() {
    return Number(new URLSearchParams(window.location.search).get('id') || 0);
}

function replacementNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    window.alert(message);
}

function initReplacementItemSelect() {
    new Components.SearchableSelect({
        inputId: 'replacement-item-search-module',
        hiddenId: 'replacement-item-id-module',
        endpoint: window.SFMS_PUBLIC_URL('/api/items?item_type=inventory_stock'),
        displayKey: 'name',
    });
}

async function loadReplacementRepair() {
    const id = getReplacementRepairId();
    if (!id) {
        replacementNotify('Invalid repair request ID.', 'warning');
        window.location.href = REPAIRS_PAGE_BASE;
        return;
    }

    document.getElementById('replacement-view-link').href = `${REPAIRS_PAGE_BASE}/${id}`;
    document.getElementById('replacement-cancel-link').href = `${REPAIRS_PAGE_BASE}/${id}`;

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

        document.getElementById('replacement-page-title').textContent = `Replacement for ${repair.repair_code || 'Repair Request'}`;
        document.getElementById('replacement-page-subtitle').textContent = `Damage report: ${repair.damage_report?.damage_report_code || 'N/A'} | Current status: ${repair.repair_status || 'pending'}`;
        document.getElementById('replacement-summary').innerHTML = `<div class="text-muted">Item: <strong>${repair.damage_report?.item?.name || 'Unknown'}</strong> | Technician: <strong>${repair.technician?.full_name || 'Unassigned'}</strong></div>`;

        if (repair.repair_status !== 'failed' && !repair.replacement_dispatch) {
            replacementNotify('Replacement is only available after the repair has been marked as failed.', 'warning');
        }

        if (repair.replacement_item) {
            document.getElementById('replacement-item-id-module').value = String(repair.replacement_item.id);
            document.getElementById('replacement-item-search-module').value = repair.replacement_item.name || '';
        }
        if (repair.replacement_quantity) {
            document.getElementById('replacement-quantity-module').value = String(repair.replacement_quantity);
        }
        document.getElementById('replacement-notes').value = repair.notes || '';
    } catch (error) {
        replacementNotify(error.message || 'Unable to load replacement form.');
    }
}

async function submitReplacement(event) {
    event.preventDefault();

    const id = getReplacementRepairId();
    const replacementItemId = Number(document.getElementById('replacement-item-id-module').value || 0);
    const replacementQuantity = Number(document.getElementById('replacement-quantity-module').value || 0);

    if (!replacementItemId || replacementQuantity <= 0) {
        replacementNotify('Replacement item and quantity are required.', 'warning');
        return;
    }

    const saveBtn = document.getElementById('replacement-save-btn');

    try {
        Components.setLoading(saveBtn, true);
        const { response, data } = await Components.fetchJson(`${REPAIRS_API_BASE}/${id}/replacement`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                replacement_item_id: replacementItemId,
                replacement_quantity: replacementQuantity,
                notes: document.getElementById('replacement-notes').value.trim() || null,
            }),
        });

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to fulfill replacement');
        }

        Components.toast(data.message || 'Replacement fulfilled successfully.', 'success');
        window.location.href = `${REPAIRS_PAGE_BASE}/${id}`;
    } catch (error) {
        replacementNotify(error.message || 'Unable to fulfill replacement.');
    } finally {
        Components.setLoading(saveBtn, false);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initReplacementItemSelect();
    loadReplacementRepair();
    document.getElementById('replacement-form').addEventListener('submit', submitReplacement);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
