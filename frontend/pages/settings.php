<?php
$pageTitle = 'Settings - SFMS';
$activeSettingsTab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'general';
$allowedSettingsTabs = ['general', 'account', 'notifications'];
if (!in_array($activeSettingsTab, $allowedSettingsTabs, true)) {
    $activeSettingsTab = 'general';
}
include __DIR__ . '/../includes/header.php';
?>

<main class="container settings-page">
    <div class="page-header">
        <h1 class="page-title">Settings</h1>
    </div>

    <div class="settings-shell">
        <section class="settings-content-column">
            <!-- General Tab -->
            <div id="general" class="settings-section-pane <?php echo ($activeSettingsTab === 'general') ? 'active' : ''; ?>">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h3 class="settings-title">General Settings</h3>
                        <p class="settings-description">Manage your core preferences and communication options.</p>
                    </div>

                    <div class="settings-card-body">
                        <form id="general-settings-form">
                            <div class="settings-form-group">
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="email_notifications" checked>
                                    <span>Receive email notifications</span>
                                </label>
                            </div>

                            <div class="settings-form-group">
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="report_reminders" checked>
                                    <span>Receive report reminders</span>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary settings-primary-btn">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Tab -->
            <div id="account" class="settings-section-pane <?php echo ($activeSettingsTab === 'account') ? 'active' : ''; ?>">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h3 class="settings-title">Account Settings</h3>
                        <p class="settings-description">Update your profile information and personal details.</p>
                    </div>

                    <div class="settings-card-body">
                        <form id="account-settings-form">
                            <div class="settings-form-grid">
                                <div class="settings-form-group">
                                    <label for="full_name" class="settings-label">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control settings-input" placeholder="Enter your full name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                                </div>

                                <div class="settings-form-group">
                                    <label for="email_address" class="settings-label">Email Address</label>
                                    <input type="email" id="email_address" name="email_address" class="form-control settings-input" placeholder="Enter your email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>

                                <div class="settings-form-group">
                                    <label for="current_password" class="settings-label">Current Password</label>
                                    <div class="password-input-wrap">
                                        <input type="password" id="current_password" name="current_password" class="form-control settings-input" placeholder="Enter current password">
                                        <button type="button" class="toggle-password-btn" data-target="current_password" aria-label="Show current password" aria-pressed="false" title="Show password">👁</button>
                                    </div>
                                </div>

                                <div class="settings-form-group">
                                    <label for="new_password" class="settings-label">New Password</label>
                                    <div class="password-input-wrap">
                                        <input type="password" id="new_password" name="new_password" class="form-control settings-input" placeholder="Enter new password (leave blank to keep current)">
                                        <button type="button" class="toggle-password-btn" data-target="new_password" aria-label="Show new password" aria-pressed="false" title="Show password">👁</button>
                                    </div>
                                </div>

                                <div class="settings-form-group settings-span-2">
                                    <label for="profile_picture" class="settings-label">Profile Picture</label>
                                    <input type="file" id="profile_picture" name="profile_picture" class="form-control settings-input" accept="image/*">
                                    <div class="settings-avatar-row">
                                        <?php if (!empty($user['avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Profile Preview" id="settings-avatar-preview" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #d7c0f2;">
                                        <?php else: ?>
                                            <div id="settings-avatar-preview-fallback" style="width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; background: #8A2BE2;"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?></div>
                                        <?php endif; ?>
                                        <span class="settings-help-text">Upload JPG, PNG, WEBP, or GIF (max 3MB)</span>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary settings-primary-btn">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notifications Tab -->
            <div id="notifications" class="settings-section-pane <?php echo ($activeSettingsTab === 'notifications') ? 'active' : ''; ?>">
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h3 class="settings-title">Notification Settings</h3>
                        <p class="settings-description">Choose which updates you want to receive from the system.</p>
                    </div>

                    <div class="settings-card-body">
                        <form id="notification-settings-form">
                            <div class="settings-form-group">
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="notify_new_report" checked>
                                    <span>Notify when new report is submitted</span>
                                </label>
                            </div>

                            <div class="settings-form-group">
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="notify_report_assigned" checked>
                                    <span>Notify when report is assigned</span>
                                </label>
                            </div>

                            <div class="settings-form-group">
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="notify_report_overdue" checked>
                                    <span>Notify when report is overdue</span>
                                </label>
                            </div>

                            <div class="settings-form-group">
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="notify_inventory_low" checked>
                                    <span>Notify when inventory is low</span>
                                </label>
                            </div>

                            <div class="settings-form-group">
                                <label class="settings-checkbox-row">
                                    <input type="checkbox" name="notify_building_update" checked>
                                    <span>Notify when building/room is updated</span>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary settings-primary-btn">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<div id="settings-toast" class="settings-toast" aria-live="polite"></div>

<style>
:root {
    --settings-font-scale: 1;
}

.settings-page {
    width: calc(100% - var(--sidebar-width));
    max-width: calc(100% - var(--sidebar-width));
    margin-left: var(--sidebar-width);
    margin-right: 0;
    padding-left: 24px;
    padding-right: 24px;
    padding-bottom: 40px;
}

.navbar .navbar-container {
    max-width: none;
    margin: 0;
    padding-left: 24px;
    padding-right: 24px;
}

#sidebar.collapsed ~ main.settings-page {
    width: calc(100% - var(--sidebar-width-collapsed));
    max-width: calc(100% - var(--sidebar-width-collapsed));
    margin-left: var(--sidebar-width-collapsed);
}

.settings-subtitle {
    margin-top: 6px;
    color: #64748b;
    font-size: calc(14px * var(--settings-font-scale));
}

.settings-shell {
    display: block;
    align-items: start;
}

.settings-nav-card {
    background: #ffffff;
    border: 1px solid rgba(125, 27, 235, 0.14);
    border-radius: 14px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    padding: 18px;
    position: sticky;
    top: 90px;
    display: grid;
    gap: 8px;
}

.settings-nav-heading {
    margin: 0 0 6px 0;
    font-size: calc(13px * var(--settings-font-scale));
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #6b7280;
}

.settings-nav-link {
    border: 1px solid transparent;
    border-radius: 10px;
    background: transparent;
    color: #334155;
    text-align: left;
    font-size: calc(14px * var(--settings-font-scale));
    font-weight: 600;
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.settings-nav-link:hover {
    background: #f7f0ff;
    border-color: rgba(125, 27, 235, 0.2);
    color: #4c1d95;
}

.settings-nav-link.active {
    background: linear-gradient(135deg, rgba(138, 43, 226, 0.18), rgba(125, 27, 235, 0.1));
    border-color: rgba(125, 27, 235, 0.35);
    color: #4c1d95;
}

.settings-content-column {
    width: 100%;
}

.settings-section-pane {
    display: none;
}

.settings-section-pane.active {
    display: block;
    animation: fadeIn 0.3s ease-in;
}

.settings-card {
    background: #ffffff;
    border: 1px solid rgba(125, 27, 235, 0.12);
    border-radius: 16px;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.settings-card-header {
    padding: 24px 24px 14px;
    border-bottom: 1px solid rgba(125, 27, 235, 0.1);
}

.settings-title {
    margin: 0;
    font-size: calc(24px * var(--settings-font-scale));
    font-weight: 700;
    color: #111827;
}

.settings-description {
    margin: 6px 0 0;
    color: #64748b;
    font-size: calc(14px * var(--settings-font-scale));
}

.settings-card-body {
    padding: 24px;
}

.settings-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.settings-form-group {
    margin-bottom: 18px;
}

.settings-form-group:last-child {
    margin-bottom: 0;
}

.settings-span-2 {
    grid-column: span 2;
}

.settings-label {
    display: block;
    margin-bottom: 8px;
    font-size: calc(13px * var(--settings-font-scale));
    font-weight: 600;
    color: #475569;
}

.settings-input,
.settings-card .form-control,
.settings-card select {
    width: 100%;
    min-height: 44px;
    border-radius: 10px;
    border: 1px solid #d7c6f2;
    padding: 10px 12px;
    font-size: calc(14px * var(--settings-font-scale));
    color: #111827;
    background: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.settings-input:focus,
.settings-card .form-control:focus,
.settings-card select:focus {
    border-color: #7d1beb;
    box-shadow: 0 0 0 3px rgba(125, 27, 235, 0.18);
    outline: none;
}

.settings-checkbox-row {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    color: #1f2937;
    font-size: calc(15px * var(--settings-font-scale));
    font-weight: 500;
}

.settings-checkbox-row input[type='checkbox'] {
    width: 18px;
    height: 18px;
    accent-color: #7d1beb;
}

.settings-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 14px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    background: #f8fafc;
    cursor: pointer;
}

.settings-toggle-row strong {
    display: block;
    color: #0f172a;
    font-size: calc(14px * var(--settings-font-scale));
    margin-bottom: 2px;
}

.settings-toggle-row small {
    color: #64748b;
    font-size: calc(12px * var(--settings-font-scale));
}

.settings-toggle-row input[type='checkbox'] {
    width: 44px;
    height: 24px;
    accent-color: #7d1beb;
}

.settings-placeholder-box {
    margin-top: 18px;
    border: 1px dashed rgba(125, 27, 235, 0.36);
    background: #fbf8ff;
    border-radius: 12px;
    padding: 16px;
}

.settings-placeholder-box h4 {
    margin: 0 0 6px;
    font-size: calc(15px * var(--settings-font-scale));
    color: #4c1d95;
}

.settings-placeholder-box p {
    margin: 0;
    font-size: calc(13px * var(--settings-font-scale));
    color: #64748b;
}

.settings-avatar-row {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.settings-help-text {
    color: #64748b;
    font-size: calc(12px * var(--settings-font-scale));
}

.settings-primary-btn {
    margin-top: 8px;
    border-radius: 10px;
    min-height: 42px;
    padding: 10px 18px;
    font-weight: 700;
    font-size: calc(14px * var(--settings-font-scale));
    background: linear-gradient(135deg, #6A1BBE, #8A2BE2);
    border: none;
    box-shadow: 0 10px 20px rgba(106, 27, 190, 0.28);
}

.settings-primary-btn:hover {
    background: linear-gradient(135deg, #5a17a6, #7a24c7);
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(106, 27, 190, 0.33);
}

.password-input-wrap {
    position: relative;
}

.password-input-wrap .form-control {
    padding-right: 44px;
}

.toggle-password-btn {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    color: #6b7280;
    font-size: calc(16px * var(--settings-font-scale));
    line-height: 1;
    padding: 4px;
}

.toggle-password-btn:hover {
    color: #4b5563;
}

.toggle-password-btn:focus-visible {
    outline: 2px solid #8A2BE2;
    outline-offset: 2px;
    border-radius: 4px;
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
    font-size: calc(14px * var(--settings-font-scale));
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

@media (max-width: 980px) {
    .settings-page {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
    }

    .settings-shell {
        grid-template-columns: 1fr;
    }

    .settings-nav-card {
        position: static;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .settings-nav-heading {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .navbar .navbar-container,
    .settings-page {
        padding-left: 14px;
        padding-right: 14px;
    }

    .settings-card-header,
    .settings-card-body {
        padding: 20px;
    }

    .settings-form-grid {
        grid-template-columns: 1fr;
    }

    .settings-span-2 {
        grid-column: span 1;
    }
}
</style>

<script>
function switchTab(tabName, syncUrl = true) {
    const panes = document.querySelectorAll('.settings-section-pane');
    const navLinks = document.querySelectorAll('.settings-nav-link');

    panes.forEach((pane) => {
        pane.classList.remove('active');
    });

    navLinks.forEach((link) => {
        link.classList.remove('active');
    });

    const selectedPane = document.getElementById(tabName);
    if (selectedPane) {
        selectedPane.classList.add('active');
    }

    const selectedNav = document.querySelector(`.settings-nav-link[data-tab="${tabName}"]`);
    if (selectedNav) {
        selectedNav.classList.add('active');
    }

    if (syncUrl) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    }
}

document.querySelectorAll('.settings-nav-link').forEach((link) => {
    link.addEventListener('click', () => {
        const tabName = link.getAttribute('data-tab');
        if (tabName) {
            switchTab(tabName);
        }
    });
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

document.getElementById('general-settings-form').addEventListener('submit', (e) => {
    e.preventDefault();
    showSettingsToast('Settings saved successfully!');
});

document.querySelectorAll('.toggle-password-btn').forEach((button) => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        button.setAttribute('aria-label', (isHidden ? 'Hide' : 'Show') + ' password');
        button.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
        button.textContent = isHidden ? '🙈' : '👁';
    });
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
