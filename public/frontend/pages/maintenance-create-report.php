<?php
/**
 * Create/Edit Maintenance Report
 */
session_start();

// Check if user is logged in and has permission
if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
if (($user['role'] ?? '') !== 'maintenance_staff') {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php');
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();

$reportId = $_GET['id'] ?? null;
$report = null;

// If editing, fetch existing report
if ($reportId) {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_reports WHERE report_id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
    
    if (!$report || $report['created_by'] != $user['user_id']) {
        if ($user['role'] !== 'super_admin') {
            header('Location: /School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $report ? 'Edit' : 'Create'; ?> Report - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container maintenance-create-report-page" style="margin-top: 20px;">
    <div class="card">
        <div class="card-header">
            <h2><?php echo $report ? 'Edit' : 'Create'; ?> Maintenance Report</h2>
            <p class="text-muted mb-0">Fill in the details below to <?php echo $report ? 'update' : 'create'; ?> a maintenance report</p>
        </div>
        
        <div class="card-body">
            <div id="alert-container"></div>
            
            <form id="report-form">
                <div class="form-group">
                    <label for="title">Report Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        placeholder="e.g., Broken Air Conditioning Unit"
                        value="<?php echo htmlspecialchars($report['title'] ?? ''); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="location">Location *</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        placeholder="e.g., Building A - Room 101"
                        value="<?php echo htmlspecialchars($report['location'] ?? ''); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select id="priority" name="priority" required>
                        <option value="low" <?php echo ($report && $report['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo ($report && $report['priority'] == 'medium') ? 'selected' : ($report ? '' : 'selected'); ?>>Medium</option>
                        <option value="high" <?php echo ($report && $report['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                        <option value="critical" <?php echo ($report && $report['priority'] == 'critical') ? 'selected' : ''; ?>>Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Please provide detailed description of the issue..."
                        required><?php echo htmlspecialchars($report['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group need-change-group">
                    <div class="need-change-panel">
                        <label for="need-change-toggle" class="need-change-toggle-label">
                            <input type="checkbox" id="need-change-toggle" <?php echo (!empty($report['need_change_item_id'])) ? 'checked' : ''; ?>>
                            <span class="need-change-toggle-text">
                                <strong>Needs Replacement Item</strong>
                                <small>Enable this only if the issue requires inventory replacement.</small>
                            </span>
                        </label>

                        <div id="need-change-wrap" class="need-change-wrap" style="display:<?php echo (!empty($report['need_change_item_id'])) ? 'block' : 'none'; ?>;">
                            <label for="need-change-item" class="need-change-item-label">Replacement Inventory Item</label>
                            <input type="text" id="need-change-search" class="form-control need-change-search" placeholder="Search inventory item...">
                            <input type="hidden" id="need-change-item" value="">
                            <div id="need-change-selected" class="need-change-selected" style="display:none;"></div>
                            <div id="need-change-results" class="need-change-results">
                                <div class="need-change-empty">Loading inventory items...</div>
                            </div>
                            <small class="text-muted d-block need-change-note">Stock will only be deducted after Super Admin approval.</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <?php echo $report ? 'Update' : 'Create'; ?> Report
                    </button>
                    <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-create-report.inline.css">

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
const reportId = <?php echo json_encode($reportId); ?>;
const existingNeedChangeItemId = <?php echo json_encode($report['need_change_item_id'] ?? null); ?>;
let needChangeItemsCache = [];
let selectedNeedChangeItem = null;

document.getElementById('report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submit-btn');
    const alertContainer = document.getElementById('alert-container');
    
    const formData = {
        title: document.getElementById('title').value.trim(),
        location: document.getElementById('location').value.trim(),
        priority: document.getElementById('priority').value,
        description: document.getElementById('description').value.trim(),
        need_change_item_id: null
    };

    const needChangeToggle = document.getElementById('need-change-toggle');
    const needChangeSelect = document.getElementById('need-change-item');

    if (needChangeToggle?.checked) {
        if (!needChangeSelect?.value) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Please select a replacement inventory item.</div>';
            return;
        }

        formData.need_change_item_id = Number(needChangeSelect.value);
    }
    
    console.log('📋 Form Data being sent:', formData);
    
    if (!formData.title || !formData.location || !formData.description) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Please fill in all required fields</div>';
        return;
    }
    
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = 'Saving...';
    submitBtn.disabled = true;
    alertContainer.innerHTML = '';
    
    try {
        const action = reportId ? 'update' : 'create';
        const url = reportId 
            ? `/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php?action=update&id=${reportId}`
            : `/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php?action=create`;
        
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            const successMessage = reportId
                ? 'Report updated successfully!'
                : 'Report created successfully! Na-notify na via email ang Super Admin.';
            alertContainer.innerHTML = '<div class="alert alert-success">' + successMessage + '</div>';
            
            setTimeout(() => {
                const newId = result.data.report_id || reportId;
                window.location.href = `/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=${newId}`;
            }, 1000);
        } else {
            throw new Error(result.message || 'Failed to save report');
        }
    } catch (error) {
        console.error('Error:', error);
        alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

async function loadNeedChangeItems() {
    const hiddenInput = document.getElementById('need-change-item');
    const results = document.getElementById('need-change-results');
    if (!hiddenInput || !results) return;

    try {
        const response = await fetch('/School_Facility_Maintenance_System/backend/api/items.php?action=list', {
            credentials: 'include'
        });
        const result = await response.json();

        if (!result.success || !Array.isArray(result.items)) {
            throw new Error(result.message || 'Failed to load inventory items');
        }

        needChangeItemsCache = result.items.filter((item) => Number(item.quantity || 0) > 0);
        const existingItem = needChangeItemsCache.find((item) => String(item.id) === String(existingNeedChangeItemId || ''));
        if (existingItem) {
            updateNeedChangeSelection(existingItem);
        } else {
            renderNeedChangeOptions();
        }
    } catch (error) {
        results.innerHTML = '<div class="need-change-empty">Unable to load items</div>';
        console.error('Failed to load need change items:', error);
    }
}

function renderNeedChangeOptions() {
    const searchInput = document.getElementById('need-change-search');
    const results = document.getElementById('need-change-results');
    if (!results) return;

    const keyword = String(searchInput?.value || '').trim().toLowerCase();
    const filteredItems = needChangeItemsCache.filter((item) => {
        if (!keyword) {
            return true;
        }

        return String(item.name || '').toLowerCase().includes(keyword);
    });

    if (!filteredItems.length) {
        results.innerHTML = '<div class="need-change-empty">No matching inventory items</div>';
        return;
    }

    results.innerHTML = filteredItems.map((item) => {
        const isActive = selectedNeedChangeItem && String(selectedNeedChangeItem.id) === String(item.id);
        return `<button type="button" class="need-change-result-item${isActive ? ' active' : ''}" data-item-id="${item.id}">${item.name} <span>(Stock: ${item.quantity})</span></button>`;
    }).join('');
}

function updateNeedChangeSelection(item) {
    const hiddenInput = document.getElementById('need-change-item');
    const selectedDisplay = document.getElementById('need-change-selected');
    const searchInput = document.getElementById('need-change-search');
    if (!hiddenInput || !selectedDisplay) return;

    selectedNeedChangeItem = item || null;
    hiddenInput.value = item ? String(item.id) : '';

    if (item) {
        selectedDisplay.style.display = 'block';
        selectedDisplay.textContent = `Selected: ${item.name} (Stock: ${item.quantity})`;
        if (searchInput) {
            searchInput.value = item.name || '';
        }
    } else {
        selectedDisplay.style.display = 'none';
        selectedDisplay.textContent = '';
    }

    renderNeedChangeOptions();
}

document.getElementById('need-change-toggle')?.addEventListener('change', (event) => {
    const wrap = document.getElementById('need-change-wrap');
    if (wrap) {
        wrap.style.display = event.target.checked ? 'block' : 'none';
    }
});

document.getElementById('need-change-search')?.addEventListener('input', renderNeedChangeOptions);

document.getElementById('need-change-results')?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-item-id]');
    if (!button) return;

    const itemId = String(button.getAttribute('data-item-id') || '');
    const matchedItem = needChangeItemsCache.find((item) => String(item.id) === itemId);
    if (!matchedItem) return;

    updateNeedChangeSelection(matchedItem);
});

loadNeedChangeItems();
</script>

</body>
</html>


