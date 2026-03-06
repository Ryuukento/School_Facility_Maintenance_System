/**
 * UI Helper Functions
 */

class UI {
    /**
     * Show a toast notification
     */
    static toast(message, type = 'info', duration = 4000) {
        const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, duration);
    }
    
    /**
     * Create toast container if it doesn't exist
     */
    static createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }
    
    /**
     * Show/hide a modal
     */
    static toggleModal(modalId, show = true) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        if (show) {
            modal.classList.add('show');
        } else {
            modal.classList.remove('show');
        }
    }
    
    /**
     * Show alert
     */
    static alert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <div class="flex-between">
                <span>${message}</span>
                <button class="close-btn" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        const container = document.querySelector('.alert-container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
    }
    
    /**
     * Show loading state
     */
    static showLoading(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = '<div class="spinner"></div>';
        }
    }
    
    /**
     * Format date for display
     */
    static formatDate(date, format = 'short') {
        const options = {
            short: { year: 'numeric', month: 'short', day: 'numeric' },
            long: { year: 'numeric', month: 'long', day: 'numeric' },
            full: { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
        };
        
        return new Date(date).toLocaleDateString('en-US', options[format]);
    }
    
    /**
     * Get priority badge HTML
     */
    static getPriorityBadge(priority) {
        const colors = {
            'low': 'badge-info',
            'medium': 'badge-warning',
            'high': 'badge-danger',
            'urgent': 'badge-danger'
        };
        
        return `<span class="badge ${colors[priority] || 'badge-info'}">${priority.toUpperCase()}</span>`;
    }
    
    /**
     * Get status badge HTML
     */
    static getStatusBadge(status) {
        const colors = {
            'submitted': 'badge-info',
            'assigned': 'badge-warning',
            'in_progress': 'badge-warning',
            'completed': 'badge-success',
            'closed': 'badge-success',
            'draft': 'badge-info'
        };
        
        const labels = {
            'submitted': 'Submitted',
            'assigned': 'Assigned',
            'in_progress': 'In Progress',
            'completed': 'Completed',
            'closed': 'Closed',
            'draft': 'Draft'
        };
        
        return `<span class="badge ${colors[status] || 'badge-info'}">${labels[status] || status}</span>`;
    }
}

/**
 * Form validation helper
 */
class FormValidator {
    /**
     * Validate form and show errors
     */
    static validate(formId, rules) {
        const form = document.getElementById(formId);
        if (!form) return false;
        
        let isValid = true;
        const errors = {};
        
        // Clear previous errors
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        
        // Validate each field
        for (const [fieldName, fieldRules] of Object.entries(rules)) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field) continue;
            
            const value = field.value.trim();
            
            for (const rule of fieldRules.split('|')) {
                if (rule === 'required' && !value) {
                    errors[fieldName] = `${fieldName} is required`;
                    isValid = false;
                    break;
                } else if (rule === 'email' && value && !this.isValidEmail(value)) {
                    errors[fieldName] = 'Please enter a valid email';
                    isValid = false;
                    break;
                } else if (rule.startsWith('min:')) {
                    const minLength = parseInt(rule.split(':')[1]);
                    if (value && value.length < minLength) {
                        errors[fieldName] = `${fieldName} must be at least ${minLength} characters`;
                        isValid = false;
                        break;
                    }
                } else if (rule.startsWith('max:')) {
                    const maxLength = parseInt(rule.split(':')[1]);
                    if (value && value.length > maxLength) {
                        errors[fieldName] = `${fieldName} cannot exceed ${maxLength} characters`;
                        isValid = false;
                        break;
                    }
                }
            }
        }
        
        // Show errors
        for (const [fieldName, errorMsg] of Object.entries(errors)) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const errorEl = document.createElement('div');
                errorEl.className = 'form-error';
                errorEl.textContent = errorMsg;
                field.parentElement.appendChild(errorEl);
            }
        }
        
        return isValid;
    }
    
    /**
     * Validate email format
     */
    static isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
}

/**
 * Session management
 */
class Session {
    static get(key) {
        try {
            const data = localStorage.getItem(key);
            return data ? JSON.parse(data) : null;
        } catch {
            return null;
        }
    }
    
    static set(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }
    
    static remove(key) {
        localStorage.removeItem(key);
    }
    
    static clear() {
        localStorage.clear();
    }
}
