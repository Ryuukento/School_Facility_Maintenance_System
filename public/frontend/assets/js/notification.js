// ===================================
// NOTIFICATION SYSTEM
// ===================================

const NotificationManager = {
    isOpen: false,
    refreshInterval: null,
    currentFilter: 'all',
    lastNotifications: [],

    getBackendApiUrl(action) {
        const base = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/backend/api/notifications.php') : '/backend/api/notifications.php';
        return `${base}?action=${encodeURIComponent(action)}`;
    },
    
    init() {
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');
        
        if (!bell) return;
        
        bell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        document.addEventListener('click', () => {
            if (this.isOpen) {
                this.closeDropdown();
            }
        });
        
        dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        
        this.loadNotifications();
        
        // Poll every 10 seconds for near-real-time bell updates
        this.refreshInterval = setInterval(() => {
            this.loadNotifications();
        }, 10000);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.loadNotifications();
            }
        });

        window.addEventListener('focus', () => {
            this.loadNotifications();
        });
        
        const clearBtn = document.getElementById('clearNotifications');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }

        const tabs = document.querySelectorAll('.notification-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.currentFilter = tab.dataset.filter || 'all';
                this.renderNotifications(this.lastNotifications);
            });
        });
    },
    
    toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        this.isOpen ? this.closeDropdown() : this.openDropdown();
    },
    
    openDropdown() {
        document.getElementById('notificationDropdown').classList.add('show');
        this.isOpen = true;
        this.loadNotifications();
    },
    
    closeDropdown() {
        document.getElementById('notificationDropdown').classList.remove('show');
        this.isOpen = false;
    },
    
    async loadNotifications() {
        try {
            let notifications = [];
            let count = 0;

            if (typeof API !== 'undefined' && typeof API.getNotifications === 'function' && typeof API.getNotificationCount === 'function') {
                const response = await API.getNotifications(10);
                notifications = (response && response.data && Array.isArray(response.data.notifications)) ? response.data.notifications : [];

                const countResult = await API.getNotificationCount();
                count = (countResult && countResult.data && typeof countResult.data.count !== 'undefined')
                    ? Number(countResult.data.count)
                    : notifications.length;
            } else {
                const listRes = await fetch(this.getBackendApiUrl('getUnread') + '&limit=10', {
                    credentials: 'include'
                });
                const listData = await listRes.json();
                notifications = (listData && listData.data && Array.isArray(listData.data.notifications)) ? listData.data.notifications : [];
                count = notifications.length;
            }
            
            const badge = document.getElementById('notificationCount');
            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
            
            this.lastNotifications = notifications;
            this.renderNotifications(notifications);
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    },
    
    renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        const filtered = this.currentFilter === 'unread'
            ? notifications.filter(n => Number(n.is_read) === 0)
            : notifications;
        
        if (filtered.length === 0) {
            list.innerHTML = '<div class="notification-empty">No notifications</div>';
            return;
        }
        
        let html = '';
        filtered.forEach(notif => {
            const isUnread = notif.is_read === 0 ? 'unread' : '';
            const icon = this.getIcon(notif.type);
            const timeAgo = this.getTimeAgo(notif.created_at);
            const notificationId = Number(notif.notification_id) || 0;
            const reportId = Number(notif.report_id) || 0;
            
            html += `
                <div class="notification-item ${isUnread}" data-notification-id="${notificationId}" data-report-id="${reportId}" onclick="NotificationManager.handleNotificationClick(${notificationId}, ${reportId}, this)">
                    <div class="notification-icon">${icon}</div>
                    <div class="notification-content">
                        <div class="notification-title">${notif.title}</div>
                        <div class="notification-message">${notif.message}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                    ${isUnread ? '<span class="notification-dot"></span>' : ''}
                </div>
            `;
        });
        
        list.innerHTML = html;
    },
    
    async handleNotificationClick(notificationId, reportId, itemEl = null) {
        const notif = this.lastNotifications.find(n => Number(n.notification_id) === Number(notificationId));
        const resolvedReportId = this.resolveNotificationReportId(reportId, notif);
        const targetUrl = this.getNotificationTargetUrl(resolvedReportId);

        // Never block navigation because of mark-as-read errors.
        try {
            if (notificationId) {
                if (notif && Number(notif.is_read) === 0) {
                    notif.is_read = 1;
                    this.updateBadgeCount(-1);
                    if (itemEl) {
                        itemEl.classList.remove('unread');
                        const dot = itemEl.querySelector('.notification-dot');
                        if (dot) dot.remove();
                    }
                }
                await this.markNotificationAsRead(notificationId);
            }
        } catch (error) {
            console.warn('Unable to mark notification as read:', error);
        }

        if (targetUrl) {
            window.location.href = targetUrl;
            return;
        }

        this.loadNotifications();
    },

    updateBadgeCount(delta) {
        const badge = document.getElementById('notificationCount');
        if (!badge) return;
        const current = badge.textContent === '9+' ? 9 : Number(badge.textContent || 0);
        const next = Math.max(0, current + delta);
        if (next > 0) {
            badge.textContent = next > 9 ? '9+' : String(next);
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    },

    async markNotificationAsRead(notificationId) {
        if (typeof API !== 'undefined' && API && typeof API.markNotificationAsRead === 'function') {
            return API.markNotificationAsRead(notificationId);
        }

        const form = new URLSearchParams();
        form.append('notification_id', String(notificationId));

        const response = await fetch(this.getBackendApiUrl('markAsRead'), {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: form.toString()
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Failed to mark notification as read');
        }

        return result;
    },

    resolveNotificationReportId(reportId, notif) {
        const directId = Number(reportId || 0);
        if (directId > 0) {
            return directId;
        }

        if (!notif) {
            return 0;
        }

        const fallbackId = Number(notif.report_id || 0);
        if (fallbackId > 0) {
            return fallbackId;
        }

        const sourceText = `${notif.title || ''} ${notif.message || ''} ${notif.report_title || ''}`;
        const reportIdMatch = sourceText.match(/(?:report\s*#|#)(\d+)/i);
        if (reportIdMatch && reportIdMatch[1]) {
            return Number(reportIdMatch[1]) || 0;
        }

        return 0;
    },

    getNotificationTargetUrl(reportId) {
        const safeReportId = Number(reportId) || 0;
        if (!safeReportId) {
            return null;
        }

        const role = (document.body && document.body.dataset && document.body.dataset.userRole)
            ? document.body.dataset.userRole
            : '';

        const normalizedRole = String(role || '').toLowerCase();
        const allReportsContextRoles = ['super_admin', 'maintenance_admin', 'admin_maintenance'];
        const maintenanceDetailRoles = ['maintenance_staff', 'eelab_staff', 'maintenance_personnel'];

        if (allReportsContextRoles.includes(normalizedRole)) {
            return `${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/frontend/pages/maintenance-report-detail.php') : '/frontend/pages/maintenance-report-detail.php'}?id=${safeReportId}&back=all_reports`;
        }

        if (maintenanceDetailRoles.includes(normalizedRole)) {
            return `${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/frontend/pages/maintenance-report-detail.php') : '/frontend/pages/maintenance-report-detail.php'}?id=${safeReportId}`;
        }

        return `${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/frontend/pages/report-detail.php') : '/frontend/pages/report-detail.php'}?id=${safeReportId}`;
    },

    
    async markAllAsRead() {
        try {
            await fetch(this.getBackendApiUrl('markAllAsRead'), {
                method: 'POST'
            });
            this.loadNotifications();
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    },
    
    getIcon(type) {
        const icons = {
            'report': '📋',
            'urgent': '⚠️',
            'completed': '✅',
            'assigned': '📌',
            'low': 'ℹ️'
        };
        return icons[type] || '🔔';
    },
    
    getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        return `${Math.floor(seconds / 86400)}d ago`;
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        NotificationManager.init();
    });
} else {
    NotificationManager.init();
}
