# Role-Based Access Control (RBAC) Implementation

## 1. Overview

The system uses a **3-tier hierarchical RBAC model** with role-based and resource-based authorization:

```
┌──────────────────────────────────────┐
│       Role Hierarchy                 │
├──────────────────────────────────────┤
│                                      │
│  Super Admin (Head Maintenance)      │  Highest privilege
│      ↓                               │
│  Department Admin (Specialized)      │  Medium privilege
│      ↓                               │
│  Reporter (Staff/Teacher)            │  Low privilege
│                                      │
└──────────────────────────────────────┘
```

---

## 2. Role Definitions & Permissions

### 2.1 Super Admin (super_admin)

**Description**: Head of maintenance department with full system control

**Database Fields**:
- `role = 'super_admin'`
- `department_id = NULL` (or 'none')
- `status = 'active'`

**Permissions**:

| Resource | Create | Read | Update | Delete |
|----------|--------|------|--------|--------|
| All Reports | N | Y | Y | Y |
| Report Status | N | Y | Y | N |
| All Users | Y | Y | Y | Y |
| Department Admins | Y | Y | Y | Y |
| Activity Logs | N | Y | N | N |
| System Settings | N | Y | Y | N |
| Notifications | N | Y | Y | Y |

**Dashboard Access**:
- View all reports (pending, ongoing, fixed)
- Filter by department, status, priority, date
- View system statistics and analytics
- View activity logs and audit trails
- Manage all users
- Override report assignments
- Access all functional areas

**Actions**:
```
- Create Department Admin users
- Deactivate/suspend users
- View all maintenance reports
- Update report status
- Reassign reports
- View activity logs
- Generate reports/statistics
- Archive completed reports
```

### 2.2 Department Admin (department_admin)

**Description**: Admin for specific department (Aircon, Electrical, Plumbing)

**Database Fields**:
- `role = 'department_admin'`
- `department_id IN (1, 2, 3)` (not NULL)
- `status = 'active'`

**Permissions**:

| Resource | Create | Read | Update | Delete |
|----------|--------|------|--------|--------|
| Assigned Reports | N | Y | Y | N |
| Own Department Reports | N | Y | Y | N |
| Other Department Reports | N | N | N | N |
| Report Status | N | Y | Y | N |
| Own Remarks | Y | Y | Y | Y |
| Department Stats | N | Y | N | N |
| Users | N | Y | N | N |

**Dashboard Access**:
- View only assigned reports
- Filter by status, priority, date
- View assigned report statistics
- Cannot see other department's reports
- Cannot access other functional areas

**Actions**:
```
- Update assigned report status
- Add remarks/notes to assigned reports
- View assigned report details
- Mark as ongoing/fixed
- View department statistics
- View own profile
- Cannot create/assign reports
- Cannot manage users
```

**Department Scope**:
```
Aircon Admin
├── Can only view/update aircon reports
├── Assigned reports where facility_type = 'aircon'
└── Cannot access electrical or plumbing reports

Electrical Admin
├── Can only view/update electrical reports
├── Assigned reports where facility_type = 'electrical'
└── Cannot access aircon or plumbing reports

Plumbing Admin
├── Can only view/update plumbing reports
├── Assigned reports where facility_type = 'plumbing'
└── Cannot access aircon or electrical reports
```

### 2.3 Reporter (reporter)

**Description**: Staff or teacher who submits maintenance requests

**Database Fields**:
- `role = 'reporter'`
- `department_id = NULL` (or 'none')
- `status = 'active'`

**Permissions**:

| Resource | Create | Read | Update | Delete |
|----------|--------|------|--------|--------|
| Own Reports | Y | Y | N | N |
| All Reports | N | N | N | N |
| Report Status | N | Y | N | N |
| Users | N | Y | N | N |
| Activity Logs | N | N | N | N |
| Notifications | N | Y | Y | N |

**Dashboard Access**:
- View own submitted reports only
- Submit new maintenance reports
- Track report status
- View notifications
- Cannot see other users' reports
- Cannot update report status
- Cannot access admin areas

**Actions**:
```
- Submit new maintenance report
- Upload image with report
- View own report status
- View own report history
- Receive notifications
- Mark notifications as read
- View own profile
- Cannot manage anything
```

---

## 3. Authorization Implementation

### 3.1 Page-Level Authorization

**Pattern**: Check role before displaying page

```php
// File: dashboard.php
<?php
require_once 'backend/middleware/AuthMiddleware.php';
require_once 'backend/middleware/RoleMiddleware.php';

// Step 1: Require login
AuthMiddleware::requireLogin();

// Step 2: Check if user has appropriate role
$user_role = $_SESSION['role'];

if ($user_role === 'super_admin') {
    // Load Super Admin dashboard
    include 'pages/super-admin-dashboard.php';
} elseif ($user_role === 'department_admin') {
    // Load Department Admin dashboard
    include 'pages/dept-admin-dashboard.php';
} elseif ($user_role === 'reporter') {
    // Load Reporter dashboard
    include 'pages/reporter-dashboard.php';
} else {
    // Unknown role - redirect to login
    header('Location: /index.php');
    exit;
}
?>
```

