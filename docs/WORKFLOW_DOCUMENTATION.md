# Workflow Documentation

## Table of Contents
1. Complete System Workflows
2. Detailed Step-by-Step Processes
3. Exception Handling
4. Data Flow Diagrams

---

## 1. Complete System Workflows

### Workflow 1: User Registration (Super Admin Only)

```
START
  ↓
Login as Super Admin
  ↓
Navigate to User Management
  ↓
Click "Add New User"
  ↓
Fill Registration Form:
  ├─ Full Name
  ├─ Email
  ├─ Password (temporary or auto-generated)
  ├─ Role Selection (department_admin or reporter)
  └─ Department (if department_admin)
  ↓
Validate Input:
  ├─ Full Name: Non-empty, 2-100 characters
  ├─ Email: Valid format, not already registered
  ├─ Password: Strong (min 8 chars, mixed case, numbers)
  ├─ Role: Valid enum value
  └─ Department: Required if role = department_admin
  ↓
  Hash password using bcrypt (cost: 12)
  ↓
INSERT INTO users table:
  ├─ full_name
  ├─ email
  ├─ password (hashed)
  ├─ role
  ├─ department_id (FK)
  ├─ status = 'active'
  └─ created_at = NOW()
  ↓
Log Activity: 'CREATE_USER', user_id, email
  ↓
Return Success:
  ├─ User ID
  ├─ Display message: "User created successfully"
  └─ Provide default password (if auto-generated)
  ↓
OPTIONAL: Send welcome email with login credentials
  ↓
END
```

### Workflow 2: User Login (All Users)

```
START
  ↓
Access /index.php (login page)
  ↓
Enter Credentials:
  ├─ Email
  └─ Password
  ↓
Click "Login"
  ↓
CALL AuthController.login(email, password)
  ↓
Validate Input:
  ├─ Email not empty? → Continue
  └─ Password not empty? → Continue
  ↓
Sanitize & validate email format
  ↓
Query database:
  SELECT user_id, full_name, email, password, role, 
         department_id, status 
  FROM users 
  WHERE email = ?
  ↓
User exists?
  ├─ No → Log failed login attempt, return "Invalid credentials"
  └─ Yes → Continue
  ↓
Check status = 'active'?
  ├─ No → Return "Account is inactive or suspended"
  └─ Yes → Continue
  ↓
Verify password using password_verify()
  ├─ Match? → Continue
  └─ No match → Log failed attempt, return "Invalid credentials"
  ↓
CREATE PHP SESSION:
  ├─ session_start()
  ├─ $_SESSION['user_id'] = user_id
  ├─ $_SESSION['email'] = email
  ├─ $_SESSION['full_name'] = full_name
  ├─ $_SESSION['role'] = role
  ├─ $_SESSION['department_id'] = department_id
  ├─ $_SESSION['last_activity'] = time()
  ├─ $_SESSION['session_token'] = random_bytes(32) [CSRF]
  └─ session_regenerate_id(true) [Prevent fixation]
  ↓
Set Secure Session Cookie:
  ├─ httponly = true [Prevent JS access]
  ├─ secure = true [HTTPS only]
  └─ samesite = 'Strict' [CSRF protection]
  ↓
Log Activity: 'LOGIN', user_id
  ↓
Redirect based on role:
  ├─ super_admin → /pages/super-admin-dashboard.php
  ├─ department_admin → /pages/dept-admin-dashboard.php
  └─ reporter → /pages/reporter-dashboard.php
  ↓
END
```

### Workflow 3: Submit Maintenance Report (Reporter)

