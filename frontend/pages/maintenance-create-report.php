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
if (!in_array($user['role'], ['super_admin', 'maintenance_admin'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';
$pdo = getDBConnection();

// Get departments
$stmt = $pdo->query("SELECT * FROM departments WHERE status = 'active' ORDER BY name");
$departments = $stmt->fetchAll();

// Get maintenance staff for assignment
$stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role IN ('maintenance_admin', 'maintenance_staff') AND status = 'active' ORDER BY full_name");
$staff = $stmt->fetchAll();

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

<main class="container" style="margin-top: 20px; max-width: 700px;">
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
                    <label for="department_id">Department *</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" 
                                <?php echo ($report && $report['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

                <div class="form-group">
                    <label for="assigned_to">Assign To</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Do not assign</option>
                        <?php foreach ($staff as $person): ?>
                            <option value="<?php echo $person['user_id']; ?>"
                                <?php echo ($report && $report['assigned_to'] == $person['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($person['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input 
                        type="date" 
                        id="due_date" 
                        name="due_date"
                        value="<?php echo htmlspecialchars($report['due_date'] ?? ''); ?>">
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
        department_id: document.getElementById('department_id').value || null,
        priority: document.getElementById('priority').value,
        description: document.getElementById('description').value.trim(),
        assigned_to: document.getElementById('assigned_to').value || null,
        due_date: document.getElementById('due_date').value || null
    };
    
    if (!formData.title || !formData.location || !formData.description || !formData.department_id) {
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
            alertContainer.innerHTML = '<div class="alert alert-success">Report ' + (reportId ? 'updated' : 'created') + ' successfully!</div>';
            
            setTimeout(() => {
                const newId = result.data.report_id || reportId;
                window.location.href = `/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=${newId}`;
            }, 1500);
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
