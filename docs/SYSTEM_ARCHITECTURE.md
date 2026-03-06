# System Architecture Documentation

## 1. Architecture Overview

The School Facility Maintenance Reporting System follows a **3-Tier Architecture Pattern**:

```
┌─────────────────────────────────────────────────────┐
│             PRESENTATION LAYER                      │
│  (User Interface - HTML/CSS/JavaScript/Bootstrap)   │
│                                                      │
│  Components:                                        │
│  ├── Login Page (index.php)                         │
│  ├── Dashboard (dashboard.php)                      │
│  ├── Report Submission Form                         │
│  ├── Report List & Filter                           │
│  ├── Report Detail & Comments                       │
│  ├── User Management (Super Admin)                  │
│  └── Notification Center                           │
└────────────────────┬────────────────────────────────┘
                     │ HTTP/AJAX Requests
                     │
┌────────────────────▼────────────────────────────────┐
│          BUSINESS LOGIC LAYER (PHP)                 │
│                                                      │
│  Controllers:                                       │
│  ├── AuthController (Login/Logout)                  │
│  ├── ReportController (CRUD)                        │
│  ├── NotificationController                         │
│  ├── UserController                                 │
│  └── DashboardController                            │
│                                                      │
│  Services:                                          │
│  ├── ReportService (Business Logic)                 │
│  ├── NotificationService (Create/Notify)            │
│  ├── AssignmentService (Auto-assign)                │
│  ├── ValidationService (Input Validation)           │
│  └── AuthenticationService                          │
│                                                      │
│  Middleware:                                        │
│  ├── AuthMiddleware (Session Check)                 │
│  ├── RoleMiddleware (RBAC Check)                    │
│  └── ValidationMiddleware                           │
└────────────────────┬────────────────────────────────┘
                     │ SQL Queries
                     │
┌────────────────────▼────────────────────────────────┐
│          DATA LAYER (MySQL Database)                │
│                                                      │
│  Models:                                            │
│  ├── User (PDO Model)                               │
│  ├── MaintenanceReport (PDO Model)                  │
│  ├── Notification (PDO Model)                       │
│  ├── ActivityLog (PDO Model)                        │
│  └── Department (PDO Model)                         │
│                                                      │
│  Tables:                                            │
│  ├── users                                          │
│  ├── maintenance_reports                            │
│  ├── notifications                                  │
│  ├── activity_logs                                  │
│  └── departments                                    │
└─────────────────────────────────────────────────────┘
```

---

## 2. Component Details

### 2.1 Presentation Layer

**Technology**: HTML5, CSS3, JavaScript (Vanilla or jQuery)

**Pages**:
- `index.php` - Public login page
- `dashboard.php` - Role-specific dashboard
- `report-submit.php` - Report submission form (Reporter)
- `report-list.php` - Reports list with filters
- `report-detail.php` - Report detail view with comments
- `notifications.php` - Notification center
- `user-manage.php` - User management (Super Admin)
- `profile.php` - User profile settings

**Key Components**:
- Responsive navigation bar with user menu
- Notification badge (unread count)
- Sidebar for role-based menu
- Modal dialogs for confirmations
- Form validation feedback
- Data tables with pagination

### 2.2 Business Logic Layer (PHP)

#### Controllers

**AuthController**
```
Functions:
- login(username, password) → Authenticate & create session
- logout() → Destroy session
- register() → Create new user (Super Admin only)
```

**ReportController**
```
Functions:
- create(data) → Validate & insert new report
- update(report_id, data) → Update report status/details
- list() → Get reports based on user role
- getDetail(report_id) → Fetch single report with comments
- delete(report_id) → Delete report (Super Admin)
- search(filters) → Filter by status/department/date
```

**NotificationController**
```
Functions:
- getUnread(user_id) → Fetch unread notifications
- markAsRead(notification_id) → Update read status
- delete(notification_id) → Remove notification
- getHistory(user_id, limit) → Get notification history
```

**DashboardController**
```
Functions:
- getSuperAdminDashboard() → All reports, statistics
- getDepartmentDashboard() → Assigned reports
- getReporterDashboard() → User's submitted reports
```

#### Services

