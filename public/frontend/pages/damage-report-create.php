<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Create Damage Report - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2>Create Damage Report</h2>
                <p class="text-muted mb-0">Report damaged deployed items for review and repair/replacement tracking.</p>
            </div>
            <a href="<?php echo htmlspecialchars(public_url('/damage-reports')); ?>" class="btn btn-secondary">Back to List</a>
        </div>
        <div class="card-body">
            <form id="damage-create-form">
                <div class="form-group">
                    <label>Damage Report ID</label>
                    <input type="text" class="form-control" value="Auto-generated upon submit" readonly>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label for="damage-item-search">Item *</label>
                        <input type="text" id="damage-item-search" class="form-control" placeholder="Search deployed item..." required>
                        <input type="hidden" id="damage-item-id">
                    </div>

                    <div class="form-group">
                        <label for="damage-room-search">Room / Laboratory *</label>
                        <input type="text" id="damage-room-search" class="form-control" placeholder="Search room..." required>
                        <input type="hidden" id="damage-room-id">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label for="damage-dept-search">Department *</label>
                        <input type="text" id="damage-dept-search" class="form-control" placeholder="Search department..." required>
                        <input type="hidden" id="damage-dept-id">
                    </div>
                    <div class="form-group">
                        <label for="damage-severity">Severity Level *</label>
                        <select id="damage-severity" class="form-control" required>
                            <option value="">Select severity</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="damage-description">Damage Description *</label>
                    <textarea id="damage-description" class="form-control" rows="4" placeholder="Describe the damage in detail..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="damage-image">Image Upload</label>
                    <input type="file" id="damage-image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small class="text-muted">Allowed: JPG, PNG, WEBP, GIF (max 5MB)</small>
                    <div id="damage-image-preview" style="margin-top:10px;"></div>
                </div>

                <div class="form-group">
                    <label for="damage-repair-notes">Repair Notes (optional)</label>
                    <textarea id="damage-repair-notes" class="form-control" rows="3" placeholder="Initial notes or findings..."></textarea>
                </div>

                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="damage-save-btn">Submit Damage Report</button>
                    <a href="<?php echo htmlspecialchars(public_url('/damage-reports')); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
const DAMAGE_REPORTS_API_BASE = '/api/damage-reports';
const DAMAGE_REPORTS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/damage-reports') : '/damage-reports';

function damageCreateNotify(message, type = 'danger') {
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

function initDamageCreateSelects() {
    new Components.SearchableSelect({
        inputId: 'damage-item-search',
        hiddenId: 'damage-item-id',
        endpoint: '/api/items?per_page=200&item_type=room_asset',
        displayKey: 'name',
    });

    new Components.SearchableSelect({
        inputId: 'damage-room-search',
        hiddenId: 'damage-room-id',
        endpoint: '/api/rooms',
        displayKey: 'name',
    });

    new Components.SearchableSelect({
        inputId: 'damage-dept-search',
        hiddenId: 'damage-dept-id',
        endpoint: '/api/departments',
        displayKey: 'name',
    });
}

function setupImagePreview() {
    const input = document.getElementById('damage-image');
    const preview = document.getElementById('damage-image-preview');

    input.addEventListener('change', () => {
        preview.innerHTML = '';
        const file = input.files && input.files[0] ? input.files[0] : null;
        if (!file) return;

        const maxBytes = 5 * 1024 * 1024;
        if (file.size > maxBytes) {
            damageCreateNotify('Image must be 5MB or smaller.', 'warning');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            preview.innerHTML = `<img src="${reader.result}" alt="Damage preview" style="max-width:280px;border:1px solid rgba(148,163,184,.3);border-radius:8px;">`;
        };
        reader.readAsDataURL(file);
    });
}

async function submitDamageReport(event) {
    event.preventDefault();

    const saveBtn = document.getElementById('damage-save-btn');
    const itemId = Number(document.getElementById('damage-item-id').value || 0);
    const roomId = Number(document.getElementById('damage-room-id').value || 0);
    const deptId = Number(document.getElementById('damage-dept-id').value || 0);
    const severity = document.getElementById('damage-severity').value;
    const description = document.getElementById('damage-description').value.trim();
    const repairNotes = document.getElementById('damage-repair-notes').value.trim();
    const imageFile = document.getElementById('damage-image').files?.[0] || null;

    const missing = [];
    if (!itemId)             missing.push('Item');
    if (!roomId)             missing.push('Room / Laboratory');
    if (!deptId)             missing.push('Department');
    if (!severity)           missing.push('Severity Level');
    if (!description.trim()) missing.push('Damage Description');
    if (missing.length > 0) {
        damageCreateNotify('Please fill in: ' + missing.join(', '), 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('item_id', String(itemId));
    formData.append('room_id', String(roomId));
    formData.append('department_id', String(deptId));
    formData.append('severity_level', severity);
    formData.append('damage_description', description);
    if (repairNotes) formData.append('repair_notes', repairNotes);
    if (imageFile) formData.append('damage_image', imageFile);

    try {
        Components.setLoading(saveBtn, true);

        const { response, data: payload } = await Components.fetchJson(DAMAGE_REPORTS_API_BASE, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            body: formData,
        });
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to create damage report');
        }

        Components.toast(payload.message || 'Damage report created successfully.', 'success');
        const reportId = payload.data?.damage_report_id;
        if (reportId) {
            window.location.href = `${DAMAGE_REPORTS_PAGE_BASE}/${reportId}`;
            return;
        }
        window.location.href = DAMAGE_REPORTS_PAGE_BASE;
    } catch (error) {
        damageCreateNotify(error.message || 'Unable to create damage report.');
    } finally {
        Components.setLoading(saveBtn, false);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initDamageCreateSelects();
    setupImagePreview();
    document.getElementById('damage-create-form').addEventListener('submit', submitDamageReport);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
