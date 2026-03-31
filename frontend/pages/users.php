<?php
/**
 * User Management Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
$userRole = $user['role'] ?? 'user';

if (!in_array($userRole, ['super_admin'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

$pageTitle = 'User Management - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container users-page-container">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div style="flex: 1;">
                <h2>User Management</h2>
                <p class="text-muted mb-0">Approve pending users and manage system accounts</p>
            </div>
        </div>

        <div class="card-body">
            <div id="alert-container"></div>

            <input type="text" id="search-input" placeholder="Search users..."
                   style="width: 100%; padding: 0.75rem 1rem; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 4px;">

            <div id="users-container">
                <div class="text-center text-muted">Loading users...</div>
            </div>
        </div>
    </div>
</main>

<style>
.users-page-container {
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

#sidebar.collapsed ~ main.users-page-container {
    width: calc(100% - var(--sidebar-width-collapsed));
    max-width: calc(100% - var(--sidebar-width-collapsed));
    margin-left: var(--sidebar-width-collapsed);
}

.users-page-container #users-container {
    overflow-x: auto;
}

@media (max-width: 992px) {
    .users-page-container {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
        padding-left: 16px;
        padding-right: 16px;
    }
}

@media (max-width: 640px) {
    .users-page-container {
        width: calc(100% - var(--sidebar-width-collapsed));
        max-width: calc(100% - var(--sidebar-width-collapsed));
        margin-left: var(--sidebar-width-collapsed);
        padding-left: 12px;
        padding-right: 12px;
    }
}
</style>

<script>
window.API = window.API || { baseURL: '/School_Facility_Maintenance_System/laravel_app/public/backend/api' };
const currentUserRole = <?php echo json_encode($userRole); ?>;

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    document.getElementById('search-input').addEventListener('input', searchUsers);
});

async function loadUsers() {
    const container = document.getElementById('users-container');
    container.innerHTML = '<div class="text-center text-muted">Loading users...</div>';

    try {
        const response = await fetch(`${window.API.baseURL}/users-api.php?action=list`, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data && data.data.users) {
            displayUsersList(data.data.users);
        } else {
            container.innerHTML = '<div class="text-center text-muted">No users found</div>';
        }
    } catch (error) {
        console.error('Error loading users:', error);
        container.innerHTML = '<div class="text-center" style="color: #c62828; padding: 1rem;">Error loading users: ' + error.message + '</div>';
    }
}

function searchUsers() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function displayUsersList(users) {
    const container = document.getElementById('users-container');

    if (!users || users.length === 0) {
        container.innerHTML = '<div class="text-center text-muted" style="padding: 2rem;">No users found</div>';
        return;
    }

    const isDark = document.documentElement.getAttribute('data-theme-resolved') === 'dark';
    const borderColor = isDark ? '#374151' : '#ddd';
    const tableBg = isDark ? '#111827' : '#ffffff';
    const headerBg = isDark ? '#1f2937' : '#f0f0f0';
    const headerText = isDark ? '#f3f4f6' : '#1f2937';
    const rowOdd = isDark ? '#111827' : '#ffffff';
    const rowEven = isDark ? '#0f172a' : '#f9fafb';
    const textPrimary = isDark ? '#f3f4f6' : '#1f2937';
    const textMuted = isDark ? '#cbd5e1' : '#4b5563';

    let html = `<table class="table" style="width: 100%; border-collapse: collapse; background: ${tableBg}; border: 1px solid ${borderColor};">`;
    html += `<thead><tr style="background: ${headerBg};">`;
    html += `<th style="padding: 10px; text-align: left; border: 1px solid ${borderColor}; color: ${headerText};">Name</th>`;
    html += `<th style="padding: 10px; text-align: left; border: 1px solid ${borderColor}; color: ${headerText};">Email</th>`;
    html += `<th style="padding: 10px; text-align: left; border: 1px solid ${borderColor}; color: ${headerText};">Role</th>`;
    html += `<th style="padding: 10px; text-align: left; border: 1px solid ${borderColor}; color: ${headerText};">Status</th>`;
    html += `<th style="padding: 10px; text-align: center; border: 1px solid ${borderColor}; color: ${headerText};">Actions</th></tr></thead>`;
    html += '<tbody>';

    users.forEach((targetUser, index) => {
        const safeName = (targetUser.full_name || '').replace(/'/g, "\\'");
        const normalizedStatus = String(targetUser.status || '').toLowerCase();
        let statusBadge = '<span style="background: #8b2020; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Inactive</span>';

        if (normalizedStatus === 'active') {
            statusBadge = '<span style="background: #2d9d78; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Active</span>';
        } else if (normalizedStatus === 'pending') {
            statusBadge = '<span style="background: #b7791f; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Pending</span>';
        } else if (normalizedStatus === 'suspended') {
            statusBadge = '<span style="background: #7f1d1d; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Suspended</span>';
        }

        const approveBtn = (normalizedStatus === 'pending' && currentUserRole === 'super_admin')
            ? `<button onclick="openApproveUserModal(${targetUser.user_id}, '${safeName}')" style="background: #6b21a8; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; margin-right: 6px;">Approve</button>`
            : '';

        const actionStatusBtn = (normalizedStatus === 'pending')
            ? `<button onclick="openRejectUserModal(${targetUser.user_id}, '${safeName}')" style="background: #b91c1c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">Reject</button>`
            : ((normalizedStatus === 'inactive')
                ? `<button onclick="openActivateUserModal(${targetUser.user_id}, '${safeName}')" style="background: #15803d; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">Active</button>`
                : `<button onclick="openInactiveUserModal(${targetUser.user_id}, '${safeName}')" style="background: #b45309; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">Inactive</button>`);
        const rowBackground = index % 2 === 0 ? rowOdd : rowEven;

        html += `<tr style="border: 1px solid ${borderColor}; background: ${rowBackground};">
            <td style="padding: 10px; border: 1px solid ${borderColor}; color: ${textPrimary};">${targetUser.full_name || 'N/A'}</td>
            <td style="padding: 10px; border: 1px solid ${borderColor}; color: ${textMuted};">${targetUser.email || 'N/A'}</td>
            <td style="padding: 10px; border: 1px solid ${borderColor}; color: ${textMuted};">${(targetUser.role || 'user').replace(/_/g, ' ').toUpperCase()}</td>
            <td style="padding: 10px; border: 1px solid ${borderColor};">${statusBadge}</td>
            <td style="padding: 10px; border: 1px solid ${borderColor}; text-align: center;">${approveBtn}${actionStatusBtn}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function openApproveUserModal(userId, userName) {
    const existingModal = document.getElementById('approve-user-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'approve-user-modal';
    modal.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; z-index:2100; padding:1rem;';

    modal.innerHTML = `
        <div style="background:#fff; width:min(520px,95vw); border-radius:10px; padding:1.25rem; box-shadow:0 14px 40px rgba(0,0,0,.2);">
            <h3 style="margin:0 0 .5rem 0; color:#1a1a1a;">Approve Pending User</h3>
            <p style="margin:0 0 1rem 0; color:#444;">Approve <strong>${userName || 'N/A'}</strong> and assign role.</p>
            <div style="margin-bottom: 1rem;">
                <label for="approve-user-role" style="display:block; margin-bottom: .4rem; font-weight:600; color:#333;">Assign role</label>
                <select id="approve-user-role" style="width:100%; padding:.65rem; border:1px solid #d1d5db; border-radius:6px;">
                    <option value="maintenance_staff" selected>Maintenance Staff</option>
                    <option value="maintenance_admin">Maintenance Admin</option>
                    <option value="user">User</option>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:.6rem;">
                <button type="button" id="cancel-approve-btn" style="padding:.55rem .9rem; border:1px solid #d6d6d6; background:#fff; color:#1f2937; font-weight:600; border-radius:6px; cursor:pointer;">Cancel</button>
                <button type="button" id="confirm-approve-btn" style="padding:.55rem .9rem; border:none; background:#6b21a8; color:#fff; border-radius:6px; cursor:pointer;">Approve</button>
            </div>
        </div>`;

    document.body.appendChild(modal);

    const closeModal = () => {
        const el = document.getElementById('approve-user-modal');
        if (el) el.remove();
    };

    document.getElementById('cancel-approve-btn')?.addEventListener('click', closeModal);
    document.getElementById('confirm-approve-btn')?.addEventListener('click', async () => {
        const role = document.getElementById('approve-user-role').value;
        await approveUser(userId, role);
        closeModal();
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
}

async function approveUser(userId, role) {
    try {
        const response = await fetch(`${window.API.baseURL}/users-api.php?action=approve`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ user_id: userId, role })
        });

        const data = await response.json();

        if (data.success) {
            showPageAlert('User approved and role assigned successfully!', 'success');
            loadUsers();
        } else {
            showPageAlert('Error: ' + (data.message || 'Failed to approve user'), 'error');
        }
    } catch (error) {
        console.error('Approve error:', error);
        showPageAlert('Failed to approve user: ' + error.message, 'error');
    }
}

function openInactiveUserModal(userId, userName) {
    const existingModal = document.getElementById('inactive-user-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'inactive-user-modal';
    modal.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; z-index:2000; padding:1rem;';

    modal.innerHTML = `
        <div style="background:#fff; width:min(480px,95vw); border-radius:10px; padding:1.25rem; box-shadow:0 14px 40px rgba(0,0,0,.2);">
            <h3 style="margin:0 0 .5rem 0; color:#1a1a1a;">Set User Inactive</h3>
            <p style="margin:0 0 1rem 0; color:#444;">Set <strong>${userName || 'N/A'}</strong> to inactive? This account will no longer be able to log in.</p>
            <div style="display:flex; justify-content:flex-end; gap:.6rem;">
                <button type="button" id="cancel-inactive-btn" style="padding:.55rem .9rem; border:1px solid #d6d6d6; background:#fff; color:#1f2937; border-radius:6px; cursor:pointer;">Cancel</button>
                <button type="button" id="confirm-inactive-btn" style="padding:.55rem .9rem; border:none; background:#b45309; color:#fff; border-radius:6px; cursor:pointer;">Inactive</button>
            </div>
        </div>`;

    document.body.appendChild(modal);

    const closeModal = () => {
        const el = document.getElementById('inactive-user-modal');
        if (el) el.remove();
    };

    document.getElementById('cancel-inactive-btn')?.addEventListener('click', closeModal);
    document.getElementById('confirm-inactive-btn')?.addEventListener('click', async () => {
        await deactivateUser(userId);
        closeModal();
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
}

function openRejectUserModal(userId, userName) {
    const existingModal = document.getElementById('reject-user-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'reject-user-modal';
    modal.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; z-index:2000; padding:1rem;';

    modal.innerHTML = `
        <div style="background:#fff; width:min(500px,95vw); border-radius:10px; padding:1.25rem; box-shadow:0 14px 40px rgba(0,0,0,.2);">
            <h3 style="margin:0 0 .5rem 0; color:#1a1a1a;">Reject Pending User</h3>
            <p style="margin:0 0 1rem 0; color:#444;">Reject <strong>${userName || 'N/A'}</strong>? This will permanently delete the pending account.</p>
            <div style="display:flex; justify-content:flex-end; gap:.6rem;">
                <button type="button" id="cancel-reject-btn" style="padding:.55rem .9rem; border:1px solid #d6d6d6; background:#fff; color:#1f2937; border-radius:6px; cursor:pointer;">Cancel</button>
                <button type="button" id="confirm-reject-btn" style="padding:.55rem .9rem; border:none; background:#b91c1c; color:#fff; border-radius:6px; cursor:pointer;">Reject</button>
            </div>
        </div>`;

    document.body.appendChild(modal);

    const closeModal = () => {
        const el = document.getElementById('reject-user-modal');
        if (el) el.remove();
    };

    document.getElementById('cancel-reject-btn')?.addEventListener('click', closeModal);
    document.getElementById('confirm-reject-btn')?.addEventListener('click', async () => {
        await rejectUser(userId);
        closeModal();
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
}

async function deactivateUser(userId) {
    try {
        const response = await fetch(`${window.API.baseURL}/users-api.php?action=deactivate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ user_id: userId })
        });

        const data = await response.json();

        if (data.success) {
            showPageAlert('User set to inactive successfully!', 'success');
            loadUsers();
        } else {
            showPageAlert('Error: ' + (data.message || 'Failed to set user inactive'), 'error');
        }
    } catch (error) {
        console.error('Deactivate user error:', error);
        showPageAlert('Failed to set user inactive: ' + error.message, 'error');
    }
}

async function rejectUser(userId) {
    try {
        const response = await fetch(`${window.API.baseURL}/users-api.php?action=reject`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ user_id: userId })
        });

        const data = await response.json();

        if (data.success) {
            showPageAlert('Pending user rejected and account deleted successfully!', 'success');
            loadUsers();
        } else {
            showPageAlert('Error: ' + (data.message || 'Failed to reject user'), 'error');
        }
    } catch (error) {
        console.error('Reject user error:', error);
        showPageAlert('Failed to reject user: ' + error.message, 'error');
    }
}

function openActivateUserModal(userId, userName) {
    const existingModal = document.getElementById('activate-user-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'activate-user-modal';
    modal.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; z-index:2000; padding:1rem;';

    modal.innerHTML = `
        <div style="background:#fff; width:min(480px,95vw); border-radius:10px; padding:1.25rem; box-shadow:0 14px 40px rgba(0,0,0,.2);">
            <h3 style="margin:0 0 .5rem 0; color:#1a1a1a;">Set User Active</h3>
            <p style="margin:0 0 1rem 0; color:#444;">Set <strong>${userName || 'N/A'}</strong> to active? This account will be able to log in again.</p>
            <div style="display:flex; justify-content:flex-end; gap:.6rem;">
                <button type="button" id="cancel-activate-btn" style="padding:.55rem .9rem; border:1px solid #d6d6d6; background:#fff; color:#1f2937; border-radius:6px; cursor:pointer;">Cancel</button>
                <button type="button" id="confirm-activate-btn" style="padding:.55rem .9rem; border:none; background:#15803d; color:#fff; border-radius:6px; cursor:pointer;">Active</button>
            </div>
        </div>`;

    document.body.appendChild(modal);

    const closeModal = () => {
        const el = document.getElementById('activate-user-modal');
        if (el) el.remove();
    };

    document.getElementById('cancel-activate-btn')?.addEventListener('click', closeModal);
    document.getElementById('confirm-activate-btn')?.addEventListener('click', async () => {
        await activateUser(userId);
        closeModal();
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
}

async function activateUser(userId) {
    try {
        const response = await fetch(`${window.API.baseURL}/users-api.php?action=activate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ user_id: userId })
        });

        const data = await response.json();

        if (data.success) {
            showPageAlert('User set to active successfully!', 'success');
            loadUsers();
        } else {
            showPageAlert('Error: ' + (data.message || 'Failed to set user active'), 'error');
        }
    } catch (error) {
        console.error('Activate user error:', error);
        showPageAlert('Failed to set user active: ' + error.message, 'error');
    }
}

function showPageAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;

    const bg = type === 'success' ? '#e8f5e9' : '#ffebee';
    const color = type === 'success' ? '#2e7d32' : '#c62828';

    alertContainer.innerHTML = `<div style="background:${bg}; color:${color}; padding:.75rem 1rem; border-radius:6px; margin-bottom:1rem;">${message}</div>`;

    setTimeout(() => {
        if (alertContainer) alertContainer.innerHTML = '';
    }, 3000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