```
START
  ↓
CHECK: Is user logged in?
  ├─ No → Redirect to login
  └─ Yes → Continue
  ↓
CHECK: Is user a reporter?
  ├─ No → Return 403 Forbidden
  └─ Yes → Continue
  ↓
Display Report Submission Form:
  ├─ Facility Type (dropdown)
  │  ├─ Air Conditioning
  │  ├─ Electrical
  │  ├─ Plumbing
  │  └─ Other
  ├─ Location (text input)
  ├─ Description (textarea)
  ├─ Priority (dropdown - optional)
  │  ├─ Low
  │  ├─ Medium (default)
  │  ├─ High
  │  └─ Critical
  └─ Image Upload (optional, max 5MB)
  ↓
User fills form and submits
  ↓
VALIDATE Input:
  ├─ facility_type: not empty, in valid enum
  ├─ location: not empty, 3-255 characters
  ├─ description: not empty, 10-1000 characters
  ├─ priority: valid enum value
  └─ image: valid MIME type, < 5MB (if provided)
  ↓
Validation failed?
  ├─ Yes → Display error messages, keep form data
  └─ No → Continue
  ↓
Handle Image Upload (if provided):
  ├─ Validate MIME type: image/jpeg, image/png, image/gif
  ├─ Validate file size: < 5MB
  ├─ Generate unique filename: timestamp + random + extension
  ├─ Move to secure directory: /uploads/reports/
  └─ Store filename in image_path variable
  ↓
BEGIN TRANSACTION
  ↓
INSERT INTO maintenance_reports:
  ├─ reported_by = current_user_id
  ├─ facility_type = sanitized_facility_type
  ├─ location = sanitized_location
  ├─ description = sanitized_description
  ├─ image_path = image_path (or NULL)
  ├─ priority = priority
  ├─ status = 'pending'
  ├─ assigned_admin = NULL (not yet assigned)
  ├─ created_at = NOW()
  └─ updated_at = NOW()
  ↓
Get newly created report_id
  ↓
AUTO-ASSIGN Report:
  CALL AssignmentService.assignToAdmin(report_id, facility_type)
    ├─ IF facility_type = 'aircon' THEN
    │  └─ Get department_id for 'aircon' department
    ├─ ELSE IF facility_type = 'electrical' THEN
    │  └─ Get department_id for 'electrical' department
    ├─ ELSE IF facility_type = 'plumbing' THEN
    │  └─ Get department_id for 'plumbing' department
    └─ ELSE (facility_type = 'other')
       └─ Return NULL (no specific admin)
  ↓
Query for active department admin:
  SELECT user_id FROM users 
  WHERE role = 'department_admin' 
  AND department_id = found_department_id
  AND status = 'active'
  ORDER BY created_at ASC
  LIMIT 1
  ↓
Update report with assigned_admin:
  UPDATE maintenance_reports 
  SET assigned_admin = admin_user_id 
  WHERE report_id = report_id
  ↓
CREATE NOTIFICATIONS:
  
  1) Notify Department Admin:
     INSERT INTO notifications:
     ├─ user_id = assigned_admin_id
     ├─ report_id = report_id
     ├─ title = 'New Maintenance Request Assigned'
     └─ message = 'New [facility_type] issue at [location]. Report ID: #[report_id]'
  
  2) Notify Super Admin:
     SELECT user_id FROM users WHERE role = 'super_admin' LIMIT 1
     INSERT INTO notifications:
     ├─ user_id = super_admin_id
     ├─ report_id = report_id
     ├─ title = 'New Maintenance Report'
     └─ message = 'New [facility_type] maintenance request. Report ID: #[report_id]'
  ↓
LOG ACTIVITY:
  INSERT INTO activity_logs:
  ├─ user_id = current_user_id
  ├─ action = 'CREATE_REPORT'
  ├─ report_id = report_id
  ├─ description = 'Submitted report for [facility_type] at [location]'
  └─ timestamp = NOW()
  ↓
COMMIT TRANSACTION
  ↓
Return Success Response:
  ├─ Message: "Report submitted successfully"
  ├─ report_id: [report_id]
  └─ Redirect to report detail page after 2 seconds
  ↓
END
```

### Workflow 4: Update Report Status (Department Admin)

