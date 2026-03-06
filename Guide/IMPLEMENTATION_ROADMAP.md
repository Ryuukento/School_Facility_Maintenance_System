# Complete Project Index & Implementation Roadmap

## Project Summary

**System Name**: School Facility Maintenance Reporting System  
**Type**: Capstone 1 Web Application  
**Technologies**: PHP, MySQL, HTML/CSS/JavaScript  
**Status**: Technical Blueprint Complete  
**Last Updated**: January 30, 2026  

---

## Documentation Structure

### 📋 Core Documentation

| Document | Purpose | Audience | Key Content |
|----------|---------|----------|-------------|
| [README.md](../README.md) | Project overview | Everyone | Features, architecture summary, evaluation criteria |
| [SYSTEM_ARCHITECTURE.md](./SYSTEM_ARCHITECTURE.md) | System design | Developers | 3-tier architecture, components, data flow diagrams |
| [DATABASE_SCHEMA.sql](../database/DATABASE_SCHEMA.sql) | Database design | Database Admin | Tables, relationships, indexes, stored procedures |
| [BACKEND_LOGIC.md](./BACKEND_LOGIC.md) | Business logic | PHP Developers | Controllers, services, authentication, workflows |
| [ACCESS_CONTROL.md](./ACCESS_CONTROL.md) | RBAC system | Developers, QA | Roles, permissions, authorization flows, testing |
| [WORKFLOW_DOCUMENTATION.md](./WORKFLOW_DOCUMENTATION.md) | Process flows | Everyone | User workflows, dashboards, exception handling |
| [API_SPECIFICATIONS.md](./API_SPECIFICATIONS.md) | API endpoints | Frontend/Backend Dev | All endpoints, requests/responses, usage examples |
| [SECURITY_IMPLEMENTATION.md](./SECURITY_IMPLEMENTATION.md) | Security measures | Developers, DevOps | Authentication, encryption, injection prevention |
| [SETUP_GUIDE.md](./SETUP_GUIDE.md) | Installation | DevOps, Deployment | Requirements, installation steps, configuration |

---

## Implementation Roadmap

### Phase 1: Foundation (Week 1-2)
**Objective**: Set up infrastructure and core database

- [ ] Create project directory structure
- [ ] Configure web server (Apache/Nginx)
- [ ] Set up MySQL database
  - [ ] Create database: `school_maintenance_system`
  - [ ] Run DATABASE_SCHEMA.sql
  - [ ] Create database user with proper permissions
- [ ] Create PHP configuration files
  - [ ] config/database.php
  - [ ] config/settings.php
  - [ ] config/.env
- [ ] Set up version control (Git)
- [ ] Create .gitignore for sensitive files

**Deliverables**:
✓ Working database with all tables  
✓ PHP can connect to database  
✓ Directory structure ready  

---

### Phase 2: Authentication & Session Management (Week 2-3)
**Objective**: Implement secure user authentication

#### Backend Implementation
- [ ] Create AuthController class
  - [ ] Implement login() method
  - [ ] Implement logout() method
  - [ ] Implement register() method (Super Admin only)
  
- [ ] Create AuthMiddleware class
  - [ ] Implement requireLogin() session check
  - [ ] Implement requireRole() permission check
  - [ ] Session timeout logic (30 minutes)
  - [ ] Session regeneration (every 5 minutes)
  
- [ ] Create AuthenticationService class
  - [ ] Password validation
  - [ ] Password hashing (bcrypt, cost 12)
  - [ ] Session creation/destruction

#### Frontend Implementation
- [ ] Login page (index.php)
  - [ ] Email input field
  - [ ] Password input field
  - [ ] Remember me checkbox (optional)
  - [ ] Forgot password link (optional)
  - [ ] Client-side validation (HTML5)

#### Testing
- [ ] Login with correct credentials
- [ ] Login with incorrect credentials
- [ ] Session creation verification
- [ ] Session timeout after 30 minutes
- [ ] Session regeneration every 5 minutes

**Deliverables**:
✓ Secure login system  
✓ Session management  
✓ Password hashing implemented  

---

### Phase 3: Role-Based Access Control (Week 3)
**Objective**: Implement RBAC with three roles

#### Backend Implementation
- [ ] Create RoleMiddleware class
  - [ ] can() method for permission checks
  - [ ] isSuperAdmin(), isDepartmentAdmin(), isReporter()
  - [ ] Permission matrix definition
  