### 3.2 Resource-Level Authorization

**Pattern**: Check if user can access specific resource

```php
// File: report-detail.php
<?php
AuthMiddleware::requireLogin();

$report_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Check if user can view this report
if (!ResourceMiddleware::canViewReport($user_id, $report_id, $pdo)) {
    http_response_code(403);
    die('Access Denied: You cannot view this report');
}

// Safe to proceed - user has access
$report = getReportDetails($report_id);
// ... display report
?>
```

### 3.3 Action-Level Authorization

**Pattern**: Check permission before performing action

```php
// File: report-update.php
<?php
AuthMiddleware::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if user can update this report
    if (!ResourceMiddleware::canUpdateReport($user_id, $report_id, $pdo)) {
        http_response_code(403);
        die('Access Denied: You cannot update this report');
    }
    
    // Safe to proceed
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? null;
    
    $result = $reportService->updateReportStatus($report_id, $status, $user_id, $remarks);
    
    if ($result['success']) {
        // ...
    }
}
?>
```

---

## 4. Permission Matrix

### Complete Permission Matrix

```
┌─────────────────────────────────────────────────────────┐
│             PERMISSION MATRIX                           │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Feature               │ Super │ Dept  │ Reporter         │
│                       │ Admin │ Admin │                  │
├───────────────────────┼───────┼───────┼──────────────────┤
│ View All Reports      │  ✓    │  ✗    │  ✗               │
│ View Assigned Reports │  ✓    │  ✓    │  ✗               │
│ View Own Reports      │  ✓    │  ✓    │  ✓               │
├───────────────────────┼───────┼───────┼──────────────────┤
│ Create Reports        │  ✗    │  ✗    │  ✓               │
│ Update Report Status  │  ✓    │  ✓*   │  ✗               │
│ Delete Reports        │  ✓    │  ✗    │  ✗               │
│ Reassign Reports      │  ✓    │  ✗    │  ✗               │
├───────────────────────┼───────┼───────┼──────────────────┤
│ Add Remarks           │  ✓    │  ✓*   │  ✗               │
│ View Activity Logs    │  ✓    │  ✗    │  ✗               │
│ Manage Users          │  ✓    │  ✗    │  ✗               │
│ View Dashboard        │  ✓    │  ✓    │  ✓               │
├───────────────────────┼───────┼───────┼──────────────────┤
│ View Notifications    │  ✓    │  ✓    │  ✓               │
│ Mark Notif. Read      │  ✓    │  ✓    │  ✓               │
│ View Statistics       │  ✓    │  ✓*   │  ✗               │
└─────────────────────────────────────────────────────────┘

* Only for assigned reports in own department
```

---

## 5. Access Control Workflows

### 5.1 Login Access Flow

```
User submits credentials
    ↓
Validate email & password
    ↓
Query user from database
    ↓
Check account status = 'active'
    ├─ Inactive/Suspended → DENY
    └─ Active → Continue
    ↓
Verify password hash
    ├─ Invalid → DENY + log
    └─ Valid → Continue
    ↓
Create PHP Session
    ├─ Set user_id
    ├─ Set role
    ├─ Set department_id (if applicable)
    └─ Set session token
    ↓
Redirect to role-appropriate dashboard
    ├─ super_admin → /pages/super-admin-dashboard.php
    ├─ department_admin → /pages/dept-admin-dashboard.php
    └─ reporter → /pages/reporter-dashboard.php
```

### 5.2 Page Access Flow

```
User requests page
    ↓
Execute AuthMiddleware::requireLogin()
    ├─ Session exists? → Continue
    └─ No session → Redirect to /index.php
    ↓
Check session timeout
    ├─ Expired? → Destroy session, redirect to login
    └─ Valid → Continue
    ↓
Execute RoleMiddleware (if specific role required)
    ├─ User role matches? → Continue
    └─ No match → Return 403 Forbidden
    ↓
Display page with role-based content
```

### 5.3 Report Access Flow

```
User requests report detail
    ↓
Extract report_id from URL
    ↓
AuthMiddleware::requireLogin()
    ↓
Call ResourceMiddleware::canViewReport(user_id, report_id, pdo)
    ├─ Super Admin? → ALLOW
    ├─ Dept Admin & assigned? → ALLOW
    ├─ Reporter & own report? → ALLOW
    └─ Otherwise → DENY (403)
    ↓
    IF ALLOW: Display report
    IF DENY: Display error page
```

### 5.4 Report Update Flow

```
User submits report status update
    ↓
AuthMiddleware::requireLogin()
    ↓
Check user_role != 'reporter'
    ├─ Is reporter? → DENY
    └─ Not reporter → Continue
    ↓
Call ResourceMiddleware::canUpdateReport(user_id, report_id, pdo)
    ├─ Super Admin? → ALLOW
    ├─ Dept Admin & assigned? → ALLOW
    └─ Otherwise → DENY (403)
    ↓
Validate new status transition
    ├─ Valid? → Continue
    └─ Invalid → Return error
    ↓
    IF ALLOW: Update status, create notifications, log activity
    IF DENY: Display error message
```

---