```
START
  ↓
CHECK: Is user logged in?
  ├─ No → Redirect to login
  └─ Yes → Continue
  ↓
CHECK: Is user a department admin?
  ├─ No → Return 403 Forbidden
  └─ Yes → Continue
  ↓
Get report_id from URL parameter
  ↓
VERIFY AUTHORIZATION:
  Call ResourceMiddleware.canUpdateReport(user_id, report_id)
    ├─ Is Super Admin? → ALLOW
    ├─ Is Dept Admin AND assigned_admin = current_user_id? → ALLOW
    └─ Otherwise → DENY
  ↓
  Authorization denied?
  ├─ Yes → Return 403 Forbidden
  └─ No → Continue
  ↓
Display Report Detail:
  ├─ Location
  ├─ Description
  ├─ Priority
  ├─ Current Status
  ├─ Image (if any)
  └─ Status Update Form:
     ├─ New Status (dropdown with valid transitions)
     └─ Remarks (textarea - optional)
  ↓
Department Admin selects new status and optional remarks
  ↓
Possible Status Transitions:
  FROM pending: → ongoing, cancelled
  FROM ongoing: → fixed, pending (reopen), cancelled
  FROM fixed: (no transitions)
  FROM cancelled: (no transitions)
  ↓
VALIDATE New Status:
  ├─ Is new_status in valid_transitions[current_status]?
  ├─ If No → Display error, request valid status
  └─ If Yes → Continue
  ↓
Click "Update Status"
  ↓
BEGIN TRANSACTION
  ↓
UPDATE Report Status:
  UPDATE maintenance_reports 
  SET status = new_status,
      remarks = remarks,
      fixed_at = (IF new_status = 'fixed' THEN NOW() ELSE NULL)
  WHERE report_id = report_id
  ↓
CREATE NOTIFICATIONS based on new status:
  
  IF new_status = 'ongoing' THEN
    → Notify REPORTER
       title: 'Repair Started'
       message: 'Your maintenance request is now being worked on.'
  
  ELSE IF new_status = 'fixed' THEN
    → Notify REPORTER
       title: 'Issue Fixed'
       message: 'Your maintenance request has been completed.'
    → Notify SUPER ADMIN
       title: 'Report Completed'
       message: 'Report #[report_id] has been marked as fixed.'
  
  ELSE IF new_status = 'cancelled' THEN
    → Notify REPORTER
       title: 'Request Cancelled'
       message: 'Your maintenance request has been cancelled.'
  ↓
LOG ACTIVITY:
  INSERT INTO activity_logs:
  ├─ user_id = current_user_id (admin)
  ├─ action = 'UPDATE_REPORT_STATUS'
  ├─ report_id = report_id
  ├─ description = 'Changed status from [old_status] to [new_status]'
  └─ timestamp = NOW()
  ↓
COMMIT TRANSACTION
  ↓
Return Success Response:
  ├─ Message: "Status updated successfully"
  └─ Refresh report detail with new data
  ↓
END
```

### Workflow 5: Super Admin Views All Reports

```
START
  ↓
CHECK: Is user logged in?
  ├─ No → Redirect to login
  └─ Yes → Continue
  ↓
CHECK: Is user Super Admin?
  ├─ No → Return 403 Forbidden
  └─ Yes → Continue
  ↓
Query all reports with optional filters:
  SELECT r.*, u1.full_name as reporter_name, u2.full_name as admin_name
  FROM maintenance_reports r
  JOIN users u1 ON r.reported_by = u1.user_id
  LEFT JOIN users u2 ON r.assigned_admin = u2.user_id
  WHERE 1=1
  ↓
Apply Filters (if provided):
  ├─ status: pending, ongoing, fixed, cancelled
  ├─ facility_type: aircon, electrical, plumbing, other
  ├─ priority: low, medium, high, critical
  ├─ department: (by assigned admin's department)
  ├─ date_from: (created_at >= date)
  └─ date_to: (created_at <= date)
  ↓
Display Reports List:
  ├─ Sortable columns:
  │  ├─ Report ID
  │  ├─ Facility Type
  │  ├─ Location
  │  ├─ Priority
  │  ├─ Status
  │  ├─ Reporter
  │  ├─ Assigned Admin
  │  └─ Created Date
  ├─ Pagination: 20 reports per page
  └─ Quick Actions:
     ├─ View Detail
     ├─ Reassign
     └─ Delete
  ↓
Calculations on display:
  ├─ Total Reports count
  ├─ Reports by Status (pie chart data)
  ├─ Reports by Department (bar chart data)
  ├─ Average resolution time
  └─ Pending reports count
  ↓
Display Statistics Box:
  ├─ Total Reports (all time)
  ├─ Pending: X
  ├─ Ongoing: Y
  ├─ Fixed: Z
  └─ Completion Rate: Z/(X+Y+Z) %
  ↓
END
```

### Workflow 6: Reporter Views Own Report Status

