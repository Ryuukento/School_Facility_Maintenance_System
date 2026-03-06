# Backend Structure

## Directory Overview

```
backend/
├── api/              # API endpoint files
│   ├── auth-api.php      # Authentication endpoints
│   ├── reports-api.php   # Report endpoints
│   └── router.php        # Main API router
├── config/           # Configuration files
│   ├── database.php      # Database connection
│   └── settings.php      # Application settings
├── controllers/      # API controllers
│   ├── AuthController.php        # Auth logic
│   └── ReportController.php      # Report logic
├── middleware/       # Request middleware
│   ├── SessionMiddleware.php     # Session management
│   ├── AuthMiddleware.php        # Authentication checks
│   └── RoleMiddleware.php        # Role-based access control
├── models/          # Data models
│   ├── User.php              # User model
│   ├── MaintenanceReport.php # Report model
│   └── ActivityLog.php       # Activity logging
├── services/        # Business logic services
│   ├── AuthenticationService.php  # Auth logic
│   └── ReportService.php         # Report business logic
├── utils/          # Utility classes
│   ├── Logger.php       # Application logging
│   ├── Response.php     # Standardized responses
│   └── Validator.php    # Input validation
└── bootstrap.php   # Application initialization

```

## Setup Instructions

1. **Database Setup**
   - Import `database/DATABASE_SCHEMA.sql` into MySQL
   - Update database credentials in `backend/config/database.php`

2. **Configuration**
   - Review `backend/config/settings.php` for environment settings
   - Set appropriate environment variables if needed

3. **Testing the API**
   - Login: `POST /api/auth?action=login`
   - Create Report: `POST /api/reports?action=create`
   - Update Report: `POST /api/reports?action=update&report_id=1`
   - Assign Report: `POST /api/reports?action=assign&report_id=1`

## Architecture

The backend uses a 3-layer architecture:

- **API Layer**: Entry points (`/api/`) that handle HTTP requests
- **Controller Layer**: Request handling and validation
- **Service Layer**: Business logic implementation
- **Model Layer**: Database operations using prepared statements

## Security Features

- Session-based authentication with timeout
- Role-based access control (RBAC)
- CSRF token protection
- Input validation and sanitization
- Prepared statements to prevent SQL injection
- Password hashing with bcrypt
- Activity logging for audit trails
