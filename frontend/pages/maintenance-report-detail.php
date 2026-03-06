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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="margin-top: 20px;">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2 id="report-title">Loading...</h2>
                <p class="text-muted mb-0">Report Details</p>
            </div>
            <a href="/School_Facility_Maintenance_System/frontend/pages/maintenance-reports-list.php" class="btn btn-secondary">
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
                        <option value="submitted">Submitted</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status-comment">Comment (optional)</label>
                    <textarea id="status-comment" placeholder="Add a comment about this status change..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="update-status-btn">Update Status</button>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
const reportId = <?php echo intval($reportId); ?>;

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

// Load report details
async function loadReport() {
    if (!reportId) {
        document.getElementById('report-details').innerHTML = 
            '<p class="text-danger">Invalid report ID</p>';
        return;
    }
    
    try {
        const response = await fetch(
            `/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php?action=get&id=${reportId}`
        );
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load report');
        }
        
        const report = data.data.report;
        document.getElementById('report-title').textContent = `#${report.report_id} - ${report.title}`;
        
        let html = '<div style="display: grid; gap: 20px;">';
        
        // Basic Info
        html += '<div>';
        html += '<h3 style="margin-bottom: 15px; font-weight: 600;">Basic Information</h3>';
        html += '<table class="table" style="border-collapse: collapse; width: 100%;">';
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Report ID</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">#${report.report_id}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Title</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;"><strong>${report.title}</strong></td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Location</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${report.location}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Priority</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;"><span class="badge ${UI.getPriorityBadge(report.priority)}">${report.priority.toUpperCase()}</span></td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Status</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;"><span class="badge ${UI.getStatusBadge(report.status)}">${report.status.replace('_', ' ').toUpperCase()}</span></td></tr>`;
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
        html += '<table class="table" style="border-collapse: collapse; width: 100%;">';
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa; border-bottom: 1px solid #ddd;">Created By</th><td style="padding: 12px 8px; border-bottom: 1px solid #ddd;">${report.creator_name} ${report.creator_email ? `(${report.creator_email})` : ''}</td></tr>`;
        html += `<tr><th style="width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: #222; background: #f8f9fa;">Assigned To</th><td style="padding: 12px 8px;">${report.assigned_name ? `${report.assigned_name} ${report.assigned_email ? `(${report.assigned_email})` : ''}` : 'Not assigned yet'}</td></tr>`;
        html += '</table>';
        html += '</div>';
        
        // Dates
        if (report.created_at_full || report.created_at || report.updated_at_formatted || report.updated_at) {
            html += '<div style="margin-top: 20px;">';
            html += '<h3 style="margin-bottom: 15px; font-weight: 600;">Timeline</h3>';
            html += '<table class="table" style="border-collapse: collapse; width: 100%;">';
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
        document.getElementById('new-status').value = report.status;
        
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
    
    if (!newStatus) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select a new status</div>';
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Updating...';
    alertDiv.innerHTML = '';
    
    try {
        const response = await fetch(
            `/School_Facility_Maintenance_System/backend/api/maintenance-reports-api.php?action=update&id=${reportId}`,
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus, comment: comment })
            }
        );
        
        const data = await response.json();
        
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">Status updated successfully!</div>';
            setTimeout(() => {
                loadReport();
                document.getElementById('status-comment').value = '';
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

// Initialize
document.addEventListener('DOMContentLoaded', loadReport);
</script>

</body>
</html>
