/**
 * Page initialization scripts
 */

const ThemeManager = {
    storageKey: 'sfmsThemeMode',
    mediaQuery: null,
    mediaListener: null,

    getSavedMode() {
        // Force dark mode as the system default theme.
        return 'dark';
    },

    resolveMode(mode) {
        if (mode === 'auto') {
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            return prefersDark ? 'dark' : 'light';
        }
        return mode;
    },

    applyMode(mode, options = {}) {
        const { persist = true } = options;
        const safeMode = ['light', 'dark', 'auto'].includes(mode) ? mode : 'light';
        const resolved = this.resolveMode(safeMode);
        const root = document.documentElement;

        if (persist) {
            localStorage.setItem(this.storageKey, safeMode);
        }

        root.setAttribute('data-theme-mode', safeMode);
        root.setAttribute('data-theme-resolved', resolved);
        root.style.colorScheme = resolved === 'dark' ? 'dark' : 'light';
    },

    init() {
        this.applyMode(this.getSavedMode(), { persist: false });

        if (!window.matchMedia) return;

        this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        if (!this.mediaListener) {
            this.mediaListener = () => {
                if (this.getSavedMode() === 'auto') {
                    this.applyMode('auto', { persist: false });
                }
            };
        }

        if (typeof this.mediaQuery.addEventListener === 'function') {
            this.mediaQuery.addEventListener('change', this.mediaListener);
        } else if (typeof this.mediaQuery.addListener === 'function') {
            this.mediaQuery.addListener(this.mediaListener);
        }
    }
};

window.ThemeManager = window.ThemeManager || ThemeManager;

const AccessibilityManager = {
    storageKey: 'sfms_settings_font_size',

    getSavedFontSize() {
        const size = localStorage.getItem(this.storageKey);
        return ['small', 'medium', 'large'].includes(size) ? size : 'medium';
    },

    getScale(size) {
        const scaleMap = {
            small: 0.92,
            medium: 1,
            large: 1.12
        };

        return Object.prototype.hasOwnProperty.call(scaleMap, size) ? scaleMap[size] : scaleMap.medium;
    },

    applyFontSize(size, options = {}) {
        const { persist = true } = options;
        const safeSize = ['small', 'medium', 'large'].includes(size) ? size : 'medium';
        const scale = this.getScale(safeSize);
        const root = document.documentElement;

        root.setAttribute('data-font-size-mode', safeSize);
        root.style.setProperty('--ui-font-scale', String(scale));
        root.style.setProperty('--ui-zoom', '1');

        if (persist) {
            localStorage.setItem(this.storageKey, safeSize);
        }
    },

    init() {
        this.applyFontSize(this.getSavedFontSize(), { persist: false });
    }
};

window.AccessibilityManager = window.AccessibilityManager || AccessibilityManager;

document.addEventListener('DOMContentLoaded', function() {
    AccessibilityManager.init();
    ThemeManager.init();
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
    
    // Set up logout buttons (with confirmation modal)
    const logoutButtons = document.querySelectorAll('[data-logout]');
    const logoutModalId = 'global-logout-modal';
    const logoutConfirmBtn = document.getElementById('global-logout-confirm');
    const logoutCancelBtn = document.getElementById('global-logout-cancel');

    async function performLogout() {
        try {
            if (window.API && typeof API.logout === 'function') {
                await API.logout();
            } else if (window.api && typeof api.logout === 'function') {
                await api.logout();
            }
        } catch (error) {
            // Ignore API failures and fall back to server-side logout.
        } finally {
            Session.clear();
            window.location.href = '/School_Facility_Maintenance_System/frontend/pages/logout.php';
        }
    }

    logoutButtons.forEach((logoutBtn) => {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (document.getElementById(logoutModalId)) {
                UI.toggleModal(logoutModalId, true);
                return;
            }
            performLogout();
        });
    });

    if (logoutConfirmBtn) {
        logoutConfirmBtn.addEventListener('click', async () => {
            UI.toggleModal(logoutModalId, false);
            await performLogout();
        });
    }

    if (logoutCancelBtn) {
        logoutCancelBtn.addEventListener('click', () => {
            UI.toggleModal(logoutModalId, false);
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
