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
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter your full name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="email_address" style="font-size: 16px;">Email Address</label>
                            <input type="email" id="email_address" name="email_address" class="form-control" placeholder="Enter your email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
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
                            <div style="margin-top: 10px; display: flex; align-items: center; gap: 12px;">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Profile Preview" id="settings-avatar-preview" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #d7c0f2;">
                                <?php else: ?>
                                    <div id="settings-avatar-preview-fallback" style="width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; background: #8A2BE2;"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?></div>
                                <?php endif; ?>
                                <span style="color: #666; font-size: 13px;">Upload JPG, PNG, WEBP, or GIF (max 3MB)</span>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; gap: 15px; font-size: 18px; cursor: pointer;">
                                <input type="checkbox" name="two_factor_auth" style="width: 24px; height: 24px; cursor: pointer;">
                                <span>Enable Two-Factor Authentication</span>
                            </label>
                        </div>
                        
                
                        <button type="submit" class="btn btn-primary">Save Account Settings</button>
                    </form>

                    <div class="logout-panel" style="margin-top: 24px;">
                        <h4 style="margin: 0 0 8px 0; font-size: 18px; color: #6A1BBE;">Account Session</h4>
                        <p style="margin: 0 0 14px 0; color: #666;">Need to end your current session? Use this button to log out safely.</p>
                        <button type="button" id="open-logout-modal" class="btn btn-danger">Log Out</button>
                    </div>
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

<div id="logout-modal" class="logout-modal" aria-hidden="true" role="dialog" aria-labelledby="logout-modal-title">
    <div class="logout-modal-card">
        <h3 id="logout-modal-title" style="margin: 0 0 10px 0; font-size: 22px;">Log out of you account?</h3>
        <p style="margin: 0 0 18px 0; color: #666;">You will need to log in again to access the dashboard.</p>
        <div class="logout-modal-actions">
            <button type="button" id="logout-cancel-btn" class="btn btn-secondary">Cancel</button>
            <button type="button" id="logout-confirm-btn" class="btn btn-danger">Log Out</button>
        </div>
    </div>
</div>

<div id="settings-toast" class="settings-toast" aria-live="polite"></div>

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

.logout-panel {
    border: 1px solid rgba(138, 43, 226, 0.18);
    border-radius: 10px;
    padding: 16px;
    background: #f9f2ff;
}

.logout-modal {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, 0.55);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    z-index: 2200;
}

.logout-modal.show {
    display: flex;
}

.logout-modal-card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 16px 34px rgba(0, 0, 0, 0.22);
}

.logout-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.settings-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    min-width: 240px;
    max-width: 320px;
    background: #111827;
    color: #fff;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: opacity 0.25s ease, transform 0.25s ease;
    z-index: 2300;
}

.settings-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.settings-toast.success {
    background: #065f46;
}

.settings-toast.error {
    background: #991b1b;
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
    },
    async updateProfile(formData) {
        const response = await fetch(`${this.baseURL}/users-api.php?action=update_profile`, {
            method: 'POST',
            body: formData,
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

let settingsToastTimer;
function showSettingsToast(message, type = 'success') {
    const toast = document.getElementById('settings-toast');
    if (!toast) return;

    toast.textContent = message;
    toast.classList.remove('success', 'error', 'show');
    toast.classList.add(type);

    clearTimeout(settingsToastTimer);
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    settingsToastTimer = setTimeout(() => {
        toast.classList.remove('show');
    }, 2400);
}

async function handleLogout() {
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

function openLogoutModal() {
    const modal = document.getElementById('logout-modal');
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeLogoutModal() {
    const modal = document.getElementById('logout-modal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('open-logout-modal').addEventListener('click', openLogoutModal);
document.getElementById('logout-cancel-btn').addEventListener('click', closeLogoutModal);
document.getElementById('logout-confirm-btn').addEventListener('click', handleLogout);

document.getElementById('logout-modal').addEventListener('click', function(e) {
    if (e.target.id === 'logout-modal') {
        closeLogoutModal();
    }
});

document.getElementById('general-settings-form').addEventListener('submit', (e) => {
    e.preventDefault();
    showSettingsToast('Settings saved successfully!');
});

function renderHeaderAvatar(user) {
    const headerName = document.getElementById('header-user-name');
    if (headerName && user.full_name) {
        headerName.textContent = user.full_name;
    }

    const profileRoot = document.querySelector('.user-profile');
    if (profileRoot && user.full_name) {
        profileRoot.setAttribute('title', user.full_name);
    }

    const profileContainer = document.querySelector('.user-avatar-wrap');
    if (!profileContainer) return;

    if (user.avatar) {
        profileContainer.innerHTML = `<img src="${user.avatar}" alt="avatar" class="avatar-img" id="header-user-avatar" />`;
    } else {
        const initial = (user.full_name || 'U').trim().charAt(0).toUpperCase();
        profileContainer.innerHTML = `<div class="avatar-circle" data-user-avatar id="header-user-avatar-fallback">${initial}</div>`;
    }
}

function renderSettingsAvatar(user) {
    const previewImg = document.getElementById('settings-avatar-preview');
    const fallback = document.getElementById('settings-avatar-preview-fallback');

    if (user.avatar) {
        if (previewImg) {
            previewImg.src = user.avatar;
        } else if (fallback && fallback.parentNode) {
            fallback.outerHTML = `<img src="${user.avatar}" alt="Profile Preview" id="settings-avatar-preview" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #d7c0f2;">`;
        }
    } else {
        const initial = (user.full_name || 'U').trim().charAt(0).toUpperCase();
        if (fallback) {
            fallback.textContent = initial;
        }
    }
}

document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;

    const previewUrl = URL.createObjectURL(file);
    renderSettingsAvatar({
        full_name: document.getElementById('full_name').value || 'U',
        avatar: previewUrl
    });
});

// Account Settings
document.getElementById('account-settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fullName = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email_address').value.trim();
    
    if (!fullName || !email) {
        showSettingsToast('Please fill in all required fields', 'error');
        return;
    }

    const form = document.getElementById('account-settings-form');
    const submitButton = form.querySelector('button[type="submit"]');
    const originalLabel = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Saving...';

    try {
        const formData = new FormData(form);
        const response = await API.updateProfile(formData);

        if (!response.success) {
            throw new Error(response.message || 'Failed to save account settings');
        }

        const updatedUser = response.data?.user || { full_name: fullName, email: email };
        renderHeaderAvatar(updatedUser);
        renderSettingsAvatar(updatedUser);

        if (window.Session && typeof Session.get === 'function' && typeof Session.set === 'function') {
            const sessionUser = Session.get('user') || {};
            Session.set('user', { ...sessionUser, ...updatedUser });
        }

        showSettingsToast('Account settings saved successfully!');
    } catch (error) {
        console.error('Save account settings error:', error);
        showSettingsToast(error.message || 'Failed to save account settings', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalLabel;
    }
});

// Notification Settings
document.getElementById('notification-settings-form').addEventListener('submit', (e) => {
    e.preventDefault();
    showSettingsToast('Notification settings saved successfully!');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
