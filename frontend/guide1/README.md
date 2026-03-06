# Frontend Structure

## Directory Overview

```
frontend/
├── assets/
│   ├── css/
│   │   ├── styles.css       # Global styles & utilities
│   │   └── layout.css       # Navigation & layout styles
│   ├── js/
│   │   ├── api-client.js    # API communication
│   │   ├── utils.js         # UI & form utilities
│   │   └── main.js          # Page initialization
│   └── images/              # Static images
├── components/
│   └── components.php       # Reusable PHP components
├── includes/
│   ├── header.php           # Common header
│   └── footer.php           # Common footer
├── pages/
│   ├── index.php            # Login page
│   ├── dashboard.php        # Dashboard
│   ├── reports.php          # Reports list
│   ├── create-report.php    # Create report form
│   ├── report-detail.php    # Report details
│   ├── users.php            # User management
│   ├── analytics.php        # Analytics & reports
│   ├── profile.php          # User profile
│   └── settings.php         # User settings
└── README.md               # This file
```

## Pages & Features

### Public Pages
- **index.php** - Login page with email/password authentication

### Authenticated Pages
- **dashboard.php** - Overview with statistics and recent reports
- **reports.php** - List all reports with filtering and search
- **create-report.php** - Form to submit new maintenance reports
- **report-detail.php** - View full report details
- **users.php** - User management (admin only)
- **analytics.php** - System analytics (admin only)
- **profile.php** - User profile and password change

## Components

### UI Components
- Navbar - Top navigation with user menu
- Sidebar - Left navigation menu
- Cards - Reusable card containers
- Alerts & Toasts - Notification system
- Modals - Dialog windows
- Tables - Data display
- Badges - Status indicators
- Forms - Input components with validation

### JavaScript Utilities

**APIClient** - HTTP communication with backend
```javascript
api.login(email, password)
api.logout()
api.createReport(data)
api.updateReport(reportId, data)
api.getReport(reportId)
```

**UI** - UI helper functions
```javascript
UI.toast(message, type)
UI.alert(message, type)
UI.toggleModal(modalId, show)
UI.formatDate(date, format)
UI.getPriorityBadge(priority)
UI.getStatusBadge(status)
```

**FormValidator** - Form validation
```javascript
FormValidator.validate(formId, rules)
FormValidator.isValidEmail(email)
```

**Session** - Client-side session storage
```javascript
Session.get(key)
Session.set(key, value)
Session.clear()
```

## Styling

### CSS Architecture
- **variables.css** - Color and spacing variables
- **styles.css** - Component styles and utilities
- **layout.css** - Navigation and layout

### Color System
- Primary: #2563eb (Blue)
- Secondary: #10b981 (Green)
- Danger: #ef4444 (Red)
- Warning: #f59e0b (Amber)
- Success: #22c55e (Green)

## Responsive Design

- Mobile-first approach
- Breakpoint at 768px
- Flexible grid layouts
- Touch-friendly buttons and inputs

## Authentication Flow

1. User logs in via `/frontend/pages/index.php`
2. Credentials sent to `/api/auth?action=login`
3. Session created on backend
4. User info stored in localStorage
5. Redirect to appropriate dashboard
6. Auth middleware validates on subsequent requests

## Best Practices

- All forms validated before submission
- API errors handled gracefully
- Loading states shown during API calls
- Toast notifications for user feedback
- Session checking on page load
- CSRF tokens for API requests (via backend)
- Responsive layouts for all screen sizes
