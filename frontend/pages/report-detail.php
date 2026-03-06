<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Report Details - SFMS';
include __DIR__ . '/../includes/header.php';

$reportId = $_GET['id'] ?? 0;
$user = $_SESSION['user'];
?>

<main class="container">
    <div class="card" style="max-width: 900px; margin: 0 auto;">
        <div class="card-header d-flex justify-between align-center">
            <div style="flex: 1;">
                <h2 id="report-title">Loading...</h2>
                <p class="text-muted mb-0">Report Details</p>
            </div>
            <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php" class="btn btn-secondary btn-sm" style="height: fit-content; margin-top: 0;">
                ← Back to Reports
            </a>
        </div>
        
        <div class="card-body" id="report-details">
            <div class="loading">Loading report details...</div>
        </div>
    </div>
    
    <!-- Update Status Card (for admins/staff) -->
    <?php if (in_array($user['role'], ['super_admin', 'department_admin', 'maintenance_staff'])): ?>
    <div class="card" style="max-width: 900px; margin: 20px auto 0;">
        <div class="card-header">
            <h2>Update Report Status</h2>
        </div>
        <div class="card-body">
            <div id="update-alert"></div>
            <form id="update-form">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="submitted">Submitted</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="update-btn">
                    Update Status
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
const reportId = <?php echo intval($reportId); ?>;

// Load report details
async function loadReport() {
    if (!reportId) {
        document.getElementById('report-details').innerHTML = 
            '<p class="text-danger">Invalid report ID</p>';
        return;
    }
    
    try {
        const response = await API.getReport(reportId);
        const report = response.data.report;
        
        // Update title
        document.getElementById('report-title').textContent = `#${report.report_id} - ${report.title}`;
        
        // Build details HTML
        let html = '<div style="display: grid; gap: 20px;">';
        
        // Basic Info
        html += '<div>';
        html += '<h3 style="margin-bottom: 15px; font-weight: 600;">Basic Information</h3>';
        html += '<table class="table" style="border-collapse: collapse;">';
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Report ID</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">#${report.report_id}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Title</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;"><strong>${report.title}</strong></td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Location</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${report.location}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Priority</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${UI.getPriorityBadge(report.priority)}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Status</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${UI.getStatusBadge(report.status)}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa;">Department</th><td style="padding: 12px 8px;">${report.department_name || 'Not assigned'}</td></tr>`;
        html += '</table>';
        html += '</div>';
        
        // Description
        html += '<div style="margin-top: 20px;">';
        html += '<h3 style="margin-bottom: 15px; font-weight: 600;">Description</h3>';
        html += `<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #007bff; line-height: 1.6; color: #333;">${report.description || 'No description provided'}</div>`;
        html += '</div>';
        
        // People
        html += '<div style="margin-top: 20px;">';
        html += '<h3 style="margin-bottom: 15px; font-weight: 600;">People</h3>';
        html += '<table class="table" style="border-collapse: collapse;">';
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Created By</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${report.creator_name} ${report.creator_email ? `(${report.creator_email})` : ''}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa;">Assigned To</th><td style="padding: 12px 8px;">${report.assigned_name ? `${report.assigned_name} ${report.assigned_email ? `(${report.assigned_email})` : ''}` : 'Not assigned yet'}</td></tr>`;
        html += '</table>';
        html += '</div>';
        
        // Dates
        if (report.created_at_full || report.created_at || report.updated_at_formatted || report.updated_at) {
            html += '<div style="margin-top: 20px;">';
            html += '<h3 style="margin-bottom: 15px; font-weight: 600;">Timeline</h3>';
            html += '<table class="table" style="border-collapse: collapse;">';
            if (report.created_at_full || report.created_at) {
                html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Created</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${report.created_at_full || report.created_at}</td></tr>`;
            }
            if (report.updated_at_formatted || report.updated_at) {
                html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Last Updated</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${report.updated_at_formatted || report.updated_at}</td></tr>`;
            }
            if (report.due_date) {
                html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Due Date</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${report.due_date_formatted || report.due_date}</td></tr>`;
            }
            if (report.completed_date) {
                html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa;">Completed</th><td style="padding: 12px 8px;">${report.completed_date_formatted || report.completed_date}</td></tr>`;
            }
            html += '</table>';
            html += '</div>';
        }
        
        html += '</div>';
        
        document.getElementById('report-details').innerHTML = html;
        
        // Set current status in form
        if (document.getElementById('status')) {
            document.getElementById('status').value = report.status;
        }
        
    } catch (error) {
        document.getElementById('report-details').innerHTML = 
            '<p class="text-danger">Failed to load report details</p>';
        console.error('Failed to load report:', error);
    }
}

// Handle status update
<?php if (in_array($user['role'], ['super_admin', 'department_admin', 'maintenance_staff'])): ?>
document.getElementById('update-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const updateBtn = document.getElementById('update-btn');
    const alertDiv = document.getElementById('update-alert');
    const newStatus = document.getElementById('status').value;
    
    const originalText = updateBtn.innerHTML;
    updateBtn.innerHTML = 'Updating...';
    updateBtn.disabled = true;
    alertDiv.innerHTML = '';
    
    try {
        await API.updateReport({
            report_id: reportId,
            status: newStatus
        });
        
        alertDiv.innerHTML = '<div class="alert alert-success">Status updated successfully!</div>';
        
        // Reload report details
        setTimeout(() => {
            loadReport();
            alertDiv.innerHTML = '';
        }, 1500);
        
    } catch (error) {
        alertDiv.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        updateBtn.innerHTML = originalText;
        updateBtn.disabled = false;
    }
});
<?php endif; ?>

// Initialize
document.addEventListener('DOMContentLoaded', loadReport);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Ensure API and Session are defined globally - CRITICAL
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/backend/api',
    async getReport(id) {
        const response = await fetch(`${this.baseURL}/reports.php?action=get&id=${id}`, {
            credentials: 'include'
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        return data;
    },
    async updateReport(data) {
        const response = await fetch(`${this.baseURL}/reports.php?action=update`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        return result;
    },
    async logout() {
        const response = await fetch(`${this.baseURL}/auth.php?action=logout`, {
            credentials: 'include'
        });
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
</script>
