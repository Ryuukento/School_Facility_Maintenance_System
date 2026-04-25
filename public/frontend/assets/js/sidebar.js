/**
 * FacilityFlow Sidebar Navigation JavaScript
 * Handles active state, toggle functionality, and responsive behavior
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const navLinks = document.querySelectorAll('.nav-link');
    const navItems = document.querySelectorAll('.nav-item');
    const isForceProfileSetup = document.body?.dataset?.forceProfileSetup === '1';

    // ========================================
    // INITIALIZATION
    // ========================================

    /**
     * Initialize sidebar on page load
     * Sets up event listeners and applies saved preferences
     */
    function initSidebar() {
        applyActiveState();
        attachEventListeners();
        setResponsiveMode();
        loadSidebarPreference();
    }

    // ========================================
    // ACTIVE STATE MANAGEMENT
    // ========================================

    /**
     * Apply active state to current page link
     * Uses PHP-set active class as fallback
     */
    function applyActiveState() {
        let activeLink = document.querySelector('.nav-link.active');

        if (!activeLink) {
            const currentPath = window.location.pathname.toLowerCase();
            const currentFile = currentPath.split('/').pop();

            activeLink = Array.from(navLinks).find((link) => {
                const href = link.getAttribute('href');

                if (!href || href === '#' || href.startsWith('javascript:')) {
                    return false;
                }

                try {
                    const linkUrl = new URL(href, window.location.origin);
                    const linkFile = linkUrl.pathname.toLowerCase().split('/').pop();
                    return linkFile !== '' && linkFile === currentFile;
                } catch (error) {
                    return false;
                }
            });
        }
        
        if (activeLink) {
            addActiveHighlight(activeLink);
        }
    }

    /**
     * Add visual highlight to active link
     * @param {HTMLElement} link - The nav link to highlight
     */
    function addActiveHighlight(link) {
        // Remove active class from all links
        navLinks.forEach(l => {
            l.classList.remove('active');
        });

        // Add active class to specified link
        link.classList.add('active');

        // Save preference
        saveSidebarPreference();
    }

    // ========================================
    // EVENT LISTENERS
    // ========================================

    /**
     * Attach all event listeners to sidebar elements
     */
    function attachEventListeners() {
        // Toggle button click
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (sidebarToggleMobile) {
            sidebarToggleMobile.addEventListener('click', toggleSidebar);
        }

        // Overlay click (closes sidebar on mobile)
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        // Navigation link clicks
        navLinks.forEach(link => {
            link.addEventListener('click', handleNavLinkClick);
        });

        // Window resize listener for responsive behavior
        window.addEventListener('resize', setResponsiveMode);

        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });
    }

    /**
     * Handle navigation link clicks
     * @param {Event} e - The click event
     */
    function handleNavLinkClick(e) {
        if (isForceProfileSetup) {
            const page = this.getAttribute('data-page') || '';
            const isLogout = this.hasAttribute('data-logout') || this.classList.contains('logout-link');
            const isAllowedDuringSetup = page === 'account' || isLogout;

            if (!isAllowedDuringSetup) {
                e.preventDefault();
                return;
            }
        }

        // Only handle internal links (not logout)
        const href = this.getAttribute('href');
        
        if (!href || href.includes('logout') || href.startsWith('http')) {
            return;
        }

        // Highlight the clicked link
        addActiveHighlight(this);

        // Close sidebar on mobile after clicking
        if (isMobileView()) {
            closeSidebar();
        }
    }

    // ========================================
    // SIDEBAR TOGGLE FUNCTIONALITY
    // ========================================

    /**
     * Toggle sidebar open/close
     */
    function toggleSidebar() {
        if (isMobileView()) {
            sidebar.classList.toggle('mobile-open');
            sidebar.classList.remove('collapsed');
            document.body.classList.toggle('sidebar-mobile-open', sidebar.classList.contains('mobile-open'));
            if (sidebarToggleMobile) {
                sidebarToggleMobile.setAttribute('aria-expanded', sidebar.classList.contains('mobile-open') ? 'true' : 'false');
            }
            return;
        }

        sidebar.classList.toggle('collapsed');
    }

    /**
     * Close sidebar (mobile)
     */
    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
        document.body.classList.remove('sidebar-mobile-open');
        if (sidebarToggleMobile) {
            sidebarToggleMobile.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Open sidebar (mobile)
     */
    function openSidebar() {
        if (isMobileView()) {
            sidebar.classList.add('mobile-open');
            sidebar.classList.remove('collapsed');
            document.body.classList.add('sidebar-mobile-open');
            if (sidebarToggleMobile) {
                sidebarToggleMobile.setAttribute('aria-expanded', 'true');
            }
        }
    }

    // ========================================
    // RESPONSIVE BEHAVIOR
    // ========================================

    /**
     * Determine if current view is mobile
     * @returns {boolean} True if viewport width is 768px or less
     */
    function isMobileView() {
        return window.innerWidth <= 768;
    }

    /**
     * Set responsive mode based on viewport width
     */
    function setResponsiveMode() {
        if (isMobileView()) {
            // Mobile mode: keep sidebar hidden until opened from the header toggle
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('mobile-open');
            document.body.classList.remove('sidebar-mobile-open');
            if (sidebarToggleMobile) {
                sidebarToggleMobile.setAttribute('aria-expanded', 'false');
            }
        } else {
            // Desktop mode: show full sidebar
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('mobile-open');
            document.body.classList.remove('sidebar-mobile-open');
            if (sidebarToggleMobile) {
                sidebarToggleMobile.setAttribute('aria-expanded', 'false');
            }
        }
    }

    // ========================================
    // LOCAL STORAGE PREFERENCES
    // ========================================

    /**
     * Save sidebar preferences to local storage
     */
    function saveSidebarPreference() {
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('facilityflow_sidebar_collapsed', isCollapsed);
    }

    /**
     * Load sidebar preferences from local storage
     */
    function loadSidebarPreference() {
        if (isMobileView()) {
            return; // Don't use saved state on mobile
        }

        const isCollapsed = localStorage.getItem('facilityflow_sidebar_collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }

    /**
     * Add collapse/expand functionality via double-click on header
     */
    const sidebarHeader = document.querySelector('.sidebar-header');
    if (sidebarHeader && !isMobileView()) {
        sidebarHeader.addEventListener('dblclick', function() {
            sidebar.classList.toggle('collapsed');
            saveSidebarPreference();
        });
    }

    // ========================================
    // KEYBOARD ACCESSIBILITY
    // ========================================

    /**
     * Handle keyboard navigation within sidebar
     */
    navLinks.forEach((link, index) => {
        link.addEventListener('keydown', function(e) {
            let targetLink;

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    targetLink = navLinks[index + 1];
                    if (targetLink) targetLink.focus();
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    targetLink = navLinks[index - 1];
                    if (targetLink) targetLink.focus();
                    break;

                case 'Enter':
                case ' ':
                    e.preventDefault();
                    this.click();
                    break;
            }
        });
    });

    // ========================================
    // PUBLIC API (for external use)
    // ========================================

    window.FacilityFlowSidebar = {
        /**
         * Programmatically set active nav item
         * @param {string} page - The page identifier (e.g., 'dashboard')
         */
        setActivePage: function(page) {
            const link = document.querySelector(`[data-page="${page}"]`);
            if (link) {
                addActiveHighlight(link);
            }
        },

        /**
         * Toggle sidebar visibility
         */
        toggle: toggleSidebar,

        /**
         * Open sidebar (mobile only)
         */
        open: openSidebar,

        /**
         * Close sidebar (mobile only)
         */
        close: closeSidebar,

        /**
         * Check if sidebar is collapsed
         * @returns {boolean}
         */
        isCollapsed: function() {
            return sidebar.classList.contains('collapsed');
        },

        /**
         * Get current active page
         * @returns {string|null} The page identifier or null
         */
        getActivePage: function() {
            const activeLink = document.querySelector('.nav-link.active');
            return activeLink ? activeLink.getAttribute('data-page') : null;
        }
    };

    // ========================================
    // INITIALIZATION CALL
    // ========================================
    initSidebar();
});

/**
 * Utility: Smooth scroll to section
 * Can be used for future in-page navigation
 */
function smoothScroll(selector) {
    const element = document.querySelector(selector);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

/**
 * Utility: Update active link programmatically
 * Usage: updateActiveNavLink('/dashboard.php')
 */
function updateActiveNavLink(url) {
    const currentPage = url.split('/').pop() || 'index.php';
    const link = document.querySelector(`a[href="${currentPage}"]`);
    
    if (link) {
        window.FacilityFlowSidebar.setActivePage(
            link.getAttribute('data-page')
        );
    }
}