- [ ] Create ResourceMiddleware class
  - [ ] canViewReport() method
  - [ ] canUpdateReport() method
  - [ ] Report ownership/assignment checks

#### Database Setup
- [ ] Insert sample users from schema:
  - [ ] 1 Super Admin
  - [ ] 3 Department Admins (Aircon, Electrical, Plumbing)
  - [ ] 2 Sample Reporters

#### Testing
- [ ] Super Admin can access all pages
- [ ] Dept Admin cannot access other depts
- [ ] Reporter cannot access admin pages
- [ ] Resource-level checks work correctly

**Deliverables**:
✓ RBAC system implemented  
✓ Role-based menu/navigation  
✓ Resource-level authorization  

---

### Phase 4: Report Management - Create (Week 4)
**Objective**: Implement report submission workflow

#### Backend Implementation
- [ ] Create ReportController class
  - [ ] create() method for new reports
  
- [ ] Create ReportService class
  - [ ] validateReportInput() validation
  - [ ] createReport() with transaction
  - [ ] Auto-assignment logic
  - [ ] Image upload handling

- [ ] Create AssignmentService class
  - [ ] assignToAdmin() by facility type
  - [ ] Auto-mapping: aircon → Aircon Admin, etc.

#### Frontend Implementation
- [ ] Report submission page (report-submit.php)
  - [ ] Facility Type dropdown (aircon, electrical, plumbing, other)
  - [ ] Location text input
  - [ ] Description textarea
  - [ ] Priority dropdown
  - [ ] Image upload (optional, max 5MB)
  - [ ] Submit button

#### Workflows
- [ ] Complete submit report workflow (see WORKFLOW_DOCUMENTATION.md)
  - [ ] Validate input
  - [ ] Upload image
  - [ ] Create report in database
  - [ ] Auto-assign to department admin
  - [ ] Create notifications
  - [ ] Log activity

#### Testing
- [ ] Submit valid report
- [ ] Submit with missing required fields
- [ ] Upload image with valid format
- [ ] Image upload with invalid format (reject)
- [ ] Image too large (> 5MB reject)
- [ ] Auto-assignment to correct admin

**Deliverables**:
✓ Report submission working  
✓ Auto-assignment logic  
✓ Image upload secure  

---

### Phase 5: Notification System (Week 5)
**Objective**: Implement notification engine

#### Backend Implementation
- [ ] Create NotificationService class
  - [ ] createNotification() insert
  - [ ] notifyDepartmentAdmin() 
  - [ ] notifySuperAdmin()
  - [ ] getUnreadNotifications()
  - [ ] markAsRead()
  - [ ] getUnreadCount()

- [ ] Update existing services to create notifications
  - [ ] ReportService: notify on report creation
  - [ ] ReportService: notify on status update

#### Frontend Implementation
- [ ] Notification badge in header
  - [ ] Unread count display
  - [ ] Dropdown preview (last 5)
  
- [ ] Notifications page (notifications.php)
  - [ ] List all notifications (paginated)
  - [ ] Mark as read button
  - [ ] Mark all as read button
  - [ ] Delete notification
  - [ ] Filter by read/unread

#### Database
- [ ] Verify notifications table structure
- [ ] Add indexes for performance

#### Testing
- [ ] Notification created on report submission
- [ ] Notifications shown in badge
- [ ] Mark as read functionality
- [ ] Notification count accuracy

**Deliverables**:
✓ Notification system implemented  
✓ Real-time badge updates  
✓ Notification history tracking  

---

### Phase 6: Report Management - View & Update (Week 6)
**Objective**: Implement report viewing and status updates

#### Backend Implementation
- [ ] ReportController
  - [ ] getDetail() for single report
  - [ ] list() with filters and pagination
  - [ ] updateStatus() for status changes
  
- [ ] ReportService
  - [ ] getFilteredReports() with filters
  - [ ] isValidStatusTransition() validation
  - [ ] updateReportStatus() with notifications

#### Frontend Implementation
- [ ] Report list page (report-list.php)
  - [ ] Table with report data
  - [ ] Filter controls (status, facility_type, priority, date)
  - [ ] Pagination
  - [ ] Role-based content
  
- [ ] Report detail page (report-detail.php)
  - [ ] Full report information
  - [ ] Status timeline
  - [ ] Image display (if uploaded)
  - [ ] Status update form (for admins only)
  - [ ] Remarks/comments section
  - [ ] Activity history

