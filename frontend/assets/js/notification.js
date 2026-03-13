// ===================================
// NOTIFICATION SYSTEM
// ===================================

const NotificationManager = {
    isOpen: false,
    refreshInterval: null,
    
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
        
        const clearBtn = document.getElementById('clearNotifications');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }
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
                const listRes = await fetch('/School_Facility_Maintenance_System/backend/api/notifications.php?action=getUnread&limit=10', {
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
            
            this.renderNotifications(notifications);
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    },
    
    renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        
        if (notifications.length === 0) {
            list.innerHTML = '<div class="notification-empty">No notifications</div>';
            return;
        }
        
        let html = '';
        notifications.forEach(notif => {
            const isUnread = notif.is_read === 0 ? 'unread' : '';
            const icon = this.getIcon(notif.type);
            const timeAgo = this.getTimeAgo(notif.created_at);
            const notificationId = Number(notif.notification_id) || 0;
            const reportId = Number(notif.report_id) || 0;
            
            html += `
                <div class="notification-item ${isUnread}" onclick="NotificationManager.handleNotificationClick(${notificationId}, ${reportId})">
                    <div class="notification-icon">${icon}</div>
                    <div class="notification-content">
                        <div class="notification-title">${notif.title}</div>
                        <div class="notification-message">${notif.message}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        });
        
        list.innerHTML = html;
    },
    
    async handleNotificationClick(notificationId, reportId) {
        const targetUrl = this.getNotificationTargetUrl(reportId);

        // Never block navigation because of mark-as-read errors.
        try {
            if (notificationId) {
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

    async markNotificationAsRead(notificationId) {
        if (typeof API !== 'undefined' && API && typeof API.markNotificationAsRead === 'function') {
            return API.markNotificationAsRead(notificationId);
        }

        const form = new URLSearchParams();
        form.append('notification_id', String(notificationId));

        const response = await fetch('/School_Facility_Maintenance_System/backend/api/notifications.php?action=markAsRead', {
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

    getNotificationTargetUrl(reportId) {
        const safeReportId = Number(reportId) || 0;
        if (!safeReportId) {
            return null;
        }

        const role = (document.body && document.body.dataset && document.body.dataset.userRole)
            ? document.body.dataset.userRole
            : '';

        if (role === 'super_admin' || role === 'maintenance_admin') {
            return `/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=${safeReportId}`;
        }

        return `/School_Facility_Maintenance_System/frontend/pages/report-detail.php?id=${safeReportId}`;
    },
    
    async markAllAsRead() {
        try {
            await fetch('/School_Facility_Maintenance_System/backend/api/notifications.php?action=markAllAsRead', {
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