<?php
/**
 * User Management Page
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

$user = $_SESSION['user'];
$userRole = $user['role'] ?? 'user';

$pageTitle = 'User Management - SFMS';
include __DIR__ . '/../includes/header.php';

if (!in_array($userRole, ['super_admin', 'department_admin'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}
?>

<main class="container">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div style="flex: 1;">
                <h2>User Management</h2>
                <p class="text-muted mb-0">Manage system users and permissions</p>
            </div>
            <button onclick="showAddUserForm()" class="btn btn-primary" style="height: fit-content; margin-top: 0;">
                + Add User
            </button>
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
    
    let html = '<table class="table" style="width: 100%; border-collapse: collapse;">';
    html += '<thead><tr style="background: #f0f0f0;"><th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Name</th>';
    html += '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Email</th>';
    html += '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Role</th>';
    html += '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Status</th>';
    html += '<th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Actions</th></tr></thead>';
    html += '<tbody>';
    
    users.forEach(user => {
        const statusBadge = user.status === 'active' 
            ? '<span style="background: #2d9d78; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Active</span>'
            : '<span style="background: #8b2020; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">Inactive</span>';
        
        const deleteBtn = `<button onclick="deleteUser(${user.user_id}, '${user.full_name}')" 
                          style="background: #c62828; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                          Delete
                          </button>`;
        
        html += `<tr style="border: 1px solid #ddd;">
            <td style="padding: 10px; border: 1px solid #ddd;">${user.full_name || 'N/A'}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">${user.email || 'N/A'}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">${(user.role || 'user').replace(/_/g, ' ').toUpperCase()}</td>
            <td style="padding: 10px; border: 1px solid #ddd;">${statusBadge}</td>
            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">${deleteBtn}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

async function deleteUser(userId, userName) {
    if (!confirm(`Are you sure you want to delete "${userName}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${window.API.baseURL}/users-api.php?action=delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ user_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('User deleted successfully!');
            loadUsers(); // Reload the user list
        } else {
            alert('Error: ' + (data.message || 'Failed to delete user'));
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Failed to delete user: ' + error.message);
    }
}

function showAddUserForm() {
    const modal = document.createElement('div');
    modal.id = 'add-user-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    `;
    
    modal.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.5rem;">Add New User</h3>
                <button onclick="closeAddUserModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">×</button>
            </div>
            
            <form id="add-user-form" onsubmit="submitAddUserForm(event)">
                <div id="form-messages"></div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="John Doe" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;" 
                           required>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">Email *</label>
                    <input type="email" id="email" name="email" placeholder="user@school.edu" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;" 
                           required>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">Password *</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 8 characters" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;" 
                           required minlength="8">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">User Role *</label>
                    <select id="role" name="role" 
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;" 
                            required>
                        <option value="">-- Select Role --</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="department_admin">Department Admin</option>
                        <option value="maintenance_staff">Maintenance Staff</option>
                        <option value="user">User</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeAddUserModal()" 
                            style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer; font-size: 1rem;">
                        Cancel
                    </button>
                    <button type="submit" 
                            style="padding: 0.75rem 1.5rem; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeAddUserModal() {
    const modal = document.getElementById('add-user-modal');
    if (modal) {
        modal.remove();
    }
}

async function submitAddUserForm(event) {
    event.preventDefault();
    
    const formMessages = document.getElementById('form-messages');
    const fullName = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;
    
    // Client-side validation
    if (!fullName || !email || !password || !role) {
        formMessages.innerHTML = '<div style="background: #ffebee; color: #c62828; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Please fill in all required fields</div>';
        return;
    }
    
    if (password.length < 8) {
        formMessages.innerHTML = '<div style="background: #ffebee; color: #c62828; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Password must be at least 8 characters</div>';
        return;
    }
    
    if (!email.includes('@')) {
        formMessages.innerHTML = '<div style="background: #ffebee; color: #c62828; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Invalid email address</div>';
        return;
    }
    
    try {
        formMessages.innerHTML = '<div style="background: #e3f2fd; color: #1565c0; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Creating user...</div>';
        
        const response = await fetch(`${window.API.baseURL}/auth-api.php?action=register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                full_name: fullName,
                email: email,
                password: password,
                role: role
            })
        });
        
        console.log('Response status:', response.status, 'OK:', response.ok);
        
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Failed to parse JSON response:', parseError);
            
            // If response is not JSON, check if it's HTML error
            if (responseText.includes('<!DOCTYPE') || responseText.includes('<html')) {
                formMessages.innerHTML = '<div style="background: #ffebee; color: #c62828; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Server error. Please check your input and try again.</div>';
            } else {
                formMessages.innerHTML = '<div style="background: #ffebee; color: #c62828; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Server response error. Response: ' + responseText.substring(0, 100) + '</div>';
            }
            return;
        }
        
        console.log('Parsed data:', data);
        
        if (data.success || response.status === 201 || response.status === 200) {
            formMessages.innerHTML = '<div style="background: #e8f5e9; color: #2e7d32; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">✓ User created successfully!</div>';
            
            // Clear form
            document.getElementById('add-user-form').reset();
            
            // Close modal after 1.5 seconds
            setTimeout(() => {
                closeAddUserModal();
                loadUsers(); // Reload users list
            }, 1500);
        } else {
            // Extract error message
            const errorMsg = data.message || data.error || data.data?.message || 'Failed to create user';
            console.error('Error response:', data);
            formMessages.innerHTML = `<div style="background: #ffebee; color: #c62828; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">${errorMsg}</div>`;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        formMessages.innerHTML = `<div style="background: #ffebee; color: #c62828; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Network error: ${error.message}</div>`;
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
