<?php
/**
 * Maintenance Report Detail View
 */
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
$reportId = $_GET['id'] ?? 0;

require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();

$currentRole = strtolower(trim((string)($user['role'] ?? ($_SESSION['role'] ?? ''))));
if ($currentRole === 'super admin' || $currentRole === 'superadmin') {
    $currentRole = 'super_admin';
}
$isSuperAdmin = ($currentRole === 'super_admin');

$allowedStatusOptions = [];
if ($currentRole === 'super_admin') {
    $allowedStatusOptions = ['assigned' => 'Assigned'];
} elseif ($currentRole === 'maintenance_admin') {
    $allowedStatusOptions = [
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'closed' => 'Closed'
    ];
} elseif ($currentRole === 'maintenance_staff') {
    $allowedStatusOptions = [
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'closed' => 'Closed'
    ];
}
$canReopenReport = in_array($currentRole, ['super_admin', 'maintenance_admin'], true);

$canAssignUser = in_array($currentRole, ['super_admin', 'maintenance_admin'], true);
$assignmentTargetsByRole = [
    'maintenance_admin' => [],
    'maintenance_staff' => [],
];
$assignmentLabel = 'Assign To';
$assignmentPlaceholder = 'Select assignee...';
$defaultAssignmentRoleFilter = 'maintenance_staff';
$backContext = strtolower(trim((string)($_GET['back'] ?? '')));
$backToReportsUrl = in_array($currentRole, ['super_admin', 'maintenance_admin'], true)
    ? '/School_Facility_Maintenance_System/frontend/pages/reports.php'
    : '/School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php';

if ($backContext === 'all_reports') {
    $backToReportsUrl = '/School_Facility_Maintenance_System/frontend/pages/reports.php';
}

if ($canAssignUser) {
    if ($currentRole === 'maintenance_admin') {
        $assignmentLabel = 'Assign To (Maintenance Staff)';
        $assignmentPlaceholder = 'Select maintenance staff...';
        $stmt = $pdo->query("SELECT user_id, username, full_name, email, designation, COALESCE(NULLIF(full_name, ''), username, designation) as display_name FROM users WHERE role IN ('maintenance_staff') AND status = 'active' ORDER BY full_name, username");
        $assignmentTargetsByRole['maintenance_staff'] = $stmt->fetchAll();
    } else {
        $assignmentLabel = 'Assign To (Maintenance Team)';
        $stmt = $pdo->query("SELECT user_id, username, full_name, email, designation, COALESCE(NULLIF(full_name, ''), username, designation) as display_name FROM users WHERE role IN ('maintenance_admin') AND status = 'active' ORDER BY full_name, username");
        $assignmentTargetsByRole['maintenance_admin'] = $stmt->fetchAll();
        $stmt = $pdo->query("SELECT user_id, username, full_name, email, designation, COALESCE(NULLIF(full_name, ''), username, designation) as display_name FROM users WHERE role IN ('maintenance_staff') AND status = 'active' ORDER BY full_name, username");
        $assignmentTargetsByRole['maintenance_staff'] = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - School Facility Maintenance System</title>
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('sfms_settings_theme') || localStorage.getItem('sfms_theme_mode') || 'dark';
                var mode = (storedTheme === 'light' || storedTheme === 'dark') ? storedTheme : 'dark';
                var fontSizeMode = localStorage.getItem('sfms_settings_font_size') || 'medium';
                var resolved = mode;
                var root = document.documentElement;
                var sizeScaleMap = { small: 0.92, medium: 1, large: 1.12 };
                var safeFontSizeMode = Object.prototype.hasOwnProperty.call(sizeScaleMap, fontSizeMode) ? fontSizeMode : 'medium';
                var safeScale = sizeScaleMap[safeFontSizeMode];

                root.setAttribute('data-theme-mode', mode);
                root.setAttribute('data-theme-resolved', resolved);
                root.setAttribute('data-font-size-mode', safeFontSizeMode);
                root.style.colorScheme = resolved === 'dark' ? 'dark' : 'light';
                root.style.setProperty('--ui-font-scale', String(safeScale));
                root.style.setProperty('--ui-zoom', '1');
            } catch (error) {
                // Keep defaults if localStorage is unavailable.
            }
        })();
    </script>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/light-mode-polish.css">
