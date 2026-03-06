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
    <title>Create Report - School Facility Maintenance System</title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
</head>
<body>

<main class="container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
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
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>">
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

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
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
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
        department_id: document.getElementById('department_id').value || null,
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
            alertContainer.innerHTML = '<div class="alert alert-success">Report submitted successfully! Redirecting...</div>';
            
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

</body>
</html>
