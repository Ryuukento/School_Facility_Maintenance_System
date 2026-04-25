<?php
/**
 * Edit Maintenance Report
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/index.php');
    exit;
}

require_once __DIR__ . '/../../backend/config/database.php';

// Establish database connection
$pdo = getDBConnection();

// Get report ID from URL
$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reportId) {
    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php');
    exit;
}

// Fetch report details
try {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_reports WHERE report_id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
    
    if (!$report) {
        header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php');
        exit;
    }
} catch (Exception $e) {
    die("Error fetching report: " . $e->getMessage());
}

// Get departments for dropdown
$stmt = $pdo->query("SELECT * FROM departments WHERE status = 'active' ORDER BY name");
$departments = $stmt->fetchAll();

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/color-scheme.css">
</head>
<body>

<main class="container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-header">
            <h2>Edit Maintenance Report</h2>
            <p class="text-muted mb-0">Update report details - Report ID #<?php echo $reportId; ?></p>
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
                        value="<?php echo htmlspecialchars($report['title']); ?>"
                        required>
                </div>
                
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        placeholder="e.g., Building A - Room 101"
                        value="<?php echo htmlspecialchars($report['location']); ?>"
                        required>
                </div>
                
                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" <?php echo ($report['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority *</label>
                    <select id="priority" name="priority" required>
                        <option value="low" <?php echo ($report['priority'] == 'low') ? 'selected' : ''; ?>>Low - Can wait</option>
                        <option value="medium" <?php echo ($report['priority'] == 'medium') ? 'selected' : ''; ?>>Medium - Normal</option>
                        <option value="high" <?php echo ($report['priority'] == 'high') ? 'selected' : ''; ?>>High - Important</option>
                        <option value="critical" <?php echo ($report['priority'] == 'critical') ? 'selected' : ''; ?>>Critical - Immediate</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="submitted" <?php echo ($report['status'] == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                        <option value="assigned" <?php echo ($report['status'] == 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in_progress" <?php echo ($report['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo ($report['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="closed" <?php echo ($report['status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Please provide detailed description of the issue..."
                        required><?php echo htmlspecialchars($report['description']); ?></textarea>
                </div>
                
                <div class="d-flex gap-sm">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        Save Changes
                    </button>
                    <a href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="button" class="btn btn-danger" id="delete-btn">
                        Delete Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/js/api.js"></script>

<script>
// Ensure API and Session are defined globally
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/laravel_app/public/backend/api',
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
    async deleteReport(reportId) {
        const response = await fetch(`${this.baseURL}/reports.php?action=delete`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: reportId })
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

document.getElementById('report-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submit-btn');
    const deleteBtn = document.getElementById('delete-btn');
    const alertContainer = document.getElementById('alert-container');
    
    // Get form data
    const formData = {
        report_id: <?php echo $reportId; ?>,
        title: document.getElementById('title').value.trim(),
        location: document.getElementById('location').value.trim(),
        department_id: document.getElementById('department_id').value || null,
        priority: document.getElementById('priority').value,
        status: document.getElementById('status').value,
        description: document.getElementById('description').value.trim()
    };
    
    // Validate
    if (!formData.title || !formData.location || !formData.description) {
        alertContainer.innerHTML = '<div class="alert alert-danger">Please fill in all required fields</div>';
        return;
    }
    
    // Show loading
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = 'Saving...';
    submitBtn.disabled = true;
    deleteBtn.disabled = true;
    alertContainer.innerHTML = '';
    
    try {
        const response = await window.API.updateReport(formData);
        
        if (response.success) {
            alertContainer.innerHTML = '<div class="alert alert-success">Report updated successfully! Redirecting...</div>';
            
            // Redirect after 1 second
            setTimeout(() => {
                window.location.href = '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php';
            }, 1000);
        } else {
            throw new Error(response.message || 'Failed to update report');
        }
    } catch (error) {
        console.error('Submit error:', error);
        alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        deleteBtn.disabled = false;
    }
});

document.getElementById('delete-btn').addEventListener('click', async () => {
    const alertContainer = document.getElementById('alert-container');
    const submitBtn = document.getElementById('submit-btn');
    const deleteBtn = document.getElementById('delete-btn');

    const confirmed = window.confirm('Delete this report? This action cannot be undone.');
    if (!confirmed) return;

    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = 'Deleting...';
    deleteBtn.disabled = true;
    submitBtn.disabled = true;
    alertContainer.innerHTML = '';

    try {
        await window.API.deleteReport(<?php echo $reportId; ?>);
        alertContainer.innerHTML = '<div class="alert alert-success">Report deleted. Redirecting...</div>';
        setTimeout(() => {
            window.location.href = '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php';
        }, 1000);
    } catch (error) {
        alertContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
        submitBtn.disabled = false;
    }
});
</script>

</body>
</html>