</head>
<body data-user-role="<?php echo htmlspecialchars($user['role'] ?? ''); ?>">

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container assigned-report-page">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="report-title">Loading...</h2>
                <p class="text-muted mb-0">Report Details</p>
            </div>
            <a href="<?php echo htmlspecialchars($backToReportsUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
                ← Back to Reports
            </a>
        </div>
        
        <div class="card-body" id="report-details">
            <div class="loading">Loading report details...</div>
        </div>
    </div>

    <!-- Status Update Card -->
    <div class="card mt-lg">
        <div class="card-header">
            <h2>Update Status</h2>
        </div>
        <div class="card-body">
            <div id="status-alert"></div>
            <form id="status-update-form">
                <div class="form-group">
                    <label for="new-status">New Status</label>
                    <select id="new-status" required>
                        <option value="">Select new status...</option>
                        <?php foreach ($allowedStatusOptions as $statusValue => $statusLabel): ?>
                            <option value="<?php echo htmlspecialchars($statusValue); ?>" <?php echo $statusValue === 'assigned' ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusLabel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($canAssignUser): ?>
                <div class="form-group" id="assigned-to-group" style="display:none;">
                    <label for="assigned-to"><?php echo htmlspecialchars($assignmentLabel); ?></label>

                    <div style="position:relative;">
                        <input type="text" id="assigned-to-search" class="form-control" placeholder="Type name to search..." autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                        <input type="hidden" id="assigned-to" value="">
                        <div id="assigned-to-selected" style="display:none;margin-top:6px;padding:8px 12px;border-radius:8px;background:rgba(59,130,246,0.12);border:1px solid rgba(96,165,250,0.3);font-size:13px;color:#93c5fd;"></div>
                        <div id="assigned-to-results" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:200;background:#0f1b31;border:1px solid rgba(148,163,184,0.24);border-radius:10px;box-shadow:0 12px 28px rgba(2,6,23,0.4);max-height:220px;overflow-y:auto;margin-top:4px;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group" id="completion-proof-group" style="display:none;">
                    <label for="completion-proof-image">Completion Proof Image</label>
                    <input type="file" id="completion-proof-image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small class="text-muted">Upload a photo showing that the issue has been fixed.</small>
                    <div id="completion-proof-preview" style="margin-top: 10px;"></div>
                </div>

                <div class="form-group">
                    <label for="status-comment">Comment (optional)</label>
                    <textarea id="status-comment" placeholder="Add a comment about this status change..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="update-status-btn">Update Status</button>
            </form>
        </div>
    </div>

    <?php if ($canReopenReport): ?>
    <div class="card mt-lg" id="reopen-report-card" style="display:none;">
        <div class="card-header">
            <h2>Reopen Report</h2>
            <p class="text-muted mb-0">Revert this report back to In Progress for further action.</p>
        </div>
        <div class="card-body">
            <div id="reopen-alert"></div>
            <button type="button" class="btn btn-warning" id="reopen-report-btn">↩ Reopen Report</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mt-lg" id="need-change-approval-card" style="display:none;">
        <div class="card-header">
            <h2>Need Change Approval</h2>
            <p class="text-muted mb-0">Approve or reject replacement request. Stock is deducted only after approval.</p>
        </div>
        <div class="card-body">
            <div id="need-change-approval-meta" class="need-change-approval-meta"></div>
            <div id="need-change-approval-alert"></div>
            <div class="d-flex gap-sm">
                <button type="button" class="btn btn-success" id="approve-need-change-btn">✓ Approve Request</button>
                <button type="button" class="btn btn-danger" id="reject-need-change-btn">✕ Reject Request</button>
            </div>
        </div>
    </div>

</main>

<div id="system-confirm-modal" class="system-confirm-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="system-confirm-title" style="display:none;">
    <div class="system-confirm-backdrop" data-close="1"></div>
    <div class="system-confirm-panel">
        <h3 id="system-confirm-title">Confirm Action</h3>
        <p id="system-confirm-message"></p>
        <div class="system-confirm-actions">
            <button type="button" class="btn btn-secondary" id="system-confirm-cancel">Cancel</button>
            <button type="button" class="btn btn-primary" id="system-confirm-ok">OK</button>
        </div>
    </div>
</div>

<style>
.system-confirm-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
}

.system-confirm-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.62);
}