#### Workflows
- [ ] Department Admin update status workflow
  - [ ] Validate authorization
  - [ ] Validate status transition
  - [ ] Update in database
  - [ ] Create notifications
  - [ ] Log activity

#### Testing
- [ ] Filter reports by status
- [ ] Filter by facility type and department
- [ ] Pagination working correctly
- [ ] Department admin cannot update others' reports
- [ ] Status transitions validated
- [ ] Notifications created on status change
- [ ] Reporter notified when status changes

**Deliverables**:
✓ Report viewing and filtering  
✓ Status update workflow  
✓ Dashboard displays correct data  

---

### Phase 7: Activity Logging & Dashboards (Week 7)
**Objective**: Implement audit trail and role-specific dashboards

#### Backend Implementation
- [ ] Create ActivityService class
  - [ ] log() method
  - [ ] getActivityLogs() with filtering
  
- [ ] Create DashboardController class
  - [ ] getSuperAdminDashboard()
  - [ ] getDepartmentDashboard()
  - [ ] getReporterDashboard()

- [ ] Add logging to all critical actions:
  - [ ] Report creation
  - [ ] Status updates
  - [ ] Login/logout
  - [ ] User creation
  - [ ] Report deletion

#### Frontend Implementation
- [ ] Dashboard pages (dashboard.php)
  - [ ] Super Admin dashboard with all reports
  - [ ] Dept Admin dashboard with assigned reports
  - [ ] Reporter dashboard with own reports
  
- [ ] Statistics section
  - [ ] Total, pending, ongoing, fixed counts
  - [ ] Charts (optional: Chart.js)
  - [ ] Completion rates
  - [ ] Average resolution time

#### Testing
- [ ] Activity logged for all actions
- [ ] Activity logs queryable and filterable
- [ ] Dashboard shows correct data per role
- [ ] Statistics calculations accurate

**Deliverables**:
✓ Complete audit trail  
✓ Role-specific dashboards  
✓ Statistical reports  

---

### Phase 8: Security Hardening (Week 8)
**Objective**: Implement all security measures

#### Validation & Input Security
- [ ] Implement all input validation functions
- [ ] HTML encoding for output (htmlspecialchars)
- [ ] CSRF token generation and validation
- [ ] SQL injection prevention (prepared statements - should be done already)

#### File Security
- [ ] MIME type validation for uploads
- [ ] File size checking
- [ ] Secure filename generation
- [ ] Storage outside webroot

#### Session Security
- [ ] Set secure cookie flags (httpOnly, secure, samesite)
- [ ] Session timeout enforcement
- [ ] Session ID regeneration
- [ ] Session fixation prevention

#### Database Security
- [ ] Verify all queries use prepared statements
- [ ] Check database user permissions (least privilege)
- [ ] Enable query logging (optional)

#### Server Security
- [ ] Configure .htaccess for Apache
- [ ] Set security headers
- [ ] Disable directory listing
- [ ] Restrict config file access
- [ ] HTTPS enforcement

#### Testing
- [ ] SQL injection attempts blocked
- [ ] XSS attempts sanitized
- [ ] CSRF token validation
- [ ] Unauthorized access denied
- [ ] File upload validation

**Deliverables**:
✓ Secure authentication  
✓ Input validation  
✓ File upload security  
✓ CSRF protection  
✓ Session security  

---

### Phase 9: Integration & Testing (Week 9)
**Objective**: Full system integration and comprehensive testing

#### Integration Testing
- [ ] Complete user workflows end-to-end
  - [ ] Login → Submit Report → Get Notification → Update Status
  - [ ] Super Admin views all reports and statistics
  - [ ] Department Admin only sees assigned
  - [ ] Reporter only sees own reports

#### Functional Testing
- [ ] All features from feature list
- [ ] All user workflows
- [ ] All API endpoints
- [ ] Database operations

#### Security Testing
- [ ] Permission checks on all pages
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] Session management

#### Performance Testing
- [ ] Load testing with 100+ reports
- [ ] Query optimization
- [ ] Index effectiveness
- [ ] Page load times

#### User Acceptance Testing (UAT)
- [ ] Test with actual users
- [ ] Gather feedback
- [ ] Document issues
- [ ] Make adjustments

**Deliverables**:
✓ All features working correctly  
✓ No critical bugs  
✓ Security verified  
✓ Performance acceptable  

---

