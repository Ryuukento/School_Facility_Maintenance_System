<?php
/**
 * Create New Maintenance Report
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';

// Establish database connection
$pdo = getDBConnection();

$user = $_SESSION['user'];
$pageTitle = 'Create Report - SFMS';
include __DIR__ . '/../includes/header.php';
?>
<main class="container create-report-page" style="margin-top: 20px;">
    <div class="card create-report-card">
        <div class="card-header">
            <h2>Create New Maintenance Report</h2>
            <p class="text-muted mb-0">Submit a new facility maintenance request</p>
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
                        required>
                </div>
                
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        placeholder="e.g., Building A - Room 101"
                        required>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select id="priority" name="priority" required>
                        <option value="low">Low - Can wait</option>
                        <option value="medium" selected>Medium - Normal</option>
                        <option value="high">High - Important</option>
                        <option value="critical">Critical - Immediate</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Please provide detailed description of the issue..."
                        required></textarea>
                </div>

                <div class="form-group need-change-group">
                    <div class="need-change-panel">
                        <label for="need-change-toggle" class="need-change-toggle-label">
                            <input type="checkbox" id="need-change-toggle">
                            <span class="need-change-toggle-text">
                                <strong>Needs Replacement Item</strong>
                                <small>Enable this only if the issue requires inventory replacement.</small>
                            </span>
                        </label>
                        <div id="need-change-wrap" class="need-change-wrap" style="display:none;">
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
                        Submit Report
                    </button>
                    <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/create-report.inline.css">

<script>
// Ensure API and Session are defined globally
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/backend/api',
    async createReport(data) {
        const response = await fetch(`${this.baseURL}/reports.php?action=create`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const raw = await response.text();
        let result;

        try {
            result = JSON.parse(raw);
        } catch (parseError) {
            const preview = raw.slice(0, 180).replace(/\s+/g, ' ').trim();
            throw new Error(`Server returned invalid response. ${preview || 'No response body received.'}`);
        }

        if (!result.success) throw new Error(result.message || 'Failed to create report');
        return result;
    },
    async logout() {
        const response = await fetch(`${this.baseURL}/auth.php?action=logout`);
        const data = await response.json();
        return data;
    }
};

let needChangeItemsCache = [];
let selectedNeedChangeItem = null;

window.Session = window.Session || {
    get(key) { 
        const v = localStorage.getItem(key);
        return v ? JSON.parse(v) : null;
    },
    set(key, value) { localStorage.setItem(key, JSON.stringify(value)); },
    clear() { localStorage.clear(); }
};

document.getElementById('report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submit-btn');
    const alertContainer = document.getElementById('alert-container');
    
    // Get form data
    const formData = {
        title: document.getElementById('title').value.trim(),
        location: document.getElementById('location').value.trim(),
        priority: document.getElementById('priority').value,
        description: document.getElementById('description').value.trim(),
        need_change_item_id: null
    };

    const needChangeToggle = document.getElementById('need-change-toggle');
    const needChangeWrap = document.getElementById('need-change-wrap');
    const needChangeSelect = document.getElementById('need-change-item');

    if (needChangeToggle?.checked) {
        if (!needChangeSelect?.value) {
            alertContainer.innerHTML = '<div class="alert alert-danger">Please select a replacement inventory item.</div>';
            return;
        }

        formData.need_change_item_id = Number(needChangeSelect.value);
    }
    
    // Validate
    if (!formData.title || !formData.location || !formData.description) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Please fill in all required fields</div>';
        return;
    }
    
    // Show loading
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = 'Submitting...';
    submitBtn.disabled = true;
    alertContainer.innerHTML = '';
    
    try {
        const response = await window.API.createReport(formData);
        
        if (response.success) {
            alertContainer.innerHTML = '<div class="alert alert-success">Report submitted successfully! Na-notify na via email ang Super Admin. Redirecting...</div>';
            
            // Redirect after 1 second, include new report ID so we can highlight it on the list
            const newId = response.data && response.data.report_id ? response.data.report_id : '';
            setTimeout(() => {
                let url = '/School_Facility_Maintenance_System/frontend/pages/reports.php';
                if (newId) url += '?new_id=' + encodeURIComponent(newId);
                window.location.href = url;
            }, 1000);
        } else {
            throw new Error(response.message || 'Failed to create report');
        }
    } catch (error) {
        console.error('Submit error:', error);
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
        renderNeedChangeOptions();
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
