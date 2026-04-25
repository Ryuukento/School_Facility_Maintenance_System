<?php
/**
 * User Management Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
$userRole = $user['role'] ?? 'user';

if (!in_array($userRole, ['super_admin'])) {
    header('Location: /School_Facility_Maintenance_System/laravel_app/public/frontend/pages/dashboard.php');
    exit;
}

$pageTitle = 'User Management - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container users-page-container">
    <section class="users-page-shell">
        <div class="users-page-hero">
            <div class="users-page-hero-copy">
                <span class="users-page-kicker">User Management</span>
                <h2>All Users</h2>
                <p>View every account as a profile card, with avatar previews, roles, and quick account actions.</p>
            </div>
        </div>

        <div class="users-toolbar">
            <div class="users-search-wrap">
                <input type="search" id="search-input" name="user-search" class="users-search-input" placeholder="Search by name, email, or role..." autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="search">
                <div class="users-role-filters" id="users-role-filters" aria-label="Filter users by role">
                    <button type="button" class="users-role-filter is-active" data-role-filter="all">All</button>
                    <button type="button" class="users-role-filter" data-role-filter="maintenance_staff">Maintenance Staff</button>
                    <button type="button" class="users-role-filter" data-role-filter="maintenance_admin">Admin Maintenance</button>
                </div>
            </div>
            <div class="users-toolbar-actions">
                <button type="button" id="add-new-user-btn" class="users-page-btn users-page-btn-primary">
                    <span class="users-page-btn-icon">+</span>
                    <span>Add New User</span>
                </button>
                <div id="users-summary" class="users-summary"></div>
            </div>
        </div>

        <div id="alert-container"></div>

        <div id="users-container">
            <div class="users-grid users-loading-grid ui-fade-in">
                <div class="users-card users-skeleton-card"></div>
                <div class="users-card users-skeleton-card"></div>
                <div class="users-card users-skeleton-card"></div>
                <div class="users-card users-skeleton-card"></div>
            </div>
        </div>

        <div id="users-pagination" class="users-pagination"></div>
    </section>
</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/users.inline.css?v=20260414-1">

<script>
window.API = window.API || { baseURL: (window.SFMS_BACKEND_API_BASE || '/backend/api') };
const currentUserRole = <?php echo json_encode($userRole); ?>;

const usersState = {
    allUsers: [],
    filteredUsers: [],
    currentPage: 1,
    pageSize: 8,
    searchTerm: '',
    roleFilter: 'all'
};

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    document.getElementById('search-input')?.addEventListener('input', searchUsers);
    document.getElementById('add-new-user-btn')?.addEventListener('click', openRegisterUserModal);

    document.querySelectorAll('[data-role-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            setRoleFilter(button.getAttribute('data-role-filter') || 'all');
        });
    });
});

async function loadUsers() {
    const container = document.getElementById('users-container');
    container.innerHTML = '<div class="users-grid users-loading-grid ui-fade-in"><div class="users-card users-skeleton-card"></div><div class="users-card users-skeleton-card"></div><div class="users-card users-skeleton-card"></div><div class="users-card users-skeleton-card"></div></div>';

    try {
        const response = await fetch(`${window.API.baseURL}/users-api.php?action=list`, {
            method: 'GET',
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success && data.data && data.data.users) {
            usersState.allUsers = data.data.users;
            usersState.searchTerm = document.getElementById('search-input')?.value.trim().toLowerCase() || '';
            usersState.currentPage = 1;
            applyUserFilters();
        } else {
            container.innerHTML = '<div class="ui-empty-state ui-fade-in users-empty-state"><strong>No users found.</strong><span>User records will appear here once accounts are created.</span></div>';
            document.getElementById('users-pagination').innerHTML = '';
            document.getElementById('users-summary').textContent = '';
        }
    } catch (error) {
        console.error('Error loading users:', error);
        container.innerHTML = '<div class="text-center users-error">Error loading users: ' + error.message + '</div>';
        document.getElementById('users-pagination').innerHTML = '';
        document.getElementById('users-summary').textContent = '';
    }
}

function searchUsers() {
    usersState.searchTerm = document.getElementById('search-input').value.trim().toLowerCase();
    usersState.currentPage = 1;
    applyUserFilters();
}

function setRoleFilter(nextRoleFilter) {
    const allowed = new Set(['all', 'maintenance_staff', 'maintenance_admin']);
    const normalized = String(nextRoleFilter || 'all').toLowerCase();
    usersState.roleFilter = allowed.has(normalized) ? normalized : 'all';
    usersState.currentPage = 1;
    renderRoleFilterState();
    applyUserFilters();
}

function renderRoleFilterState() {
    document.querySelectorAll('[data-role-filter]').forEach((button) => {
        const buttonRole = String(button.getAttribute('data-role-filter') || 'all').toLowerCase();
        button.classList.toggle('is-active', buttonRole === usersState.roleFilter);
    });
}

function applyUserFilters() {
    const term = usersState.searchTerm;
    const roleFilter = usersState.roleFilter;
    usersState.filteredUsers = usersState.allUsers.filter((targetUser) => {
        const roleValue = String(targetUser.role || '').toLowerCase();
        if (roleFilter !== 'all' && roleValue !== roleFilter) {
            return false;
        }

        const haystack = [
            targetUser.full_name,
            targetUser.email,
            targetUser.role,
            targetUser.status,
            targetUser.employee_id,
            targetUser.user_id
        ]
            .map((value) => String(value || '').toLowerCase())
            .join(' ');

        return term === '' || haystack.includes(term);
    });

    renderUsersSummary();
    renderUsersGrid();
    renderUsersPagination();
}

function renderUsersSummary() {
    const summary = document.getElementById('users-summary');
    if (!summary) return;

    const total = usersState.allUsers.length;
    const matched = usersState.filteredUsers.length;
    summary.textContent = total === matched
        ? `${total} account${total === 1 ? '' : 's'} shown`
        : `${matched} of ${total} accounts matched`;
}

function renderUsersGrid() {
    const container = document.getElementById('users-container');
    const users = usersState.filteredUsers;
    const start = (usersState.currentPage - 1) * usersState.pageSize;
    const pageUsers = users.slice(start, start + usersState.pageSize);

    if (!users || users.length === 0) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in users-empty-state"><strong>No users found.</strong><span>User records will appear here once accounts are created.</span></div>';
        return;
    }

    const cards = pageUsers.map((targetUser) => renderUserCard(targetUser)).join('');
    container.innerHTML = `<div class="users-grid ui-fade-in">${cards}</div>`;
}

function renderUsersPagination() {
    const pagination = document.getElementById('users-pagination');
    const totalPages = Math.ceil(usersState.filteredUsers.length / usersState.pageSize);

    if (!pagination || totalPages <= 1) {
        if (pagination) pagination.innerHTML = '';
        return;
    }

    const items = [];
    items.push(`<button type="button" class="users-page-nav ${usersState.currentPage === 1 ? 'is-disabled' : ''}" ${usersState.currentPage === 1 ? 'disabled' : ''} data-page="prev">&laquo;</button>`);

    for (let page = 1; page <= totalPages; page++) {
        items.push(`<button type="button" class="users-page-nav ${page === usersState.currentPage ? 'is-active' : ''}" data-page="${page}">${page}</button>`);
    }

    items.push(`<button type="button" class="users-page-nav ${usersState.currentPage === totalPages ? 'is-disabled' : ''}" ${usersState.currentPage === totalPages ? 'disabled' : ''} data-page="next">&raquo;</button>`);

    pagination.innerHTML = items.join('');
    pagination.querySelectorAll('[data-page]').forEach((button) => {
        button.addEventListener('click', () => {
            const pageValue = button.getAttribute('data-page');

            if (pageValue === 'prev') {
                usersState.currentPage = Math.max(1, usersState.currentPage - 1);
            } else if (pageValue === 'next') {
                usersState.currentPage = Math.min(totalPages, usersState.currentPage + 1);
            } else {
                usersState.currentPage = Number(pageValue);
            }

            renderUsersGrid();
            renderUsersPagination();
        });
    });
}

function renderUserCard(targetUser) {
    const safeId = Number(targetUser.user_id || 0);
    const safeEmployeeId = escapeHtml(targetUser.employee_id || 'Not assigned');
    const safeName = escapeHtml(targetUser.full_name || 'N/A');
    const safeEmail = escapeHtml(targetUser.email || 'N/A');
    const normalizedRole = String(targetUser.role || 'user').toLowerCase();
    const normalizedStatus = String(targetUser.status || '').toLowerCase();
    const avatarMarkup = buildAvatarMarkup(targetUser);
    const mediaMarkup = buildCardMediaMarkup(targetUser);
    const roleLabel = getRoleLabel(normalizedRole);
    const statusLabel = getStatusLabel(normalizedStatus);
    const statusClass = getStatusClass(normalizedStatus);

    let actionButtons = '';
    if (normalizedStatus === 'pending') {
        if (currentUserRole === 'super_admin') {
            actionButtons += `<button type="button" onclick="openApproveUserModal(${safeId}, '${escapeJsString(targetUser.full_name || '')}')" class="users-action-btn users-action-approve">Approve</button>`;
        }

        actionButtons += `<button type="button" onclick="openRejectUserModal(${safeId}, '${escapeJsString(targetUser.full_name || '')}')" class="users-action-btn users-action-reject">Reject</button>`;
    } else if (normalizedStatus === 'inactive') {
        actionButtons = `<button type="button" onclick="openActivateUserModal(${safeId}, '${escapeJsString(targetUser.full_name || '')}')" class="users-action-btn users-action-inactive" title="Set this user to active">Set Active</button>`;
    } else {
        actionButtons = `<button type="button" onclick="openInactiveUserModal(${safeId}, '${escapeJsString(targetUser.full_name || '')}')" class="users-action-btn users-action-activate" title="Set this user to inactive">Set Inactive</button>`;
    }

    return `
        <article class="users-card">
            <div class="users-card-media">
                ${mediaMarkup}
                <div class="users-card-badges">
                    <span class="users-role-pill users-role-${normalizedRole}">${escapeHtml(roleLabel)}</span>
                    <span class="users-status-pill ${statusClass}">${escapeHtml(statusLabel)}</span>
                </div>
            </div>
            <div class="users-card-body">
                <div class="users-card-headline">
                    <div class="users-card-name-row">
                        <h3>${safeName}</h3>
                        <div class="users-mini-avatar">${getInitials(targetUser.full_name || 'U')}</div>
                    </div>
                </div>
                <div class="users-card-meta">
                    <div class="users-card-row">
                        <span class="users-card-icon">#</span>
                        <span>${safeEmployeeId}</span>
                    </div>
                    <div class="users-card-row">
                        <span class="users-card-icon">✉</span>
                        <span>${safeEmail}</span>
                    </div>
                    <div class="users-card-row">
                        <span class="users-card-icon">◌</span>
                        <span>${escapeHtml(roleLabel)}</span>
                    </div>
                </div>
                <div class="users-card-actions">${actionButtons}</div>
            </div>
        </article>`;
}

function buildAvatarMarkup(targetUser) {
    const avatarUrl = normalizeAvatarUrl(targetUser.avatar);
    const initials = getInitials(targetUser.full_name || 'U');

    if (avatarUrl) {
        return `
            <div class="users-avatar-wrap">
                <img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(targetUser.full_name || 'User')} profile" class="users-avatar-image" loading="lazy" onerror="this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <div class="users-avatar-fallback users-avatar-fallback-hidden">${escapeHtml(initials)}</div>
            </div>`;
    }

    return `<div class="users-avatar-fallback">${escapeHtml(initials)}</div>`;
}

function buildCardMediaMarkup(targetUser) {
    const avatarUrl = normalizeAvatarUrl(targetUser.avatar);
    const initials = getInitials(targetUser.full_name || 'U');

    if (avatarUrl) {
        return `
            <div class="users-card-media-avatar-wrap">
                <img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(targetUser.full_name || 'User')} profile" class="users-card-media-image" loading="lazy" onerror="this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <div class="users-card-media-fallback users-card-media-fallback-hidden">
                    <div class="users-card-media-orbit"></div>
                    <div class="users-card-media-initials">${escapeHtml(initials)}</div>
                </div>
            </div>`;
    }

    return `
        <div class="users-card-media-fallback">
            <div class="users-card-media-orbit"></div>
            <div class="users-card-media-initials">${escapeHtml(initials)}</div>
        </div>`;
}

function normalizeAvatarUrl(avatar) {
    const value = String(avatar || '').trim();
    if (!value) return '';
    if (/^https?:\/\//i.test(value) || value.startsWith('/')) return value;
    if (window.SFMS_PUBLIC_URL) {
        return window.SFMS_PUBLIC_URL('/' + value.replace(/^\/?/, ''));
    }
    return '/' + value.replace(/^\/?/, '');
}

function getInitials(name) {
    return String(name || 'U')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'U';
}

function getRoleLabel(role) {
    const labels = {
        super_admin: 'Admin',
        maintenance_admin: 'Admin Maintenance',
        maintenance_staff: 'Maintenance Staff',
        user: 'Data Entry',
        client: 'Client'
    };

    if (labels[role]) {
        return labels[role];
    }

    return role.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function getStatusLabel(status) {
    const labels = {
        active: 'Active',
        pending: 'Pending',
        inactive: 'Inactive',
        suspended: 'Suspended'
    };

    return labels[status] || 'Inactive';
}

function getStatusClass(status) {
    if (status === 'active') return 'is-active';
    if (status === 'pending') return 'is-pending';
    return 'is-inactive';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeJsString(value) {
    return String(value ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/\r?\n/g, ' ');
}

function openRegisterUserModal() {
    if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
    }

    const existingModal = document.getElementById('register-user-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'register-user-modal';
    modal.className = 'users-modal-overlay';

    modal.innerHTML = `
        <div class="users-modal-card users-register-modal-card">
            <h3 class="users-modal-title">Register User</h3>
            <p class="users-modal-desc">Create a new active account. Employee ID is auto-generated based on today\'s date (example: 20260409). The user will be prompted to update their profile on first open.</p>
            <div class="users-modal-field">
                <label for="register-last-name" class="users-modal-label">Last Name</label>
                <input id="register-last-name" class="users-modal-input" type="text" placeholder="Enter last name" autocomplete="off" autocapitalize="words" autocorrect="off" spellcheck="false">
                <div id="register-last-name-error" class="users-modal-field-error"></div>
            </div>
            <div class="users-modal-field">
                <label for="register-first-name" class="users-modal-label">First Name</label>
                <input id="register-first-name" class="users-modal-input" type="text" placeholder="Enter first name" autocomplete="off" autocapitalize="words" autocorrect="off" spellcheck="false">
                <div id="register-first-name-error" class="users-modal-field-error"></div>
            </div>
            <div class="users-modal-field">
                <label for="register-middle-initial" class="users-modal-label">Middle Initial</label>
                <input id="register-middle-initial" class="users-modal-input" type="text" placeholder="Optional (e.g., D)" maxlength="1" autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false">
                <div id="register-middle-initial-error" class="users-modal-field-error"></div>
            </div>
            <div class="users-modal-field">
                <label for="register-suffix" class="users-modal-label">Suffix</label>
                <input id="register-suffix" class="users-modal-input" type="text" placeholder="Optional (e.g., Jr., Sr., III)" autocomplete="off" autocapitalize="words" autocorrect="off" spellcheck="false">
                <div id="register-suffix-error" class="users-modal-field-error"></div>
            </div>
            <div class="users-modal-field">
                <label for="register-email" class="users-modal-label">Email</label>
                <input id="register-email" class="users-modal-input" type="email" placeholder="Enter email" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                <div id="register-email-error" class="users-modal-field-error"></div>
            </div>
            <div class="users-modal-field" style="position:relative;">
                <label for="register-password" class="users-modal-label">Password</label>
                <input id="register-password" class="users-modal-input" type="password" placeholder="Minimum 8 characters" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" style="padding-right:44px;height:44px;line-height:44px;">
                <button
                    type="button"
                    id="toggle-register-password"
                    aria-label="Show password"
                    aria-pressed="false"
                    title="Show password"
                    style="position:absolute;right:12px;top:38px;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;padding:0;border:0;background:transparent;color:rgba(248,244,255,0.82);cursor:pointer;"
                >
                    <svg id="register-password-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2 12C3.8 7.9 7.5 5 12 5C16.5 5 20.2 7.9 22 12C20.2 16.1 16.5 19 12 19C7.5 19 3.8 16.1 2 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
                <div id="register-password-error" class="users-modal-field-error"></div>
            </div>
            <div class="users-modal-field">
                <label for="register-role" class="users-modal-label">Role</label>
                <select id="register-role" class="users-modal-select">
                    <option value="maintenance_admin">Admin Maintenance</option>
                    <option value="maintenance_staff" selected>Maintenance Staff</option>
                </select>
            </div>
            <div class="users-modal-actions">
                <button type="button" id="cancel-register-btn" class="users-modal-btn users-modal-btn-secondary">Cancel</button>
                <button type="button" id="confirm-register-btn" class="users-modal-btn users-modal-btn-primary">Create</button>
            </div>
        </div>`;

    document.body.appendChild(modal);

    const closeModal = () => {
        const el = document.getElementById('register-user-modal');
        if (el) el.remove();
    };

    document.getElementById('cancel-register-btn')?.addEventListener('click', closeModal);
    
    // Add real-time validation
    const lastNameInput = document.getElementById('register-last-name');
    const firstNameInput = document.getElementById('register-first-name');
    const middleInitialInput = document.getElementById('register-middle-initial');
    const suffixInput = document.getElementById('register-suffix');
    const emailInput = document.getElementById('register-email');
    const passwordInput = document.getElementById('register-password');
    
    lastNameInput?.addEventListener('blur', () => validateRequiredNameField(lastNameInput, 'Last name'));
    firstNameInput?.addEventListener('blur', () => validateRequiredNameField(firstNameInput, 'First name'));
    middleInitialInput?.addEventListener('blur', () => validateMiddleInitialField(middleInitialInput));
    suffixInput?.addEventListener('blur', () => validateSuffixField(suffixInput));
    emailInput?.addEventListener('blur', () => validateEmailField(emailInput));
    passwordInput?.addEventListener('blur', () => validatePasswordField(passwordInput));

        // Password eye icon toggle
        const toggleRegisterPasswordBtn = document.getElementById('toggle-register-password');
        const registerPasswordInput = document.getElementById('register-password');
        const registerPasswordEye = document.getElementById('register-password-eye');
        if (toggleRegisterPasswordBtn && registerPasswordInput && registerPasswordEye) {
            toggleRegisterPasswordBtn.addEventListener('click', () => {
                const isVisible = registerPasswordInput.type === 'text';
                registerPasswordInput.type = isVisible ? 'password' : 'text';
                toggleRegisterPasswordBtn.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                toggleRegisterPasswordBtn.setAttribute('aria-pressed', String(!isVisible));
                toggleRegisterPasswordBtn.setAttribute('title', isVisible ? 'Show password' : 'Hide password');
                registerPasswordEye.innerHTML = isVisible
                    ? '<path d="M2 12C3.8 7.9 7.5 5 12 5C16.5 5 20.2 7.9 22 12C20.2 16.1 16.5 19 12 19C7.5 19 3.8 16.1 2 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>'
                    : '<path d="M3 3L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.6 10.7C10.2 11.1 10 11.5 10 12C10 13.1 10.9 14 12 14C12.5 14 12.9 13.8 13.3 13.4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.9 5.2C10.6 5.1 11.3 5 12 5C16.5 5 20.2 7.9 22 12C21.3 13.6 20.2 15 18.8 16.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 6.3C4.6 7.5 3.2 9.4 2 12C3.8 16.1 7.5 19 12 19C13.7 19 15.3 18.6 16.6 17.9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
            });
        }
    
    document.getElementById('confirm-register-btn')?.addEventListener('click', async () => {
        const lastName = lastNameInput.value.trim();
        const firstName = firstNameInput.value.trim();
        const middleInitial = middleInitialInput.value.trim();
        const suffix = suffixInput.value.trim();
        const fullName = buildFullName(lastName, firstName, middleInitial);
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const role = document.getElementById('register-role').value;

        // Validate all fields
        const lastNameError = getRequiredNameValidationError(lastName, 'Last name');
        const firstNameError = getRequiredNameValidationError(firstName, 'First name');
        const middleInitialError = getMiddleInitialValidationError(middleInitial);
        const suffixError = getSuffixValidationError(suffix);
        const emailError = getEmailValidationError(email);
        const passwordError = getPasswordValidationError(password);

        // Display inline errors in form
        setFieldValidationState(lastNameInput, lastNameError);
        setFieldValidationState(firstNameInput, firstNameError);
        setFieldValidationState(middleInitialInput, middleInitialError);
        setFieldValidationState(suffixInput, suffixError);
        setFieldValidationState(emailInput, emailError);
        setFieldValidationState(passwordInput, passwordError);

        // Show errors if any exist
        if (lastNameError || firstNameError || middleInitialError || suffixError || emailError || passwordError) {
            return;
        }

        if (!role) {
            showPageAlert('Please select a role.', 'error');
            return;
        }

        try {
            const response = await fetch(`${window.API.baseURL}/users-api.php?action=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    last_name: lastName,
                    first_name: firstName,
                    middle_initial: middleInitial,
                    suffix: suffix,
                    full_name: fullName,
                    email,
                    password,
                    role
                })
            });

            const data = await response.json();

            if (data.success) {
                const generatedEmployeeId = data?.data?.employee_id || data?.employee_id || '';
                const successMessage = generatedEmployeeId
                    ? `User created successfully. Generated ID: ${generatedEmployeeId}`
                    : 'User created successfully and marked for profile setup.';

                showPageAlert(successMessage, 'success');
                closeModal();
                loadUsers();
            } else {
                showPageAlert('Error: ' + (data.message || 'Failed to create user'), 'error');
            }
        } catch (error) {
            console.error('Register error:', error);
            showPageAlert('Failed to create user: ' + error.message, 'error');
        }
    });

}

function openApproveUserModal(userId, userName) {
    const existingModal = document.getElementById('approve-user-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'approve-user-modal';
    modal.className = 'users-modal-overlay';

    modal.innerHTML = `
        <div class="users-modal-card">
            <h3 class="users-modal-title">Approve Pending User</h3>
            <p class="users-modal-desc">Approve <strong>${escapeHtml(userName || 'N/A')}</strong> and assign role.</p>
            <div class="users-modal-field">
                <label for="approve-user-role" class="users-modal-label">Assign role</label>
                <select id="approve-user-role" class="users-modal-select">
                    <option value="maintenance_staff" selected>Maintenance Staff</option>
                    <option value="maintenance_admin">Admin Maintenance</option>
                </select>
            </div>
            <div class="users-modal-actions">
                <button type="button" id="cancel-approve-btn" class="users-modal-btn users-modal-btn-secondary">Cancel</button>
                <button type="button" id="confirm-approve-btn" class="users-modal-btn users-modal-btn-primary">Approve</button>
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
    modal.className = 'users-modal-overlay';

    modal.innerHTML = `
        <div class="users-modal-card">
            <h3 class="users-modal-title">Set User Inactive</h3>
            <p class="users-modal-desc">Set <strong>${escapeHtml(userName || 'N/A')}</strong> to inactive? This account will no longer be able to log in.</p>
            <div class="users-modal-actions">
                <button type="button" id="cancel-inactive-btn" class="users-modal-btn users-modal-btn-secondary">Cancel</button>
                <button type="button" id="confirm-inactive-btn" class="users-modal-btn users-modal-btn-warning">Inactive</button>
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

function openRejectUserModal(userId, userName) {
    const existingModal = document.getElementById('reject-user-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'reject-user-modal';
    modal.className = 'users-modal-overlay';

    modal.innerHTML = `
        <div class="users-modal-card">
            <h3 class="users-modal-title">Reject Pending User</h3>
            <p class="users-modal-desc">Reject <strong>${escapeHtml(userName || 'N/A')}</strong>? This will permanently delete the pending account.</p>
            <div class="users-modal-actions">
                <button type="button" id="cancel-reject-btn" class="users-modal-btn users-modal-btn-secondary">Cancel</button>
                <button type="button" id="confirm-reject-btn" class="users-modal-btn users-modal-btn-danger">Reject</button>
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
    modal.className = 'users-modal-overlay';

    modal.innerHTML = `
        <div class="users-modal-card">
            <h3 class="users-modal-title">Set User Active</h3>
            <p class="users-modal-desc">Set <strong>${escapeHtml(userName || 'N/A')}</strong> to active? This account will be able to log in again.</p>
            <div class="users-modal-actions">
                <button type="button" id="cancel-activate-btn" class="users-modal-btn users-modal-btn-secondary">Cancel</button>
                <button type="button" id="confirm-activate-btn" class="users-modal-btn users-modal-btn-success">Active</button>
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

    const cls = type === 'success' ? 'users-page-alert users-page-alert-success' : 'users-page-alert users-page-alert-error';
    alertContainer.innerHTML = `<div class="${cls}">${escapeHtml(message)}</div>`;

    setTimeout(() => {
        if (alertContainer) alertContainer.innerHTML = '';
    }, 3000);
}

/**
 * Validation functions for user registration form
 */