**ReportService** - Core business logic
```
Functions:
- validateReport(data) → Input validation
- createReport(data) → Process new report
- updateStatus(report_id, status) → Update with notifications
- assignToAdmin(report_id, facility_type) → Auto-assign
- getFilteredReports(filters) → Query with filters
- calculateStatistics() → Generate dashboard stats
```

**NotificationService** - Notification engine
```
Functions:
- createNotification(user_id, report_id, title, msg) → Insert notification
- notifyDepartmentAdmin(report_id) → Send to admin
- notifySuperAdmin(report_id) → Send to head
- notifyReporter(report_id) → Notify original submitter
- markAllAsRead(user_id) → Batch mark as read
```

**AssignmentService** - Auto-assignment logic
```
Functions:
- assignByFacilityType(facility_type) → Get assigned admin
- getAdminForDepartment(department) → Find available admin
- redistributeLoad() → Balance assignments (optional)

Logic:
IF facility_type = "aircon" THEN assign to Aircon Admin
IF facility_type = "electrical" THEN assign to Electrical Admin
IF facility_type = "plumbing" THEN assign to Plumbing Admin
```

**ValidationService** - Input validation
```
Functions:
- validateEmail(email) → Email format check
- validatePassword(password) → Strong password check
- sanitizeInput(input) → Remove XSS attempts
- validateFileUpload(file) → Image validation
- validateRequired(field) → Non-empty check
```

**AuthenticationService** - Session & auth
```
Functions:
- authenticateUser(email, password) → Verify credentials
- createSession(user) → Set PHP session
- verifySession() → Check session validity
- checkExpiry() → Validate session timeout
- generateSessionToken() → Create CSRF token
```

#### Middleware

**AuthMiddleware** - Protect pages
```
Logic:
IF session not exists OR session expired THEN
    redirect to login
ELSE
    continue
```

**RoleMiddleware** - Role-based access
```
Logic:
IF user_role not in allowed_roles THEN
    return 403 Forbidden
ELSE
    continue
```

**ValidationMiddleware** - Input validation
```
Logic:
FOR each form field DO
    validate against rules
    IF validation fails THEN
        return error response
    END IF
END FOR
continue
```

### 2.3 Data Layer (MySQL)

**PDO Database Abstraction**:
- Prepared statements for all queries
- Connection pooling (optional)
- Query logging for debugging

**Models**:
```
User Model
- Methods: findById(), findByEmail(), create(), update(), delete()
- Properties: user_id, full_name, email, role, department

Report Model
- Methods: create(), update(), findById(), findAll(), findByStatus()
- Properties: report_id, reported_by, status, assigned_admin, facility_type

Notification Model
- Methods: create(), findUnread(), markAsRead(), delete()
- Properties: notification_id, user_id, report_id, is_read

ActivityLog Model
- Methods: log(), findByUser(), findByReport()
- Properties: log_id, user_id, action, timestamp
```

---

## 3. Data Flow Diagrams

### 3.1 Report Submission Flow

```
┌─────────────────┐
│  Reporter Form  │
└────────┬────────┘
         │ Submit
         ▼
┌────────────────────┐
│ Validate Input     │
│ (ReportService)    │
└────────┬───────────┘
         │
      ┌──▼───┐
      │Valid?│
      └──┬───┘
         │
    NO ◄─┴─► YES
    │       │
    ▼       ▼
 Error  ┌──────────────────┐
 Msg    │ Create Report    │
        │ Insert to DB     │
        └────────┬─────────┘
                 │
        ┌────────▼────────┐
        │ Auto-assign to  │
        │ Department Admin│
        │(AssignmentSvc)  │
        └────────┬────────┘
                 │
        ┌────────▼──────────────┐
        │ Create Notifications: │
        │ • Dept Admin          │
        │ • Super Admin         │
        │ (NotificationService) │
        └────────┬──────────────┘
                 │
        ┌────────▼────────┐
        │ Log Activity    │
        │ (ActivityLog)   │
        └────────┬────────┘
                 │
        ┌────────▼────────┐
        │ Return Success  │
        │ Response & ID   │
        └─────────────────┘
```

### 3.2 Status Update Flow

