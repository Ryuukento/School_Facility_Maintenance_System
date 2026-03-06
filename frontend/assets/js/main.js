/**
 * Page initialization scripts
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeModals();
    initializeDropdowns();
});

/**
 * Initialize navigation
 */
function initializeNavigation() {
    // Check if user is logged in
    const user = Session.get('user');
    
    if (!user && window.location.pathname !== '/index.php' && !window.location.pathname.includes('login')) {
        window.location.href = '/index.php';
        return;
    }
    
    // Set up logout button
    const logoutBtn = document.querySelector('[data-logout]');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await api.logout();
                Session.clear();
                window.location.href = '/index.php';
            } catch (error) {
                UI.toast('Logout failed', 'danger');
            }
        });
    }
    
    // Update user display
    if (user) {
        const userNameEl = document.querySelector('[data-user-name]');
        const userAvatarEl = document.querySelector('[data-user-avatar]');
        
        if (userNameEl) userNameEl.textContent = user.full_name;
        if (userAvatarEl) userAvatarEl.textContent = user.full_name.charAt(0).toUpperCase();
    }
}

/**
 * Initialize modals
 */
function initializeModals() {
    // Close modal when clicking close button
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            UI.toggleModal(modal.id, false);
        });
    });
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                UI.toggleModal(modal.id, false);
            }
        });
    });
}

/**
 * Initialize dropdowns
 */
function initializeDropdowns() {
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdown = e.target.closest('.dropdown');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                if (m !== menu) m.classList.remove('show');
            });
            
            menu.classList.toggle('show');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                m.classList.remove('show');
            });
        }
    });
}

/**
 * Add CSS for toast notifications
 */
const toastStyles = `
<style>
    .toast {
        background-color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        margin-bottom: 0.5rem;
        border-left: 4px solid;
        animation: slideIn 0.3s ease;
    }
    
    .toast-success {
        background-color: #ecfdf5;
        color: #065f46;
        border-color: #22c55e;
    }
    
    .toast-danger {
        background-color: #fef2f2;
        color: #7f1d1d;
        border-color: #ef4444;
    }
    
    .toast-warning {
        background-color: #fffbeb;
        color: #78350f;
        border-color: #f59e0b;
    }
    
    .toast-info {
        background-color: #eff6ff;
        color: #0c2340;
        border-color: #3b82f6;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: inherit;
    }
</style>
`;

document.head.insertAdjacentHTML('beforeend', toastStyles);
