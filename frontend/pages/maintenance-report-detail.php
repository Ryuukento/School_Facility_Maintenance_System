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

$currentRole = strtolower(trim((string)($user['role'] ?? '')));
if ($currentRole === 'admin_maintenance') {
    $currentRole = 'maintenance_admin';
}

$canAssignUser = in_array($currentRole, ['super_admin', 'maintenance_admin'], true);
$assignmentTargets = [];
$assignmentLabel = 'Assign To';
$assignmentPlaceholder = 'Select assignee...';
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
        $stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role IN ('maintenance_staff', 'eelab_staff', 'maintenance_personnel') AND status = 'active' ORDER BY full_name");
        $assignmentTargets = $stmt->fetchAll();
    } else {
        $assignmentLabel = 'Assign To (Maintenance Team)';
        $assignmentPlaceholder = 'Select maintenance admin or staff...';
        $stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role IN ('maintenance_admin', 'maintenance_staff', 'eelab_staff', 'maintenance_personnel') AND status = 'active' ORDER BY full_name");
        $assignmentTargets = $stmt->fetchAll();
    }
}
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
                        <option value="submitted">Submitted</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <?php if ($canAssignUser): ?>
                <div class="form-group" id="assigned-to-group" style="display:none;">
                    <label for="assigned-to"><?php echo htmlspecialchars($assignmentLabel); ?></label>
                    <select id="assigned-to">
                        <option value=""><?php echo htmlspecialchars($assignmentPlaceholder); ?></option>
                        <?php foreach ($assignmentTargets as $person): ?>
                            <option value="<?php echo (int)$person['user_id']; ?>">
                                <?php echo htmlspecialchars($person['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="status-comment">Comment (optional)</label>
                    <textarea id="status-comment" placeholder="Add a comment about this status change..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="update-status-btn">Update Status</button>
            </form>
        </div>
    </div>
</main>

<style>
.assigned-report-page {
    width: calc(100% - var(--sidebar-width));
    max-width: calc(100% - var(--sidebar-width));
    margin-left: var(--sidebar-width);
    margin-right: 0;
    margin-top: 20px;
    padding-left: 24px;
    padding-right: 24px;
}

.navbar .navbar-container {
    max-width: none;
    margin: 0;
    padding-left: 24px;
    padding-right: 24px;
}

#sidebar.collapsed ~ main.assigned-report-page {
    width: calc(100% - var(--sidebar-width-collapsed));
    max-width: calc(100% - var(--sidebar-width-collapsed));
    margin-left: var(--sidebar-width-collapsed);
}

@media (max-width: 992px) {
    .assigned-report-page {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
    }
}

@media (max-width: 640px) {
    .navbar .navbar-container,
    .assigned-report-page {
        padding-left: 14px;
        padding-right: 14px;
    }
}
</style>

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
        html += `<tr><th style="${thStyle}">Priority</th><td style="${tdStyle}"><span class="badge ${UI.getPriorityBadge(report.priority)}">${report.priority.toUpperCase()}</span></td></tr>`;
        html += `<tr><th style="${thStyle}">Status</th><td style="${tdStyle}"><span class="badge ${UI.getStatusBadge(report.status)}">${report.status.replace('_', ' ').toUpperCase()}</span></td></tr>`;
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
        
        html += '</div>';
        
        document.getElementById('report-details').innerHTML = html;
        
        // Set current status in form
        document.getElementById('new-status').value = report.status;
        const assignGroup = document.getElementById('assigned-to-group');
        const assignSelect = document.getElementById('assigned-to');
        if (assignGroup && assignSelect) {
            if (report.status === 'assigned') {
                assignGroup.style.display = 'block';
                assignSelect.required = true;
            }
            if (report.assigned_to) {
                assignSelect.value = report.assigned_to;
            }
        }
        
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

    const assignSelect = document.getElementById('assigned-to');
    if (newStatus === 'assigned' && assignSelect && !assignSelect.value) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select a maintenance admin to assign</div>';
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
                body: JSON.stringify({
                    status: newStatus,
                    comment: comment,
                    assigned_to: assignSelect ? (assignSelect.value || null) : null
                })
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

// Toggle assignment dropdown for super admin
document.getElementById('new-status').addEventListener('change', () => {
    const group = document.getElementById('assigned-to-group');
    const select = document.getElementById('assigned-to');
    if (!group || !select) return;
    if (document.getElementById('new-status').value === 'assigned') {
        group.style.display = 'block';
        select.required = true;
    } else {
        group.style.display = 'none';
        select.required = false;
        select.value = '';
    }
});
</script>

</body>
</html>
