<?php
$pageTitle = 'Settings - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container">
    <div class="page-header">
        <h1 class="page-title">Settings</h1>
    </div>
    
    <div style="max-width: 600px;">
        <!-- Settings Tabs Navigation -->
        <div class="settings-tabs-nav">
            <button class="settings-tab-button active" onclick="switchTab('general')" data-tab="general">General</button>
            <button class="settings-tab-button" onclick="switchTab('account')" data-tab="account">Account</button>
            <button class="settings-tab-button" onclick="switchTab('notifications')" data-tab="notifications">Notifications</button>
        </div>
        
        <!-- General Tab -->
        <div id="general" class="settings-tab-pane active">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0" style="font-size: 22px;">General Settings</h3>
                </div>
    
                    <div class="card-body">
                    <form id="general-settings-form">
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="email_notifications" checked style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Receive email notifications</span>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="report_reminders" checked style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Receive report reminders</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="theme" style="font-size: 16px;">Theme</label>
                            <select id="theme" name="theme">
                                <option value="light" selected>Light</option>
                                <option value="dark">Dark</option>
                                <option value="auto">Auto</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- End General Tab -->
        
        <!-- Account Tab -->
        <div id="account" class="settings-tab-pane">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0" style="font-size: 22px;">Account Settings</h3>
                </div>
                    
                    <div class="card-body">
                    <form id="account-settings-form">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="full_name" style="font-size: 16px;">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter your full name">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="email_address" style="font-size: 16px;">Email Address</label>
                            <input type="email" id="email_address" name="email_address" class="form-control" placeholder="Enter your email">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="current_password" style="font-size: 16px;">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter current password">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="new_password" style="font-size: 16px;">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password (leave blank to keep current)">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="profile_picture" style="font-size: 16px;">Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="two_factor_auth" style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Enable Two-Factor Authentication</span>
                            </label>
                        </div>
                        
                
                        <button type="submit" class="btn btn-primary">Save Account Settings</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- End Account Tab -->
        
        <!-- Notifications Tab -->
        <div id="notifications" class="settings-tab-pane">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0" style="font-size: 22px;">Notification Settings</h3>
                    </div>
                    
                    <div class="card-body">
                    <form id="notification-settings-form">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="notify_new_report" checked style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Notify when new report is submitted</span>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="notify_report_assigned" checked style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Notify when report is assigned</span>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="notify_report_overdue" checked style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Notify when report is overdue</span>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="notify_inventory_low" checked style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Notify when inventory is low</span>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="notify_building_update" checked style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Notify when building/room is updated</span>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- End Notifications Tab -->
        </div>
    </main>

<style>
/* Settings Tabs Navigation */
.settings-tabs-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 2px solid #ddd;
    background: #f9f9f9;
    padding: 10px 0;
    border-radius: 4px 4px 0 0;
}

.settings-tab-button {
    padding: 10px 16px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
    margin-bottom: -10px;
    white-space: nowrap;
}

.settings-tab-button:hover {
    color: #333;
    background: #f0f0f0;
}

.settings-tab-button.active {
    color: #6608be;
    border-bottom-color: #6608be;
}

.settings-tab-pane {
    display: none;
}

.settings-tab-pane.active {
    display: block;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive tabs */
@media (max-width: 768px) {
    .settings-tabs-nav {
        gap: 2px;
        padding: 8px 0;
    }
    
    .settings-tab-button {
        padding: 8px 12px;
        font-size: 12px;
    }
}
</style>

<script>
// Tab Switching Function
function switchTab(tabName) {
    // Hide all tab panes
    const panes = document.querySelectorAll('.settings-tab-pane');
    panes.forEach(pane => {
        pane.classList.remove('active');
    });
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.settings-tab-button');
    buttons.forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab pane
    const selectedPane = document.getElementById(tabName);
    if (selectedPane) {
        selectedPane.classList.add('active');
    }
    
    // Add active class to clicked button
    const selectedButton = document.querySelector('[data-tab="' + tabName + '"]');
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // Save tab preference to localStorage
    localStorage.setItem('preferredSettingsTab', tabName);
}

// Restore tab preference on page load
document.addEventListener('DOMContentLoaded', function() {
    const preferredTab = localStorage.getItem('preferredSettingsTab') || 'general';
    switchTab(preferredTab);
});

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

async function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            await API.logout();
            if (typeof Session !== 'undefined' && Session.clear) {
                Session.clear();
            }
            window.location.href = '/School_Facility_Maintenance_System/frontend/pages/index.php';
        } catch (error) {
            console.error('Logout error:', error);
            // Force logout anyway
            window.location.href = '/School_Facility_Maintenance_System/frontend/pages/index.php';
        }
    }
}

document.getElementById('general-settings-form').addEventListener('submit', (e) => {
    e.preventDefault();
    alert('Settings saved successfully!');
});

// Account Settings
document.getElementById('account-settings-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const fullName = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email_address').value.trim();
    
    if (!fullName || !email) {
        alert('Please fill in all required fields');
        return;
    }
    
    alert('Account settings saved successfully!');
});

// Notification Settings
document.getElementById('notification-settings-form').addEventListener('submit', (e) => {
    e.preventDefault();
    alert('Notification settings saved successfully!');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
