/**
 * Page initialization scripts
 */

const ThemeManager = {
    storageKey: 'sfmsThemeMode',
    legacyStorageKeys: ['sfms_settings_theme', 'sfms_theme_mode'],
    mediaQuery: null,
    mediaListener: null,

    getSavedMode() {
        const candidates = [this.storageKey, ...this.legacyStorageKeys];

        for (const key of candidates) {
            const stored = localStorage.getItem(key);
            if (['light', 'dark', 'auto'].includes(stored)) {
                return stored;
            }
        }

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
            this.legacyStorageKeys.forEach((key) => {
                localStorage.setItem(key, safeMode);
            });
        }

        root.setAttribute('data-theme-mode', safeMode);
        root.setAttribute('data-theme-resolved', resolved);
        root.setAttribute('data-theme', resolved);
        root.style.colorScheme = resolved === 'dark' ? 'dark' : 'light';

        this.updateToggleButtons(resolved);
    },

    toggleMode() {
        const currentMode = this.getSavedMode();
        const nextMode = currentMode === 'dark' ? 'light' : 'dark';
        this.applyMode(nextMode);
        return nextMode;
    },

    getToggleIcon(mode) {
        if (mode === 'dark') {
            return '<svg class="theme-toggle-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1 1 11.21 3 7.5 7.5 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }

        return '<svg class="theme-toggle-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><path d="M12 2V4M12 20V22M4.93 4.93L6.34 6.34M17.66 17.66L19.07 19.07M2 12H4M20 12H22M4.93 19.07L6.34 17.66M17.66 6.34L19.07 4.93" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
    },

    updateToggleButtons(resolvedMode) {
        const buttons = document.querySelectorAll('[data-theme-toggle]');
        if (!buttons.length) return;

        buttons.forEach((button) => {
            const nextMode = resolvedMode === 'dark' ? 'light' : 'dark';
            button.innerHTML = this.getToggleIcon(resolvedMode);
            button.setAttribute('aria-label', `Switch to ${nextMode} mode`);
            button.setAttribute('title', `Switch to ${nextMode} mode`);
            button.setAttribute('data-theme-current', resolvedMode);
        });
    },

    bindToggleButtons() {
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            if (button.dataset.themeToggleBound === '1') return;
            button.dataset.themeToggleBound = '1';
            button.addEventListener('click', () => {
                this.toggleMode();
            });
        });
    },

    init() {
        this.applyMode(this.getSavedMode(), { persist: true });
        this.bindToggleButtons();

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

function updateGlobalHeaderKicker() {
    const greetingEl = document.getElementById('headerProfileGreeting');
    const subtextEl = document.getElementById('headerProfileSubtext');
    const legacyKickerEl = document.getElementById('headerDashboardKicker');
    if (!greetingEl && !subtextEl && !legacyKickerEl) return;

    const now = new Date();
    const hour = now.getHours();
    const user = Session.get('user');
    const userName = (user && (user.full_name || user.username)) ? String(user.full_name || user.username) : 'User';

    let greeting = 'Good evening';
    if (hour < 12) {
        greeting = 'Good morning';
    } else if (hour < 18) {
        greeting = 'Good afternoon';
    }

    const dateText = new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric'
    }).format(now);

    const timeText = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit'
    }).format(now);

    if (greetingEl) {
        greetingEl.textContent = `${greeting}, ${userName}`;
    }

    if (subtextEl) {
        subtextEl.textContent = `${dateText} · ${timeText}`;
    }

    if (legacyKickerEl) {
        legacyKickerEl.textContent = `${dateText} · ${timeText} ${greeting}`;
        legacyKickerEl.classList.add('is-visible');
    }
}

window.updateGlobalHeaderKicker = window.updateGlobalHeaderKicker || updateGlobalHeaderKicker;

document.addEventListener('DOMContentLoaded', function() {
    AccessibilityManager.init();
    ThemeManager.init();
    initializeNavigation();
    initializeModals();
    initializeDropdowns();
    updateGlobalHeaderKicker();
    setInterval(updateGlobalHeaderKicker, 60000);
});

/**
 * Initialize navigation
 */
function initializeNavigation() {
    // Check if user is logged in
    const user = Session.get('user');
    
    if (!user && window.location.pathname !== '/index.php' && !window.location.pathname.includes('login')) {
        window.location.href = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/frontend/pages/index.php') : '/frontend/pages/index.php';
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
            window.location.href = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/frontend/pages/logout.php') : '/frontend/pages/logout.php';
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