```
START
  ↓
CHECK: Is user logged in?
  ├─ No → Redirect to login
  └─ Yes → Continue
  ↓
CHECK: Is user a Reporter?
  ├─ No → Redirect to appropriate dashboard
  └─ Yes → Continue
  ↓
Query reporter's reports:
  SELECT * FROM maintenance_reports 
  WHERE reported_by = current_user_id
  ORDER BY created_at DESC
  ↓
Display Reports List:
  ├─ For each report show:
  │  ├─ Report ID
  │  ├─ Facility Type
  │  ├─ Location
  │  ├─ Priority
  │  ├─ Current Status
  │  │  ├─ Pending (grey badge)
  │  │  ├─ Ongoing (yellow badge)
  │  │  ├─ Fixed (green badge)
  │  │  └─ Cancelled (red badge)
  │  ├─ Date Submitted
  │  └─ Action Button: "View Detail"
  ↓
Reporter clicks on a report
  ↓
GET report_id from list click
  ↓
VERIFY AUTHORIZATION:
  CHECK: Is this the reporter's own report?
    ├─ report.reported_by != current_user_id → 403 Forbidden
    └─ Matches → Continue
  ↓
Display Report Detail Page:
  ├─ Facility Type
  ├─ Location
  ├─ Description
  ├─ Priority
  ├─ Current Status (with timeline)
  ├─ Image (if uploaded)
  ├─ Date Submitted
  ├─ Assigned Admin Name (if assigned)
  ├─ Remarks (if any)
  └─ Remarks History (all updates)
  ↓
Display Timeline of Status Changes:
  ├─ Created: [date] by Reporter
  ├─ Status: pending
  ├─ ↓
  ├─ Assigned to: [admin_name]
  ├─ Status: ongoing (date) - Repair started
  ├─ ↓
  ├─ Status: fixed (date) - Repair completed
  └─ Timeline: Shows all history
  ↓
Display Notifications:
  ├─ All notifications related to this report
  ├─ Marked as read when reporter views detail
  └─ Archive old notifications
  ↓
END
```

---

## 2. Exception Handling

### Exception 1: Report Assignment Failure

```
SCENARIO: No department admin available for facility type

CREATE Report workflow at auto-assign step:
  ↓
Query for department admin fails or returns NULL
  ↓
CHECK: Is admin_id = NULL?
  ├─ Yes → Continue without assignment (optional)
  └─ No → Skip this step
  ↓
Report is created with assigned_admin = NULL
  ↓
NOTIFY Super Admin ONLY:
  "Report #[report_id] could not be auto-assigned. 
   Please manually assign to department admin."
  ↓
Super Admin must manually reassign report
  ↓
Manual assignment triggers same notification workflow
```

### Exception 2: Invalid Status Transition

```
SCENARIO: User tries to change status to invalid state

UPDATE Report Status workflow:
  ↓
VALIDATE transition:
  pending ✓→ ongoing, cancelled
  ongoing ✓→ fixed, pending, cancelled
  fixed ✗→ (no transitions allowed)
  ↓
IF invalid transition THEN
  ├─ Reject the update
  ├─ Display error message
  ├─ Show valid transitions in dropdown
  └─ Do NOT update database
  ↓
No notifications created
No activity logged
```

### Exception 3: Authorization Failure

```
SCENARIO: User tries to access/update report they shouldn't

ANY report access attempt:
  ↓
CALL ResourceMiddleware.canAccessReport()
  ↓
IF authorization check fails THEN
  ├─ Log failed access attempt:
  │  ├─ user_id
  │  ├─ report_id
  │  ├─ timestamp
  │  └─ denied_reason: "unauthorized_access_attempt"
  ├─ Return HTTP 403 Forbidden
  ├─ Display: "Access Denied: You do not have permission"
  └─ Do NOT perform requested action
  ↓
Super Admin can review failed attempts in activity logs
```

### Exception 4: Session Timeout

```
SCENARIO: User's session expires while working

User performs action (15 minutes of inactivity):
  ↓
AuthMiddleware::requireLogin() checks:
  IF (time() - last_activity) > 1800 seconds THEN  // 30 min
    ├─ session_destroy()
    ├─ Clear $_SESSION
    ├─ Set error message: "Session expired. Please login again."
    └─ Redirect to /index.php
  ↓
User sees login page with expiry message
  ↓
User must re-enter credentials
```

---

## 3. Dashboard Content Flow

### Super Admin Dashboard Content

```
┌─────────────────────────────────────────────┐
│        SUPER ADMIN DASHBOARD                │
├─────────────────────────────────────────────┤
│                                             │
│  HEADER:                                    │
│  ├─ Welcome, [Full Name]                    │
│  ├─ Unread Notifications: [count]           │
│  └─ [Notification bell icon]                │
│                                             │
│  STATISTICS BOXES:                          │
│  ├─ Total Reports: [count]                  │
│  ├─ Pending: [count]                        │
│  ├─ Ongoing: [count]                        │
│  ├─ Fixed: [count]                          │
│  └─ Completion Rate: [%]                    │
│                                             │
│  FILTER SECTION:                            │
│  ├─ Status: [dropdown]                      │
│  ├─ Department: [dropdown]                  │
│  ├─ Priority: [dropdown]                    │
│  ├─ Date Range: [from] - [to]               │
│  └─ [Search button]                         │
│                                             │
│  REPORTS TABLE:                             │
│  ├─ ID | Facility | Location | Priority    │
│  ├─ Status | Reporter | Assigned | Date    │
│  └─ [View] [Reassign] [Delete] buttons      │
│                                             │
│  PAGINATION:                                │
│  └─ [< Prev] [1 2 3 ...] [Next >]           │
│                                             │
│  RECENT ACTIVITIES:                         │
│  ├─ [User] [Action] [time]                  │
│  └─ Last 10 activities                      │
│                                             │
└─────────────────────────────────────────────┘
```

