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
            const response = await API.getNotifications(10);
            const notifications = response.data.notifications;
            
            const countResult = await API.getNotificationCount();
            const count = countResult.data.count;
            
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
            
            html += `
                <div class="notification-item ${isUnread}" onclick="NotificationManager.handleNotificationClick(${notif.notification_id}, ${notif.report_id})">
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
        try {
            await API.markNotificationAsRead(notificationId);
            
            if (reportId) {
                window.location.href = `/School_Facility_Maintenance_System/frontend/pages/report-detail.php?id=${reportId}`;
            }
            
            this.loadNotifications();
        } catch (error) {
            console.error('Error handling notification:', error);
        }
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