## 6. Security Considerations

### 6.1 Privilege Escalation Prevention

**Threat**: User tries to elevate their role/permissions

**Mitigation**:
```php
// Never trust role from URL/form
$user_role = $_SESSION['role'];  // ✓ From trusted session
$role_from_form = $_POST['role']; // ✗ Never trust user input

// Always re-verify permissions from database
$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_role = $stmt->fetch()['role'];

// Verify on each action
if ($current_role !== 'super_admin') {
    return ['success' => false, 'message' => 'Insufficient permissions'];
}
```

### 6.2 Horizontal Privilege Escalation Prevention

**Threat**: User tries to access other users' data

**Mitigation**:
```php
// Verify user can only see own reports (reporter)
$target_report = getReport($report_id);

if ($_SESSION['role'] === 'reporter') {
    if ($target_report['reported_by'] != $_SESSION['user_id']) {
        die('Access Denied');
    }
}

// Verify dept admin only sees assigned reports
if ($_SESSION['role'] === 'department_admin') {
    if ($target_report['assigned_admin'] != $_SESSION['user_id']) {
        die('Access Denied');
    }
}
```

### 6.3 Session Hijacking Prevention

**Mitigation**:
```php
// Regenerate session ID periodically
if (!isset($_SESSION['regenerated']) || time() - $_SESSION['regenerated'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = time();
}

// Use secure session cookies
session_set_cookie_params([
    'httponly' => true,    // Prevent JavaScript access
    'secure' => true,      // HTTPS only
    'samesite' => 'Strict' // CSRF protection
]);
```

---

## 7. Testing Access Control

### Test Cases

```
Test Case 1: Reporter Cannot View All Reports
├─ Login as reporter
├─ Try to access /reports/list (Super Admin page)
└─ Expected: Redirect to /index.php or 403 error

Test Case 2: Reporter Cannot Update Report
├─ Login as reporter
├─ Try to POST status update
└─ Expected: 403 Forbidden

Test Case 3: Dept Admin Can Only See Assigned
├─ Login as Aircon Admin
├─ Query database for all reports
├─ Expected: Only aircon reports returned

Test Case 4: Dept Admin Cannot Access Other Dept
├─ Login as Aircon Admin
├─ Try to view electrical report (assigned to Electrical Admin)
└─ Expected: 403 Forbidden

Test Case 5: Super Admin Can Override
├─ Login as Super Admin
├─ Access any report
├─ Update any report status
└─ Expected: All operations succeed

Test Case 6: Session Expires
├─ Login as any user
├─ Wait 30+ minutes without activity
├─ Try to access protected page
└─ Expected: Redirect to login

Test Case 7: Invalid Session Token
├─ Login and get session
├─ Manually tamper with session data
├─ Try to perform action
└─ Expected: Action denied or session invalidated
```

---

## 8. Role Transition Rules

**Can a user change roles?**

- Super Admin: Assigned at system initialization, typically 1 person
- Department Admin: Created by Super Admin, fixed role
- Reporter: Default role for new users, cannot be promoted through system UI

**Changing Roles**:
```php
// Only Super Admin can change roles (not shown in UI)
if ($_SESSION['role'] !== 'super_admin') {
    return ['success' => false, 'message' => 'Only Super Admin can change roles'];
}

// Validate new role
if (!in_array($new_role, ['super_admin', 'department_admin', 'reporter'])) {
    return ['success' => false, 'message' => 'Invalid role'];
}

// Update user role
$query = "UPDATE users SET role = ? WHERE user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$new_role, $target_user_id]);

// Log this critical action
$activityService->log($_SESSION['user_id'], 'CHANGE_USER_ROLE', null,
                     "Changed user role to: $new_role");
```

---

## 9. Default Dashboard Access

When user logs in, they are redirected based on role:

| Role | Redirect URL | Page | Content |
|------|--------------|------|---------|
| super_admin | /dashboard.php | Super Admin Dashboard | All reports, statistics, user management |
| department_admin | /dashboard.php | Dept Admin Dashboard | Assigned reports, department statistics |
| reporter | /dashboard.php | Reporter Dashboard | Own reports, submit report option |

---

## 10. Menu & Navigation Control

**Navigation structure varies by role**:

```
Super Admin Menu:
├─ Dashboard
├─ All Reports
│  ├─ Pending
│  ├─ Ongoing
│  └─ Fixed
├─ Statistics
├─ Users
├─ Activity Logs
└─ Settings

Department Admin Menu:
├─ Dashboard
├─ Assigned Reports
│  ├─ Pending
│  ├─ Ongoing
│  └─ Fixed
├─ Statistics
└─ Profile

Reporter Menu:
├─ Dashboard
├─ My Reports
├─ Submit Report
├─ Notifications
└─ Profile
```

---

## 11. Summary

The RBAC system ensures:
✓ Users can only access resources appropriate to their role  
✓ Users cannot elevate their own permissions  
✓ Department Admins are isolated to their department  
✓ Reporters are isolated to their own reports  
✓ Super Admin has complete system visibility  
✓ All permissions are enforced server-side, not in UI  
✓ Detailed audit trail of who accessed what