### Department Admin Dashboard Content

```
┌─────────────────────────────────────────────┐
│      DEPARTMENT ADMIN DASHBOARD             │
├─────────────────────────────────────────────┤
│                                             │
│  HEADER:                                    │
│  ├─ Welcome, [Full Name]                    │
│  ├─ Department: [Aircon/Electrical/Plumbing]
│  └─ Unread Notifications: [count]           │
│                                             │
│  STATISTICS BOXES:                          │
│  ├─ Assigned to Me: [count]                 │
│  ├─ Pending: [count]                        │
│  ├─ Ongoing: [count]                        │
│  ├─ Fixed This Month: [count]               │
│  └─ Avg Resolution Time: [X days]           │
│                                             │
│  FILTER SECTION:                            │
│  ├─ Status: [dropdown]                      │
│  ├─ Priority: [dropdown]                    │
│  └─ Date Range: [from] - [to]               │
│                                             │
│  ASSIGNED REPORTS TABLE:                    │
│  ├─ ID | Location | Priority | Status      │
│  ├─ Reporter | Date | [View] [Update]       │
│  └─ ONLY reports assigned to this admin     │
│                                             │
│  QUICK ACTION:                              │
│  ├─ Update Status [dropdown]                │
│  ├─ Add Remarks [textarea]                  │
│  └─ [Save Changes] button                   │
│                                             │
└─────────────────────────────────────────────┘
```

### Reporter Dashboard Content

```
┌─────────────────────────────────────────────┐
│       REPORTER DASHBOARD                    │
├─────────────────────────────────────────────┤
│                                             │
│  HEADER:                                    │
│  ├─ Welcome, [Full Name]                    │
│  └─ Unread Notifications: [count]           │
│                                             │
│  QUICK ACTION BUTTONS:                      │
│  ├─ [+ Submit New Report]                   │
│  └─ [View Notifications]                    │
│                                             │
│  MY REPORTS SUMMARY:                        │
│  ├─ Total Submitted: [count]                │
│  ├─ Pending: [count] (waiting assignment)   │
│  ├─ In Progress: [count] (being worked on)  │
│  └─ Completed: [count] (fixed)              │
│                                             │
│  MY REPORTS LIST:                           │
│  ├─ ID | Type | Location | Status | Date   │
│  ├─ Status badge (pending/ongoing/fixed)   │
│  └─ [View Detail] button                    │
│                                             │
│  RECENT NOTIFICATIONS:                      │
│  ├─ [Notification 1]                        │
│  ├─ [Notification 2]                        │
│  └─ [...more]                               │
│                                             │
│  HELP / INFO BOX:                           │
│  └─ How to submit a report [link]           │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 4. Notification Display Workflow

```
NOTIFICATION TRIGGER Events:
1. Report Submitted → Notify Dept Admin + Super Admin
2. Status: Pending → Ongoing → Notify Reporter
3. Status: Ongoing → Fixed → Notify Reporter + Super Admin
4. Report Reassigned → Notify New Admin
5. Report Cancelled → Notify Reporter

NOTIFICATION DISPLAY:
  User logs in
    ↓
  Query unread notifications:
    SELECT * FROM notifications 
    WHERE user_id = ? AND is_read = FALSE
    ORDER BY created_at DESC
    LIMIT 10
    ↓
  Display in two places:
  1. Notification badge (top-right corner)
     ├─ Bell icon
     ├─ Red badge with count
     └─ Dropdown showing last 5 unread
  
  2. Notifications Page (/notifications.php)
     ├─ List of all unread notifications
     ├─ Grouped by date
     ├─ [Mark as Read] button per notification
     └─ [Mark All as Read] button
    ↓
  User clicks notification
    ↓
  Two actions:
  1. Auto-mark as read: UPDATE notifications SET is_read=TRUE
  2. Navigate to related report detail
    ↓
  Notification count badge updates (decrements)
    ↓
  END
```

