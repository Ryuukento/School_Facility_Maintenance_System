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
                        <option value="urgent">Urgent - Critical</option>
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

<style>
.create-report-page {
    width: calc(100% - var(--sidebar-width));
    max-width: calc(100% - var(--sidebar-width));
    margin-left: var(--sidebar-width);
    margin-right: 0;
    padding-left: 24px;
    padding-right: 24px;
}

.navbar .navbar-container {
    max-width: none;
    margin: 0;
    padding-left: 24px;
    padding-right: 24px;
}

#sidebar.collapsed ~ main.create-report-page {
    width: calc(100% - var(--sidebar-width-collapsed));
    max-width: calc(100% - var(--sidebar-width-collapsed));
    margin-left: var(--sidebar-width-collapsed);
}

.create-report-card {
    width: 100%;
    max-width: none;
    margin: 0;
}

@media (max-width: 992px) {
    .create-report-page {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
        padding-left: 16px;
        padding-right: 16px;
    }
}

@media (max-width: 640px) {
    .create-report-page {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
        padding-left: 12px;
        padding-right: 12px;
    }
}
</style>

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
        description: document.getElementById('description').value.trim()
    };
    
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
