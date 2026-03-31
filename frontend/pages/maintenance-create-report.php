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
                        <option value="urgent" <?php echo ($report && $report['priority'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
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

<style>
.maintenance-create-report-page {
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

#sidebar.collapsed ~ main.maintenance-create-report-page {
    width: calc(100% - var(--sidebar-width-collapsed));
    max-width: calc(100% - var(--sidebar-width-collapsed));
    margin-left: var(--sidebar-width-collapsed);
}

.maintenance-create-report-page .card {
    width: 100%;
    margin: 0;
}

@media (max-width: 992px) {
    .maintenance-create-report-page {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
        padding-left: 16px;
        padding-right: 16px;
    }
}

@media (max-width: 640px) {
    .maintenance-create-report-page {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
        padding-left: 12px;
        padding-right: 12px;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
const reportId = <?php echo json_encode($reportId); ?>;

document.getElementById('report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submit-btn');
    const alertContainer = document.getElementById('alert-container');
    
    const formData = {
        title: document.getElementById('title').value.trim(),
        location: document.getElementById('location').value.trim(),
        priority: document.getElementById('priority').value,
        description: document.getElementById('description').value.trim()
    };
    
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
</script>

</body>
</html>
