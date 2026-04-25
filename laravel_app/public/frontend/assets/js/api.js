/**
 * API Client for SFMS - Laravel Backend
 */

const API = {
    baseURL: window.API_BASE_URL || '/api',
    csrfToken: window.CSRF_TOKEN || '',
    
    /**
     * Login user
     */
    async login(email, password) {
        const response = await fetch(`${this.baseURL}/auth/login`, {
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
        const response = await fetch(`${this.baseURL}/auth/logout`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        return data;
    },
    
    /**
     * Check session
     */
    async checkSession() {
        const response = await fetch(`${this.baseURL}/auth/check`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        return data;
    },
    
    /**
     * Get all reports
     */
    async getReports() {
        const response = await fetch(`${this.baseURL}/reports`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Unauthorized: Please log in');
        }
        
        return data;
    },
    
    /**
     * Get single report
     */
    async getReport(id) {
        const response = await fetch(`${this.baseURL}/reports/${id}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
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
        const response = await fetch(`${this.baseURL}/reports`, {
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
        const reportId = reportData.report_id || reportData.id;
        const response = await fetch(`${this.baseURL}/reports/${reportId}`, {
            method: 'PATCH',
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
        const response = await fetch(`${this.baseURL}/reports/${id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message);
        }
        
        return data;
    },

    /**
     * Notifications - stored as part of reports (pending implementation)
     */
    async getNotifications(limit = 10) {
        const response = await fetch(`${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/backend/api/notifications.php') : '/backend/api/notifications.php'}?action=getUnread&limit=` + encodeURIComponent(limit), {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to load notifications');
        }

        return data;
    },

    async getNotificationCount() {
        const response = await fetch(`${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/backend/api/notifications.php') : '/backend/api/notifications.php'}?action=count`, {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to load notification count');
        }

        return data;
    },

    async markNotificationAsRead(notificationId) {
        const form = new URLSearchParams();
        form.append('notification_id', String(notificationId));

        const response = await fetch(`${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/backend/api/notifications.php') : '/backend/api/notifications.php'}?action=markAsRead`, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: form.toString()
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to mark notification as read');
        }

        return data;
    },
    
    /**
     * Get statistics / Dashboard stats
     */
    async getStats() {
        const response = await fetch(`${this.baseURL}/dashboard/stats`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
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