```
┌──────────────────┐
│ Admin Updates    │
│ Report Status    │
└────────┬─────────┘
         │
         ▼
┌──────────────────────────┐
│ Check Authorization:     │
│ • User is logged in?     │
│ • Is assigned admin?     │
│ • Valid role?            │
└────────┬─────────────────┘
         │
      ┌──▼────┐
      │Auth OK?│
      └──┬────┘
         │
    NO ◄─┴─► YES
    │       │
    ▼       ▼
 403 Err ┌──────────────┐
         │ Update Status│
         │ in Database  │
         └────────┬─────┘
                  │
         ┌────────▼──────────┐
         │ Validate New      │
         │ Status Transition │
         └────────┬──────────┘
                  │
         ┌────────▼────────────────┐
         │ Create Notifications:   │
         │ • Super Admin           │
         │ • (If "fixed") Reporter │
         └────────┬─────────────────┘
                  │
         ┌────────▼────────┐
         │ Log Activity    │
         │ (status update) │
         └────────┬────────┘
                  │
         ┌────────▼────────┐
         │ Return Success  │
         └─────────────────┘
```

---

## 4. Database Relationships

```
users (PK: user_id)
├── 1 ──→ N maintenance_reports (FK: reported_by)
├── 1 ──→ N maintenance_reports (FK: assigned_admin)
├── 1 ──→ N notifications (FK: user_id)
├── 1 ──→ N activity_logs (FK: user_id)
└── M ──→ N departments (FK: department)

maintenance_reports (PK: report_id)
├── N ──→ 1 users (FK: reported_by)
├── N ──→ 1 users (FK: assigned_admin)
├── 1 ──→ N notifications (FK: report_id)
└── 1 ──→ N activity_logs (FK: report_id)

notifications (PK: notification_id)
├── N ──→ 1 users (FK: user_id)
└── N ──→ 1 maintenance_reports (FK: report_id)

activity_logs (PK: log_id)
├── N ──→ 1 users (FK: user_id)
└── N ──→ 1 maintenance_reports (FK: report_id)
```

---

## 5. Session Management

**Session Variables**:
```php
$_SESSION = [
    'user_id' => 123,
    'email' => 'admin@school.edu',
    'full_name' => 'John Doe',
    'role' => 'super_admin',
    'department' => 'none',
    'last_activity' => 1706547800,  // Timestamp
    'session_token' => 'abc123...'   // CSRF token
];
```

**Session Timeout**: 30 minutes of inactivity  
**Session Check**: On every page load via AuthMiddleware

---

## 6. Error Handling Strategy

**Application Errors**:
```
Level 1: User Input Errors (form validation)
Level 2: Business Logic Errors (can't assign, conflict)
Level 3: Database Errors (connection, constraint)
Level 4: System Errors (permission denied, file I/O)
```

**Error Responses**:
- User-facing: Friendly error messages
- Developer-facing: Detailed logs with stack trace
- Admin-facing: Error reports in activity logs

---

## 7. Scalability Considerations

**Current Design Supports**:
- 100-500 users
- 1,000-10,000 reports
- Horizontal scaling ready

**Optimization Points**:
- Database indexing on frequently queried columns
- Query optimization with proper JOINs
- Session storage in database (for clustering)
- Image compression on upload
- Pagination for list views

---

## 8. Technology Stack Rationale

| Component | Choice | Reason |
|-----------|--------|--------|
| Backend | PHP 7.4+ | Server-side logic, easy deployment |
| Frontend | HTML/CSS/JS | No build tools needed, direct browser execution |
| Database | MySQL 8.0+ | ACID compliance, normalized design support |
| Session | PHP Native | Built-in, no additional overhead |
| Authentication | Password Hash | Bcrypt standard, secure default |
| Authorization | RBAC | Simple, flexible, easy to extend |

---

## 9. Security Architecture

**Multi-Layer Defense**:
```
Layer 1: Input Validation (ValidationService)
         ↓
Layer 2: SQL Injection Prevention (Prepared Statements)
         ↓
Layer 3: Authentication (AuthenticationService)
         ↓
Layer 4: Authorization (RoleMiddleware)
         ↓
Layer 5: Output Encoding (htmlspecialchars)
         ↓
Layer 6: Session Security (httpOnly, secure cookies)
```

---

## 10. Extension Points

**Future Enhancements**:
1. Email notifications (SMTP integration)
2. SMS alerts (Twilio integration)
3. Dashboard reports/graphs (Chart.js)
4. Mobile app (REST API layer)
5. Multi-language support (i18n)
6. Two-factor authentication
7. Advanced analytics