// Name-part validation: letters, spaces, apostrophes, and hyphens only.
function getRequiredNameValidationError(value, label) {
    if (!value) {
        return `❌ ${label} is required.`;
    }

    // Regex: Only allows A-Z, a-z, spaces, hyphens, and apostrophes.
    const nameRegex = /^[a-zA-Z\s'-]+$/;
    if (!nameRegex.test(value)) {
        return `❌ ${label} can only contain letters, spaces, hyphens, and apostrophes.`;
    }

    if (value.length < 2) {
        return `❌ ${label} must be at least 2 characters long.`;
    }

    if (value.length > 100) {
        return `❌ ${label} must not exceed 100 characters.`;
    }

    return null;
}

function getMiddleInitialValidationError(value) {
    if (!value) {
        return null;
    }

    if (!/^[a-zA-Z]$/.test(value)) {
        return '❌ Middle Initial must be a single letter (A-Z).';
    }

    return null;
}

function buildFullName(lastName, firstName, middleInitial) {
    const nameParts = [`${lastName},`, firstName];

    if (middleInitial) {
        nameParts.push(`${middleInitial.toUpperCase()}.`);
    }

    return nameParts.join(' ').trim();
}

// Email validation: must be a valid Gmail address
function getEmailValidationError(email) {
    if (!email) {
        return '❌ Email is required.';
    }

    // Accept only Gmail addresses and reject typo domains like @gamial.com.
    const gmailRegex = /^[a-zA-Z0-9._+-]+@gmail\.com$/i;
    if (!gmailRegex.test(email)) {
        return '❌ Email must be a valid Gmail address ending with @gmail.com.';
    }

    // Check email length
    if (email.length > 254) {
        return '❌ Email is too long. Maximum 254 characters allowed.';
    }

    return null;
}

// Password validation: Minimum 8 characters
function getPasswordValidationError(password) {
    if (!password) {
        return '❌ Password is required.';
    }

    if (password.length < 8) {
        return '❌ Password must be at least 8 characters long.';
    }

    if (password.length > 255) {
        return '❌ Password is too long.';
    }

    return null;
}

// Real-time validation handlers
function validateRequiredNameField(input, label) {
    const error = getRequiredNameValidationError(input.value.trim(), label);
    setFieldValidationState(input, error);
}

function validateEmailField(input) {
    const error = getEmailValidationError(input.value.trim());
    setFieldValidationState(input, error);
}

function validatePasswordField(input) {
    const error = getPasswordValidationError(input.value);
    setFieldValidationState(input, error);
}

function validateMiddleInitialField(input) {
    const error = getMiddleInitialValidationError(input.value.trim());
    setFieldValidationState(input, error);
}

function getSuffixValidationError(value) {
    if (!value) {
        return null;
    }

    if (value.length > 50) {
        return '❌ Suffix must be 50 characters or less.';
    }

    // Allow letters, numbers, periods, commas, and hyphens
    if (!/^[a-zA-Z0-9.,\-\s]+$/.test(value)) {
        return '❌ Suffix can only contain letters, numbers, periods, commas, and hyphens.';
    }

    return null;
}

function validateSuffixField(input) {
    const error = getSuffixValidationError(input.value.trim());
    setFieldValidationState(input, error);
}

// Helper to show/hide validation state on field and display inline errors
function setFieldValidationState(input, error) {
    const fieldId = input.id;
    const errorContainer = document.getElementById(fieldId + '-error');
    
    if (error) {
        input.classList.add('users-modal-input-error');
        input.title = error.replace(/^❌ /, '');
        // Show error message in modal
        if (errorContainer) {
            errorContainer.textContent = error.replace(/^❌ /, '');
            errorContainer.classList.add('is-visible');
        }
    } else {
        input.classList.remove('users-modal-input-error');
        input.title = '';
        // Hide error message in modal
        if (errorContainer) {
            errorContainer.textContent = '';
            errorContainer.classList.remove('is-visible');
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>