.system-confirm-panel {
    position: relative;
    width: min(92vw, 500px);
    margin: 14vh auto 0;
    background: var(--card-color, #111827);
    border: 1px solid var(--border, #2a3342);
    border-radius: 14px;
    box-shadow: 0 22px 60px rgba(0, 0, 0, 0.5);
    padding: 18px;
}

.system-confirm-panel h3 {
    margin: 0 0 10px;
    color: var(--text-light, #f3f4f6);
    font-size: 20px;
}

.system-confirm-panel p {
    margin: 0;
    color: var(--text-muted, #c8cfda);
    line-height: 1.55;
    white-space: pre-line;
}

.system-confirm-actions {
    margin-top: 18px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.assignment-role-switcher {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.assignment-role-switcher {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.assignment-role-btn {
    flex: 1;
    border: 2px solid rgba(148, 163, 184, 0.25);
    background: rgba(15, 23, 42, 0.72);
    color: #94a3b8;
    border-radius: 10px;
    padding: 10px 14px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s ease;
    position: relative;
}

.assignment-role-btn:hover:not(.is-active) {
    border-color: rgba(148, 163, 184, 0.5);
    color: #e2e8f0;
    background: rgba(30, 41, 59, 0.9);
}

.assignment-role-btn.is-active {
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    border-color: #a855f7;
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(124, 58, 237, 0.4);
}

.assignment-role-btn.is-active::after {
    content: ' ✓';
}
</style>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-report-detail.inline.css">

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
const reportId = <?php echo intval($reportId); ?>;
const currentUserRole = <?php echo json_encode($currentRole); ?>;
const isSuperAdminUser = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;
const assignmentTargetsByRole = <?php echo json_encode($assignmentTargetsByRole, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const defaultAssignmentRoleFilter = <?php echo json_encode($defaultAssignmentRoleFilter); ?>;
const assignmentPlaceholder = <?php echo json_encode($assignmentPlaceholder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
let currentReport = null;
let activeAssignmentRole = defaultAssignmentRoleFilter;

window.UI = window.UI || {
    getPriorityBadge(priority) {
        const colors = {
            'low': 'badge-info',
            'medium': 'badge-warning',
            'high': 'badge-danger',
            'urgent': 'badge-danger',
            'critical': 'badge-danger'
        };
        return colors[priority] || 'badge-info';
    },

    getStatusBadge(status) {
        const colors = {
            'submitted': 'badge-info',
            'assigned': 'badge-warning',
            'in_progress': 'badge-warning',
            'completed': 'badge-success',
            'closed': 'badge-success',
            'draft': 'badge-info'
        };
        return colors[status] || 'badge-info';
    },

    formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
};

function renderPriorityBadge(priority) {
    const safePriority = String(priority || '').trim().toLowerCase();
    const fallbackLabel = safePriority ? safePriority.toUpperCase() : 'N/A';

    if (!window.UI || typeof window.UI.getPriorityBadge !== 'function') {
        return `<span class="badge badge-info">${fallbackLabel}</span>`;
    }

    const raw = window.UI.getPriorityBadge(safePriority);
    const rawText = String(raw || '').trim();

    // Support both helper styles:
    // 1) returns full HTML markup: <span class="badge ...">TEXT</span>
    // 2) returns only class name: badge-danger
    if (rawText.toLowerCase().includes('<span')) {
        return rawText;
    }

    return `<span class="badge ${rawText || 'badge-info'}">${fallbackLabel}</span>`;
}

function renderStatusBadge(status) {
    const safeStatus = String(status || '').trim().toLowerCase();
    const fallbackLabel = safeStatus ? safeStatus.replace(/_/g, ' ').toUpperCase() : 'N/A';

    if (!window.UI || typeof window.UI.getStatusBadge !== 'function') {
        return `<span class="badge badge-info">${fallbackLabel}</span>`;
    }

    const raw = window.UI.getStatusBadge(safeStatus);
    const rawText = String(raw || '').trim();

    if (rawText.toLowerCase().includes('<span')) {
        return rawText;
    }

    return `<span class="badge ${rawText || 'badge-info'}">${fallbackLabel}</span>`;
}

function syncAssignedToVisibility() {
    const group = document.getElementById('assigned-to-group');
    const hiddenInput = document.getElementById('assigned-to');
    const statusSelect = document.getElementById('new-status');
    if (!group || !hiddenInput || !statusSelect) return;

    if (statusSelect.value === 'assigned') {
        group.style.display = 'block';
        renderAssignmentOptions(hiddenInput.value || (currentReport && currentReport.assigned_to ? String(currentReport.assigned_to) : ''));
    } else {
        group.style.display = 'none';
        hiddenInput.value = '';
        const si = document.getElementById('assigned-to-search');
        const se = document.getElementById('assigned-to-selected');
        const re = document.getElementById('assigned-to-results');
        if (si) si.value = '';
        if (se) { se.style.display = 'none'; se.textContent = ''; }
        if (re) { re.style.display = 'none'; re.innerHTML = ''; }
    }
}

function getAssignmentRoleLabel(roleKey) {
    if (roleKey === 'maintenance_admin') return 'Maintenance Admin';
    return 'Maintenance Staff';
}

function getAssignmentPrimaryLabel(person) {
    return String(person?.full_name || person?.username || person?.display_name || 'Unknown').trim();
}

function getAssignmentSecondaryLabel(person) {
    const details = [];
    const username = String(person?.username || '').trim();
    const designation = String(person?.designation || '').trim();
    const email = String(person?.email || '').trim();

    if (username) details.push('@' + username);
    if (designation) details.push(designation);
    if (email) details.push(email);

    return details.join(' • ');
}

function getAssignmentSearchBlob(person) {
    return [
        person?.full_name,
        person?.username,
        person?.display_name,
        person?.designation,
        person?.email,
    ].join(' ').toLowerCase();
}

function renderAssignmentOptions(selectedValue = '') {
    const searchInput = document.getElementById('assigned-to-search');
    const hiddenInput = document.getElementById('assigned-to');
    const resultsEl = document.getElementById('assigned-to-results');
    const selectedEl = document.getElementById('assigned-to-selected');
    const roleButtons = document.querySelectorAll('.assignment-role-btn');
    if (!hiddenInput || !resultsEl) return;

    // Update active role button UI
    roleButtons.forEach((btn) => {
        btn.classList.toggle('is-active', btn.getAttribute('data-assignment-role') === activeAssignmentRole);
    });

    // If a selectedValue is passed (e.g. pre-existing assignment), show it
    if (selectedValue) {
        const allTargets = Object.values(assignmentTargetsByRole).flat();
        const match = allTargets.find((p) => String(p.user_id) === String(selectedValue));
        if (match) {
            hiddenInput.value = String(match.user_id);
            if (searchInput) searchInput.value = getAssignmentPrimaryLabel(match);
            if (selectedEl) {
                selectedEl.style.display = 'block';
                selectedEl.textContent = 'Assigned: ' + getAssignmentPrimaryLabel(match);
            }
        }
    }
}

function filterAssignmentResults() {
    const searchInput = document.getElementById('assigned-to-search');
    const resultsEl = document.getElementById('assigned-to-results');
    const hiddenInput = document.getElementById('assigned-to');
    if (!searchInput || !resultsEl) return;

    const keyword = searchInput.value.trim().toLowerCase();
    if (!keyword) {
        resultsEl.style.display = 'none';
        resultsEl.innerHTML = '';
        return;
    }

    // Search across all roles
    const allTargets = Object.values(assignmentTargetsByRole).flat();
    const filtered = allTargets.filter((p) => getAssignmentSearchBlob(p).includes(keyword));

    if (!filtered.length) {
        resultsEl.innerHTML = '<div style="padding:10px 14px;color:#94a3b8;font-size:13px;">No matching personnel</div>';
        resultsEl.style.display = 'block';
        return;
    }

    const currentVal = hiddenInput ? hiddenInput.value : '';
    resultsEl.innerHTML = filtered.map((p) => {
        const isActive = String(p.user_id) === String(currentVal);
        const primary = getAssignmentPrimaryLabel(p);
        const secondary = getAssignmentSecondaryLabel(p);
        return `<button type="button" data-person-id="${p.user_id}" style="display:block;width:100%;text-align:left;padding:10px 14px;background:${isActive ? 'rgba(59,130,246,0.18)' : 'none'};border:none;color:${isActive ? '#93c5fd' : '#dbe7fb'};font-size:14px;cursor:pointer;transition:background 0.15s;" onmouseover="this.style.background='rgba(148,163,184,0.12)'" onmouseout="this.style.background='${isActive ? 'rgba(59,130,246,0.18)' : 'none'}'"><div style="font-weight:600;">${primary}</div>${secondary ? `<div style="margin-top:2px;color:#94a3b8;font-size:12px;">${secondary}</div>` : ''}</button>`;
    }).join('');
    resultsEl.style.display = 'block';
}

function syncCompletionProofVisibility() {
    const statusSelect = document.getElementById('new-status');
    const proofGroup = document.getElementById('completion-proof-group');
    const proofInput = document.getElementById('completion-proof-image');
    const proofPreview = document.getElementById('completion-proof-preview');

    if (!statusSelect || !proofGroup || !proofInput || !proofPreview) {
        return;
    }

    const isCompleted = statusSelect.value === 'completed';
    proofGroup.style.display = isCompleted ? 'block' : 'none';

    if (!isCompleted) {
        proofInput.required = false;
        proofInput.value = '';
        proofPreview.innerHTML = '';
        return;
    }

    const hasExistingProof = Boolean(currentReport && currentReport.completion_proof_image);
    proofInput.required = !hasExistingProof;

    if (hasExistingProof) {
        const safeUrl = String(currentReport.completion_proof_image);
        proofPreview.innerHTML = `
            <div class="text-muted" style="margin-bottom: 6px;">Current proof image:</div>
            <a href="${safeUrl}" target="_blank" rel="noopener">
                <img src="${safeUrl}" alt="Completion proof" style="max-width: 240px; width: 100%; border-radius: 8px; border: 1px solid rgba(148, 163, 184, 0.25);">
            </a>
        `;
    } else {
        proofPreview.innerHTML = '<div class="text-muted">No proof image uploaded yet.</div>';
    }
}

function showSystemConfirm(options = {}) {
    const modal = document.getElementById('system-confirm-modal');
    const title = document.getElementById('system-confirm-title');
    const message = document.getElementById('system-confirm-message');
    const okBtn = document.getElementById('system-confirm-ok');
    const cancelBtn = document.getElementById('system-confirm-cancel');
    const backdrop = modal?.querySelector('[data-close="1"]');

    if (!modal || !title || !message || !okBtn || !cancelBtn || !backdrop) {
        return Promise.resolve(window.confirm(String(options.message || 'Proceed?')));
    }

    title.textContent = String(options.title || 'Confirm Action');
    message.textContent = String(options.message || 'Proceed?');
    okBtn.textContent = String(options.confirmText || 'OK');
    cancelBtn.textContent = String(options.cancelText || 'Cancel');
    okBtn.className = String(options.confirmClass || 'btn btn-primary');

    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');

    return new Promise((resolve) => {
        let done = false;
        const close = (result) => {
            if (done) return;
            done = true;
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            backdrop.removeEventListener('click', onCancel);
            document.removeEventListener('keydown', onKeyDown);
            resolve(result);
        };

        const onOk = () => close(true);
        const onCancel = () => close(false);
        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                close(false);
            }
        };

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        backdrop.addEventListener('click', onCancel);
        document.addEventListener('keydown', onKeyDown);
        okBtn.focus();
    });
}

function updateNeedChangeApprovalUI(report) {
    const approvalCard = document.getElementById('need-change-approval-card');
    const approvalMeta = document.getElementById('need-change-approval-meta');
    const approvalAlert = document.getElementById('need-change-approval-alert');
    const approvalBtn = document.getElementById('approve-need-change-btn');
    const rejectBtn = document.getElementById('reject-need-change-btn');

    if (!approvalCard || !approvalMeta || !approvalBtn) {
        return;
    }

    if (!isSuperAdminUser) {
        approvalCard.style.display = 'none';
        return;
    }

    if (!(report.need_change_item_id || report.need_change_item_name)) {
        approvalCard.style.display = 'block';
        approvalMeta.innerHTML = '<div><strong>Need Change Status:</strong> No replacement request for this report.</div>';
        approvalBtn.disabled = true;
        approvalBtn.textContent = 'Approve';
        if (rejectBtn) {
            rejectBtn.disabled = true;
        }
        return;
    }

    const status = String(report.need_change_status || 'pending').toLowerCase();
    const isApproved = Boolean(report.need_change_deducted_at) || status === 'deducted' || status === 'approved';
    approvalCard.style.display = 'block';
    approvalAlert.innerHTML = '';

    approvalMeta.innerHTML = `
        <div><strong>Replacement Item:</strong> ${report.need_change_item_name || ('Item #' + report.need_change_item_id)}</div>
        <div><strong>Current Need Change Status:</strong> ${status.replace(/_/g, ' ').toUpperCase()}</div>
        ${report.need_change_deducted_at ? `<div><strong>Deducted At:</strong> ${report.need_change_deducted_at}</div>` : ''}
    `;

    if (isApproved) {
        approvalBtn.disabled = true;
        approvalBtn.textContent = 'Already Approved';
        if (rejectBtn) {
            rejectBtn.disabled = true;
        }
    } else {
        approvalBtn.disabled = false;
        approvalBtn.textContent = 'Approve Need Change';
        if (rejectBtn) {
            rejectBtn.disabled = false;
        }
    }
}


// Load report details
async function loadReport() {
    if (!reportId) {
        document.getElementById('report-details').innerHTML = 
            '<p class="text-danger">Invalid report ID</p>';
        return;
    }
    
    try {
        const response = await fetch(
            window.SFMS_PUBLIC_URL(`/api/reports/${reportId}`),
            { credentials: 'include' }
        );
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load report');
        }
        
        const report = data.data.report;        console.log('📋 Report loaded from API:', report);
        console.log('Need Change Item ID:', report.need_change_item_id);
        console.log('Need Change Item Name:', report.need_change_item_name);        currentReport = report;
        document.getElementById('report-title').textContent = `#${report.report_id} - ${report.title}`;
        
        const sectionWrapStyle = 'margin-top: 20px;';
        const sectionTitleStyle = 'margin-bottom: 15px; font-weight: 600; color: var(--text-light);';
        const tableStyle = 'border-collapse: collapse; width: 100%;';
        const thStyle = 'width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: var(--text-light); background: var(--muted-card); border-bottom: 1px solid var(--border);';
        const thLastStyle = 'width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: var(--text-light); background: var(--muted-card);';
        const tdStyle = 'padding: 12px 8px; color: var(--text-light); background: var(--card-color); border-bottom: 1px solid var(--border);';
        const tdLastStyle = 'padding: 12px 8px; color: var(--text-light); background: var(--card-color);';
        const detailBoxStyle = 'background: var(--muted-card); padding: 15px; border-radius: 6px; border-left: 4px solid var(--primary-color); line-height: 1.6; color: var(--text-light);';

        let html = '<div style="display: grid; gap: 20px;">';
        
        // Basic Info
        html += '<div>';
        html += `<h3 style="${sectionTitleStyle}">Basic Information</h3>`;
        html += `<table class="table" style="${tableStyle}">`;
        html += `<tr><th style="${thStyle}">Report ID</th><td style="${tdStyle}">#${report.report_id}</td></tr>`;
        html += `<tr><th style="${thStyle}">Title</th><td style="${tdStyle}"><strong>${report.title}</strong></td></tr>`;
        html += `<tr><th style="${thStyle}">Location</th><td style="${tdStyle}">${report.location}</td></tr>`;
        html += `<tr><th style="${thStyle}">Priority</th><td style="${tdStyle}">${renderPriorityBadge(report.priority)}</td></tr>`;
        html += `<tr><th style="${thStyle}">Status</th><td style="${tdStyle}">${renderStatusBadge(report.status)}</td></tr>`;
        if (report.need_change_item_id || report.need_change_item_name) {
            const needChangeStatus = String(report.need_change_status || 'pending').replace(/_/g, ' ').toUpperCase();
            html += `<tr><th style="${thStyle}">Need Change</th><td style="${tdStyle}">Yes</td></tr>`;
            html += `<tr><th style="${thStyle}">Replacement Item</th><td style="${tdStyle}"><strong>${report.need_change_item_name || ('Item #' + report.need_change_item_id)}</strong>${report.need_change_item_quantity ? ` <span class="text-muted">(Stock: ${report.need_change_item_quantity})</span>` : ''}</td></tr>`;
            html += `<tr><th style="${thLastStyle}">Need Change Status</th><td style="${tdLastStyle}">${needChangeStatus}${report.need_change_deducted_at ? ' <span class="badge badge-success">Deducted</span>' : ''}</td></tr>`;
        } else {
            html += `<tr><th style="${thLastStyle}">Need Change</th><td style="${tdLastStyle}">Not requested</td></tr>`;
        }
        html += `<tr><th style="${thLastStyle}">Department</th><td style="${tdLastStyle}">${report.department_name || 'Not assigned'}</td></tr>`;
        html += '</table>';
        html += '</div>';
        
        // Description
        html += `<div style="${sectionWrapStyle}">`;
        html += `<h3 style="${sectionTitleStyle}">Description</h3>`;
        html += `<div style="${detailBoxStyle}">${report.description || 'No description provided'}</div>`;
        html += '</div>';
        
        // People
        html += `<div style="${sectionWrapStyle}">`;
        html += `<h3 style="${sectionTitleStyle}">People</h3>`;
        html += `<table class="table" style="${tableStyle}">`;
        html += `<tr><th style="${thStyle}">Created By</th><td style="${tdStyle}">${report.creator_name} ${report.creator_email ? `(${report.creator_email})` : ''}</td></tr>`;
        html += `<tr><th style="${thLastStyle}">Assigned To</th><td style="${tdLastStyle}">${report.assigned_name ? `${report.assigned_name} ${report.assigned_email ? `(${report.assigned_email})` : ''}` : 'Not assigned yet'}</td></tr>`;
        html += '</table>';
        html += '</div>';
        
        // Dates
        if (report.created_at_full || report.created_at || report.updated_at_formatted || report.updated_at) {
            html += `<div style="${sectionWrapStyle}">`;
            html += `<h3 style="${sectionTitleStyle}">Timeline</h3>`;
            html += `<table class="table" style="${tableStyle}">`;
            if (report.created_at_full || report.created_at) {
                html += `<tr><th style="${thStyle}">Created</th><td style="${tdStyle}">${report.created_at_full || report.created_at}</td></tr>`;
            }
            if (report.updated_at_formatted || report.updated_at) {
                html += `<tr><th style="${thStyle}">Last Updated</th><td style="${tdStyle}">${report.updated_at_formatted || report.updated_at}</td></tr>`;
            }
            if (report.due_date) {
                html += `<tr><th style="${thStyle}">Due Date</th><td style="${tdStyle}">${report.due_date_formatted || report.due_date}</td></tr>`;
            }
            if (report.completed_date) {
                html += `<tr><th style="${thLastStyle}">Completed</th><td style="${tdLastStyle}">${report.completed_date_formatted || report.completed_date}</td></tr>`;
            }
            html += '</table>';
            html += '</div>';
        }

        if (report.completion_proof_image) {
            const proofUrl = String(report.completion_proof_image);
            html += `<div style="${sectionWrapStyle}">`;
            html += `<h3 style="${sectionTitleStyle}">Completion Proof</h3>`;
            html += `<a href="${proofUrl}" target="_blank" rel="noopener">`;
            html += `<img src="${proofUrl}" alt="Completion proof" style="max-width: 360px; width: 100%; border-radius: 10px; border: 1px solid var(--border);">`;
            html += `</a>`;
            html += '</div>';
        }
        
        html += '</div>';
        
        document.getElementById('report-details').innerHTML = html;
        
        // Set current status in form
        document.getElementById('new-status').value = report.status;
        const assignGroup = document.getElementById('assigned-to-group');
        const assignSelect = document.getElementById('assigned-to');
        if (assignGroup && assignSelect) {
            renderAssignmentOptions(report.assigned_to ? String(report.assigned_to) : '');
        }
        syncAssignedToVisibility();
        syncCompletionProofVisibility();
        updateNeedChangeApprovalUI(report);
        updateReopenCardUI(report);
        
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('report-details').innerHTML = 
            '<p class="text-danger">' + error.message + '</p>';
    }
}

// Handle status update
document.getElementById('status-update-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const newStatus = document.getElementById('new-status').value;
    const comment = document.getElementById('status-comment').value;
    const btn = document.getElementById('update-status-btn');
    const alertDiv = document.getElementById('status-alert');
    const completionProofInput = document.getElementById('completion-proof-image');
    
    if (!newStatus) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select a new status</div>';
        return;
    }

    const assignSelect = document.getElementById('assigned-to');
    if (newStatus === 'assigned' && assignSelect && !assignSelect.value) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select an assignee before updating status.</div>';
        return;
    }

    if (newStatus === 'completed' && completionProofInput && !completionProofInput.files.length && !(currentReport && currentReport.completion_proof_image)) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please upload a completion proof image before marking this report as completed.</div>';
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Updating...';
    alertDiv.innerHTML = '';
    
    try {
        const formData = new FormData();
        formData.append('status', newStatus);
        formData.append('comment', comment || '');
        if (newStatus === 'assigned' && assignSelect) {
            formData.append('assigned_to', assignSelect.value || '');
        }
        if (completionProofInput && completionProofInput.files.length > 0) {
            formData.append('completion_proof_image', completionProofInput.files[0]);
        }

        const response = await fetch(
            window.SFMS_PUBLIC_URL(`/api/reports/${reportId}`),
            {
                method: 'PATCH',
                credentials: 'include',
                body: formData
            }
        );

        const data = await response.json();

        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">Status updated successfully!</div>';
            setTimeout(() => {
                loadReport();
                document.getElementById('status-comment').value = '';
                if (completionProofInput) {
                    completionProofInput.value = '';
                }
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to update status');
        }
    } catch (error) {
        alertDiv.innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

document.getElementById('approve-need-change-btn')?.addEventListener('click', async () => {
    const approvalBtn = document.getElementById('approve-need-change-btn');
    const rejectBtn = document.getElementById('reject-need-change-btn');
    const approvalAlert = document.getElementById('need-change-approval-alert');

    if (!currentReport) {
        return;
    }

    const status = String(currentReport.need_change_status || 'pending').toLowerCase();
    const isAlreadyApproved = Boolean(currentReport.need_change_deducted_at) || status === 'deducted' || status === 'approved';
    if (isAlreadyApproved) {
        approvalAlert.innerHTML = '<div class="alert alert-info">Need Change is already approved.</div>';
        return;
    }

    const isConfirmed = await showSystemConfirm({
        title: 'Approve Need Change',
        message: 'Approve this Need Change request?\n\nPress OK to approve and deduct inventory now.\nPress Cancel to stop.',
        confirmText: 'OK',
        cancelText: 'Cancel',
        confirmClass: 'btn btn-success'
    });

    if (!isConfirmed) {
        approvalAlert.innerHTML = '<div class="alert alert-info">Approval cancelled.</div>';
        return;
    }

    const originalText = approvalBtn.textContent;
    approvalBtn.disabled = true;
    rejectBtn.disabled = true;
    approvalBtn.textContent = 'Approving...';
    approvalAlert.innerHTML = '';

    try {
        const response = await fetch(
            window.SFMS_PUBLIC_URL(`/api/reports/${reportId}`),
            {
                method: 'PATCH',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    approve_need_change: true,
                    comment: 'Need Change approved by Admin'
                })
            }
        );

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to approve Need Change');
        }

        approvalAlert.innerHTML = '<div class="alert alert-success">Need Change approved successfully. Inventory updated.</div>';
        loadReport();
    } catch (error) {
        approvalAlert.innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
        approvalBtn.disabled = false;
        rejectBtn.disabled = false;
        approvalBtn.textContent = originalText;
    }
});

document.getElementById('reject-need-change-btn')?.addEventListener('click', async () => {
    const approvalBtn = document.getElementById('approve-need-change-btn');
    const rejectBtn = document.getElementById('reject-need-change-btn');
    const approvalAlert = document.getElementById('need-change-approval-alert');

    if (!currentReport) {
        return;
    }

    const status = String(currentReport.need_change_status || 'pending').toLowerCase();
    const isAlreadyProcessed = Boolean(currentReport.need_change_deducted_at) || status === 'deducted' || status === 'approved' || status === 'rejected';
    if (isAlreadyProcessed) {
        approvalAlert.innerHTML = '<div class="alert alert-info">This Need Change request has already been processed.</div>';
        return;
    }

    const isRejectConfirmed = await showSystemConfirm({
        title: 'Reject Need Change',
        message: 'Reject this Need Change request? The item will not be replaced.',
        confirmText: 'OK',
        cancelText: 'Cancel',
        confirmClass: 'btn btn-danger'
    });

    if (!isRejectConfirmed) {
        return;
    }

    const originalText = rejectBtn.textContent;
    approvalBtn.disabled = true;
    rejectBtn.disabled = true;
    rejectBtn.textContent = 'Rejecting...';
    approvalAlert.innerHTML = '';

    try {
        const response = await fetch(
            window.SFMS_PUBLIC_URL(`/api/reports/${reportId}`),
            {
                method: 'PATCH',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reject_need_change: true,
                    comment: 'Need Change rejected by Admin'
                })
            }
        );

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to reject Need Change');
        }

        approvalAlert.innerHTML = '<div class="alert alert-success">Need Change rejected successfully.</div>';
        loadReport();
    } catch (error) {
        approvalAlert.innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
        approvalBtn.disabled = false;
        rejectBtn.disabled = false;
        rejectBtn.textContent = originalText;
    }
});

function updateReopenCardUI(report) {
    const card = document.getElementById('reopen-report-card');
    if (!card) return;
    const status = String(report.status || '').toLowerCase();
    card.style.display = (status === 'completed' || status === 'closed') ? 'block' : 'none';
}

document.getElementById('reopen-report-btn')?.addEventListener('click', async () => {
    const btn = document.getElementById('reopen-report-btn');
    const alertDiv = document.getElementById('reopen-alert');
    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Reopening...';
    alertDiv.innerHTML = '';
    try {
        const formData = new FormData();
        formData.append('status', 'in_progress');
        formData.append('comment', 'Report reopened');
        const response = await fetch(window.SFMS_PUBLIC_URL(`/api/reports/${reportId}`), { method: 'PATCH', credentials: 'include', body: formData });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to reopen report');
        alertDiv.innerHTML = '<div class="alert alert-success">Report reopened successfully!</div>';
        setTimeout(() => loadReport(), 1500);
    } catch (error) {
        alertDiv.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        btn.disabled = false;
        btn.textContent = original;
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.assignment-role-btn').forEach((button) => {
        button.addEventListener('click', () => {
            activeAssignmentRole = button.dataset.assignmentRole || defaultAssignmentRoleFilter;
            // Clear search and selection when switching role
            const si = document.getElementById('assigned-to-search');
            const hi = document.getElementById('assigned-to');
            const se = document.getElementById('assigned-to-selected');
            const re = document.getElementById('assigned-to-results');
            if (si) si.value = '';
            if (hi) hi.value = '';
            if (se) { se.style.display = 'none'; se.textContent = ''; }
            if (re) { re.style.display = 'none'; re.innerHTML = ''; }
            renderAssignmentOptions('');
        });
    });

    renderAssignmentOptions('');

    // Search input for assign personnel
    const assignSearchInput = document.getElementById('assigned-to-search');
    const assignResultsEl = document.getElementById('assigned-to-results');

    if (assignSearchInput) {
        assignSearchInput.placeholder = assignmentPlaceholder || 'Type name or username to search...';
        assignSearchInput.addEventListener('input', filterAssignmentResults);
        assignSearchInput.addEventListener('focus', filterAssignmentResults);
    }

    if (assignResultsEl) {
        assignResultsEl.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-person-id]');
            if (!btn) return;
            const personId = btn.getAttribute('data-person-id');
            const targets = Object.values(assignmentTargetsByRole).flat();
            const match = targets.find((p) => String(p.user_id) === String(personId));
            if (!match) return;

            const hiddenInput = document.getElementById('assigned-to');
            const selectedEl = document.getElementById('assigned-to-selected');
            if (hiddenInput) hiddenInput.value = String(match.user_id);
            if (assignSearchInput) assignSearchInput.value = getAssignmentPrimaryLabel(match);
            if (selectedEl) {
                selectedEl.style.display = 'block';
                selectedEl.textContent = 'Assigned: ' + getAssignmentPrimaryLabel(match);
            }
            assignResultsEl.style.display = 'none';
            assignResultsEl.innerHTML = '';
        });
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#assigned-to-group')) {
            if (assignResultsEl) assignResultsEl.style.display = 'none';
        }
    });

    loadReport();
});

// Toggle assignment dropdown for assign flow only.
document.getElementById('new-status').addEventListener('change', () => {
    syncAssignedToVisibility();
    syncCompletionProofVisibility();
});
</script>

</body>
</html>