### Phase 10: Deployment & Documentation (Week 10)
**Objective**: Prepare for production deployment

#### Production Setup
- [ ] Configure production server
- [ ] Set up SSL certificate
- [ ] Configure backups
- [ ] Set up monitoring/logging

#### Documentation
- [ ] Complete all technical documentation
- [ ] Create user manual
- [ ] Create admin guide
- [ ] API documentation for future developers

#### Training
- [ ] Train Super Admin
- [ ] Train Department Admins
- [ ] Train on reporting issues

#### Deployment
- [ ] Migration from development to production
- [ ] Initial data setup
- [ ] Verify all functionality on production
- [ ] Create backup immediately after deployment

**Deliverables**:
✓ Production system live  
✓ Complete documentation  
✓ User trained  
✓ Support ready  

---

## File Structure Overview

```
School_Facility_Maintenance_System/
│
├── README.md                          # Project overview
│
├── docs/                              # Documentation
│   ├── SYSTEM_ARCHITECTURE.md         # System design
│   ├── BACKEND_LOGIC.md               # Business logic
│   ├── ACCESS_CONTROL.md              # RBAC system
│   ├── WORKFLOW_DOCUMENTATION.md      # User workflows
│   ├── API_SPECIFICATIONS.md          # API endpoints
│   ├── SECURITY_IMPLEMENTATION.md     # Security guide
│   └── SETUP_GUIDE.md                 # Installation guide
│
├── database/                          # Database files
│   └── DATABASE_SCHEMA.sql            # MySQL schema
│
├── backend/                           # PHP backend
│   ├── config.php                     # Configuration loader
│   ├── api/                           # API endpoints
│   │   ├── auth-api.php
│   │   ├── report-api.php
│   │   ├── notification-api.php
│   │   ├── user-api.php
│   │   ├── dashboard-api.php
│   │   └── activity-api.php
│   ├── controllers/                   # Controllers
│   │   ├── AuthController.php
│   │   ├── ReportController.php
│   │   ├── NotificationController.php
│   │   ├── UserController.php
│   │   └── DashboardController.php
│   ├── models/                        # Data models
│   │   ├── User.php
│   │   ├── MaintenanceReport.php
│   │   ├── Notification.php
│   │   └── ActivityLog.php
│   ├── services/                      # Business services
│   │   ├── AuthenticationService.php
│   │   ├── ReportService.php
│   │   ├── NotificationService.php
│   │   ├── AssignmentService.php
│   │   ├── ValidationService.php
│   │   └── ActivityService.php
│   ├── middleware/                    # Middleware
│   │   ├── AuthMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   └── ValidationMiddleware.php
│   └── includes/                      # Utility functions
│       ├── helpers.php
│       ├── functions.php
│       └── constants.php
│
├── frontend/                          # Frontend files
│   ├── index.php                      # Login page
│   ├── dashboard.php                  # Role-specific dashboard
│   ├── pages/                         # Feature pages
│   │   ├── report-submit.php
│   │   ├── report-list.php
│   │   ├── report-detail.php
│   │   ├── notifications.php
│   │   ├── user-manage.php (admin)
│   │   └── profile.php
│   ├── css/                           # Stylesheets
│   │   ├── style.css
│   │   ├── dashboard.css
│   │   ├── forms.css
│   │   └── responsive.css
│   ├── js/                            # JavaScript
│   │   ├── main.js
│   │   ├── dashboard.js
│   │   ├── forms.js
│   │   ├── notifications.js
│   │   └── api-client.js
│   ├── uploads/                       # User uploaded files
│   │   └── reports/
│   └── images/                        # Static images
│
├── config/                            # Configuration
│   ├── database.php                   # DB config
│   ├── settings.php                   # App settings
│   └── .env                           # Environment vars (private)
│
├── logs/                              # Application logs
│   ├── error.log
│   ├── activity.log
│   └── access.log
│
├── .gitignore                         # Git ignore file
├── .htaccess                          # Apache config
└── LICENSE                            # License file
```

---

## Key Features Checklist

### User Authentication
- [ ] Login/logout system
- [ ] Password hashing (bcrypt)
- [ ] Session management
- [ ] Session timeout
- [ ] Password strength requirements
- [ ] Account activation/deactivation

### Role-Based Access Control
- [ ] Super Admin role
- [ ] Department Admin role (specialized)
- [ ] Reporter role
- [ ] Role-based navigation
- [ ] Resource-level authorization
- [ ] Permission matrix enforcement

