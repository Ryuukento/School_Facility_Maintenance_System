<?php
/**
 * Analytics Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// Check permission FIRST - before any output
if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$userRole = $_SESSION['user']['role'] ?? 'user';

// Check if user has permission
if (!in_array($userRole, ['super_admin', 'department_admin'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

$pageTitle = 'Analytics - SFMS';
$currentUser = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
</head>
<body>

<main class="container">
    <div class="card">
        <div class="card-header">
            <h2>Analytics & Reports</h2>
            <p class="text-muted mb-0">System statistics and reports</p>
        </div>
        
        <div class="card-body">
            <div id="alert-container"></div>
            <div id="analytics-content">
                <div class="text-center text-muted">Loading analytics...</div>
            </div>
        </div>
    </div>
</main>

<script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
<script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

<script>
// Ensure API and Session are defined globally
window.API = window.API || {
    baseURL: '/School_Facility_Maintenance_System/backend/api',
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

document.addEventListener('DOMContentLoaded', () => {
    loadAnalytics();
});

async function loadAnalytics() {
    const container = document.getElementById('analytics-content');
    
    try {
        const response = await fetch(`${window.API.baseURL}/reports.php?action=stats`);
        const data = await response.json();
        
        if (data.success && data.data && data.data.stats) {
            const stats = data.data.stats;
            
            let html = '<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';
            
            html += `<div class="stat-card" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Total Reports</h3>
                <div style="font-size: 24px; font-weight: bold; color: #4a9eff; margin: 10px 0;">${stats.total || 0}</div>
            </div>`;
            
            html += `<div class="stat-card" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Submitted</h3>
                <div style="font-size: 24px; font-weight: bold; color: #4a9eff; margin: 10px 0;">${stats.submitted || 0}</div>
            </div>`;
            
            html += `<div class="stat-card" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>In Progress</h3>
                <div style="font-size: 24px; font-weight: bold; color: #d4a574; margin: 10px 0;">${stats.in_progress || 0}</div>
            </div>`;
            
            html += `<div class="stat-card" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h3>Completed</h3>
                <div style="font-size: 24px; font-weight: bold; color: #2d9d78; margin: 10px 0;">${stats.completed || 0}</div>
            </div>`;
            
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-center text-muted">No analytics data available</p>';
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        container.innerHTML = '<p class="text-center text-danger">Failed to load analytics</p>';
    }
}
</script>

</body>
</html>

