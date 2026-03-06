/**
 * API Client for SFMS
 */

const API = {
    baseURL: '/School_Facility_Maintenance_System/backend/api',
    
    /**
     * Login user
     */
    async login(email, password) {
        const response = await fetch(`${this.baseURL}/auth.php?action=login`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    },
    
    /**
     * Logout user
     */
    async logout() {
        const response = await fetch(`${this.baseURL}/auth.php?action=logout`, {
            credentials: 'include'
        });
        const data = await response.json();
        return data;
    },
    
    /**
     * Check session
     */
    async checkSession() {
        const response = await fetch(`${this.baseURL}/auth.php?action=check`, {
            credentials: 'include'
        });
        const data = await response.json();
        return data;
    },
    
    /**
     * Get all reports
     */
    async getReports() {
        const response = await fetch(`${this.baseURL}/reports.php?action=list`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    },
    
    /**
     * Get single report
     */
    async getReport(id) {
        const response = await fetch(`${this.baseURL}/reports.php?action=get&id=${id}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    },
    
    /**
     * Create new report
     */
    async createReport(reportData) {
        const response = await fetch(`${this.baseURL}/reports.php?action=create`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(reportData)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    },
    
    /**
     * Update report
     */
    async updateReport(reportData) {
        const response = await fetch(`${this.baseURL}/reports.php?action=update`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(reportData)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    },
    
    /**
     * Delete report
     */
    async deleteReport(id) {
        const response = await fetch(`${this.baseURL}/reports.php?action=delete&id=${id}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    },

    /**
     * Notifications
     */
    async getNotifications(limit = 10) {
        const response = await fetch(`${this.baseURL}/notifications.php?action=getUnread&limit=${limit}`, {
            credentials: 'include'
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to fetch notifications');
        return data;
    },

    async getNotificationCount() {
        const response = await fetch(`${this.baseURL}/notifications.php?action=count`, {
            credentials: 'include'
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to fetch notification count');
        return data;
    },

    async markNotificationAsRead(notificationId) {
        const form = new URLSearchParams();
        form.append('notification_id', notificationId);

        const response = await fetch(`${this.baseURL}/notifications.php?action=markAsRead`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: form.toString()
        });

        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to mark notification');
        return data;
    },
    
    /**
     * Get statistics
     */
    async getStats() {
        const response = await fetch(`${this.baseURL}/reports.php?action=stats`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    }
};

/**
 * UI Helper functions
 */
const UI = {
    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },
    
    /**
     * Show loading state
     */
    showLoading(element) {
        const originalContent = element.innerHTML;
        element.innerHTML = '<span class="loading">Loading...</span>';
        element.disabled = true;
        
        return () => {
            element.innerHTML = originalContent;
            element.disabled = false;
        };
    },
    
    /**
     * Format date
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    /**
     * Format datetime
     */
    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    /**
     * Get priority badge class
     */
    getPriorityBadge(priority) {
        const badges = {
            'low': 'badge-primary',
            'medium': 'badge-warning',
            'high': 'badge-danger',
            'urgent': 'badge-danger'
        };
        return badges[priority] || 'badge-primary';
    },
    
    /**
     * Get status badge class
     */
    getStatusBadge(status) {
        const badges = {
            'draft': 'badge-secondary',
            'submitted': 'badge-primary',
            'assigned': 'badge-warning',
            'in_progress': 'badge-info',
            'completed': 'badge-success',
            'closed': 'badge-secondary'
        };
        return badges[status] || 'badge-primary';
    }
};

/**
 * Session helper - using localStorage
 */
if (typeof Session === 'undefined') {
    const Session = {
        get(key) {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        },
        
        set(key, value) {
            localStorage.setItem(key, JSON.stringify(value));
        },
        
        remove(key) {
            localStorage.removeItem(key);
        },
        
        clear() {
            localStorage.clear();
        },
        
        getUser() {
            return this.get('user');
        }
    };
}