### Report Management
- [ ] Create report (reporter)
- [ ] Auto-assign to department
- [ ] View reports (role-based)
- [ ] Update status (admin)
- [ ] Status transitions validation
- [ ] Image upload with report
- [ ] Report history/timeline
- [ ] Delete reports (super admin)

### Notification System
- [ ] Create notifications on events
- [ ] Unread notification badge
- [ ] Notification list/history
- [ ] Mark as read
- [ ] Notification deletion
- [ ] Multiple notification types

### Dashboard
- [ ] Super Admin dashboard (all reports)
- [ ] Dept Admin dashboard (assigned)
- [ ] Reporter dashboard (own reports)
- [ ] Statistics display
- [ ] Filters and sorting
- [ ] Pagination

### Activity Logging
- [ ] Log all actions
- [ ] Audit trail retrieval
- [ ] Filter logs (user, action, date)
- [ ] Export logs (optional)
- [ ] Failed login attempts

### Security
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] Secure file uploads
- [ ] Input validation
- [ ] Output encoding
- [ ] HTTPS enforcement
- [ ] Security headers

---

## Technology Stack Details

### PHP Frameworks & Libraries
- **No frameworks**: Vanilla PHP for clarity in capstone project
- **PDO**: For database abstraction and prepared statements
- **Built-in functions**: Arrays, strings, date/time

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Responsive design, grid/flexbox
- **Vanilla JavaScript**: No jQuery/frameworks required
- **Bootstrap** (optional): For faster UI development

### Database
- **MySQL 5.7+**: Relational database
- **Normalization**: 3NF structure
- **Indexes**: On frequently queried columns
- **Foreign Keys**: Referential integrity

### Server
- **Apache 2.4+** or **Nginx**: Web server
- **mod_rewrite**: URL rewriting
- **SSL/TLS**: HTTPS encryption

---

## Testing Plan

### Unit Testing
- [ ] Individual service methods
- [ ] Validation functions
- [ ] Helper functions
- [ ] Database operations

### Integration Testing
- [ ] Controller to service communication
- [ ] API endpoints
- [ ] Database transactions
- [ ] Multiple features together

### Security Testing
- [ ] SQL injection attempts
- [ ] XSS payload injection
- [ ] CSRF token validation
- [ ] Authentication bypass attempts
- [ ] Authorization bypass attempts

### User Acceptance Testing
- [ ] End-to-end workflows
- [ ] UI usability
- [ ] Performance under load
- [ ] Browser compatibility

### Test Automation (Optional)
- PHPUnit for unit tests
- Selenium for UI tests
- Load testing tools

---

## Success Criteria for Capstone Project

✓ **Database Design**: Normalized MySQL schema with relationships  
✓ **Backend Logic**: PHP business logic with proper architecture  
✓ **Frontend**: HTML/CSS/JavaScript user interface  
✓ **Authentication**: Session-based with password hashing  
✓ **Authorization**: Role-based access control implemented  
✓ **Data Validation**: Server-side input validation  
✓ **Error Handling**: Proper exception handling  
✓ **Documentation**: Complete technical documentation  
✓ **Security**: Best practices implemented  
✓ **Code Quality**: Clean, maintainable code  
✓ **Testing**: Comprehensive test coverage  
✓ **Deployment**: Production-ready setup  

---

## Next Steps

1. **Review Documentation**: Read SYSTEM_ARCHITECTURE.md first
2. **Setup Environment**: Follow SETUP_GUIDE.md
3. **Start Phase 1**: Create directory structure and database
4. **Follow Roadmap**: Complete phases sequentially
5. **Test Continuously**: Don't wait until end for testing
6. **Ask Questions**: Reference documentation as needed
7. **Deploy**: Use production checklist when ready

---

## Support Resources

- **SQL Queries**: See API_SPECIFICATIONS.md for query examples
- **PHP Code**: See BACKEND_LOGIC.md for implementation patterns
- **Workflows**: See WORKFLOW_DOCUMENTATION.md for user flows
- **Security**: See SECURITY_IMPLEMENTATION.md for secure coding
- **Installation**: See SETUP_GUIDE.md for server setup
- **Database**: See DATABASE_SCHEMA.sql for table structures

---

**Good luck with your Capstone project! Remember to:**
- Keep code clean and commented
- Test thoroughly before moving to next phase
- Document any custom changes
- Follow security best practices
- Backup frequently during development

