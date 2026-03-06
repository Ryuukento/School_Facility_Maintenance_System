# API Specifications & Endpoints

## Backend API Overview

All endpoints use HTTP POST (for data modification) or GET (for data retrieval) with prepared statements to prevent SQL injection. Session validation is required for all endpoints except login.

---

## 1. Authentication Endpoints

### 1.1 Login
**Endpoint**: `POST /backend/api/auth-api.php?action=login`

**Request Body**:
```json
{
  "email": "user@school.edu",
  "password": "password123"
}
```

**Response Success (200)**:
```json
{
  "success": true,
  "message": "Login successful",
  "redirect": "/pages/super-admin-dashboard.php"
}
```

**Response Failure (401)**:
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

**Backend Logic**:
```php
// Verify email & password
$user = authenticate($email, $password);
IF user_exists AND password_valid THEN
  CREATE session
  LOG activity
  RETURN success + redirect URL
ELSE
  LOG failed attempt
  RETURN error
END IF
```

---

### 1.2 Logout
**Endpoint**: `POST /backend/api/auth-api.php?action=logout`

**Request Body**: (empty)

**Response (200)**:
```json
{
  "success": true,
  "message": "Logout successful"
}
```

**Backend Logic**:
```php
LOG activity 'LOGOUT'
DESTROY session
CLEAR $_SESSION
REDIRECT to /index.php
```

---

### 1.3 Register User (Super Admin Only)
**Endpoint**: `POST /backend/api/auth-api.php?action=register`

**Request Body**:
```json
{
  "full_name": "John Smith",
  "email": "john@school.edu",
  "password": "SecurePass123",
  "role": "department_admin",
  "department_id": 1
}
```

**Response Success (200)**:
```json
{
  "success": true,
  "message": "User created successfully",
  "user_id": 42
}
```

**Response Failure (403)**:
```json
{
  "success": false,
  "message": "Unauthorized - Only Super Admin can register users"
}
```

---

## 2. Report Management Endpoints

### 2.1 Create Report
**Endpoint**: `POST /backend/api/report-api.php?action=create`

**Request Body** (multipart/form-data):
```
full_name: "John Smith"
facility_type: "aircon"
location: "Room 201"
description: "AC unit not cooling"
priority: "high"
image: [FILE UPLOAD]
```

**Response Success (201)**:
```json
{
  "success": true,
  "message": "Report submitted successfully",
  "report_id": 15
}
```

**Response Failure (400)**:
```json
{
  "success": false,
  "message": "Description must be 10-1000 characters"
}
```

**Backend Logic**:
```php
BEGIN TRANSACTION
  VALIDATE input
  HANDLE image upload
  INSERT into maintenance_reports
  GET assigned_admin for facility_type
  UPDATE assigned_admin field
  INSERT notifications for admin and super_admin
  LOG activity
COMMIT TRANSACTION
```

---

### 2.2 Get Reports (Filtered)
**Endpoint**: `GET /backend/api/report-api.php?action=list&status=pending&facility_type=aircon`

**Query Parameters**:
- `status`: pending, ongoing, fixed, cancelled
- `facility_type`: aircon, electrical, plumbing, other
- `priority`: low, medium, high, critical
- `department_id`: 1-3
- `date_from`: YYYY-MM-DD
- `date_to`: YYYY-MM-DD
- `page`: 1-N (pagination)
- `limit`: 10-100 (per page)

**Response Success (200)**:
```json
{
  "success": true,
  "data": [
    {
      "report_id": 15,
      "reported_by": 5,
      "reporter_name": "Ms. Teacher",
      "facility_type": "aircon",
      "location": "Room 201",
      "description": "AC not cooling",
      "priority": "high",
      "status": "pending",
      "assigned_admin": 2,
      "admin_name": "Mr. Aircon",
      "created_at": "2026-01-28 10:30:00",
      "updated_at": "2026-01-28 10:30:00"
    }
  ],
  "total_count": 23,
  "page": 1,
  "total_pages": 3
}
```

**Backend Logic**:
```php
CHECK user role
IF reporter THEN
  QUERY only own reports
ELSE IF department_admin THEN
  QUERY only assigned reports
ELSE IF super_admin THEN
  QUERY all reports
END IF

APPLY filters to query
PAGINATE results
RETURN filtered list
```

---

### 2.3 Get Report Detail
**Endpoint**: `GET /backend/api/report-api.php?action=detail&report_id=15`

**Query Parameters**:
- `report_id`: Required

**Response Success (200)**:
```json
{
  "success": true,
  "data": {
    "report_id": 15,
    "reported_by": 5,
    "reporter_name": "Ms. Teacher",
    "facility_type": "aircon",
    "location": "Room 201",
    "description": "AC unit not cooling properly",
    "image_path": "reports/1706547000_a1b2c3.jpg",
    "priority": "high",
    "status": "pending",
    "assigned_admin": 2,
    "admin_name": "Mr. Aircon",
    "remarks": null,
    "created_at": "2026-01-28 10:30:00",
    "updated_at": "2026-01-28 10:30:00",
    "fixed_at": null,
    "timeline": [
      {
        "timestamp": "2026-01-28 10:30:00",
        "action": "REPORT_CREATED",
        "by": "Ms. Teacher",
        "status": "pending"
      }
    ]
  }
}
```

**Response Error (403)**:
```json
{
  "success": false,
  "message": "Access Denied: Cannot view this report"
}
```

---

### 2.4 Update Report Status
**Endpoint**: `POST /backend/api/report-api.php?action=update_status`

**Request Body**:
```json
{
  "report_id": 15,
  "status": "ongoing",
  "remarks": "Starting repair on the compressor"
}
```

**Response Success (200)**:
```json
{
  "success": true,
  "message": "Status updated successfully",
  "data": {
    "report_id": 15,
    "old_status": "pending",
    "new_status": "ongoing",
    "updated_at": "2026-01-28 14:45:00"
  }
}
```

**Response Error (403)**:
```json
{
  "success": false,
  "message": "Only assigned admin can update this report"
}
```

**Response Error (400)**:
```json
{
  "success": false,
  "message": "Invalid status transition from ongoing to pending"
}
```

**Backend Logic**:
```php
BEGIN TRANSACTION
  VERIFY authorization
  VALIDATE status transition
  UPDATE report status
  CREATE notifications based on status
  LOG activity
COMMIT TRANSACTION
```

---

### 2.5 Delete Report (Super Admin Only)
**Endpoint**: `DELETE /backend/api/report-api.php?action=delete&report_id=15`

**Response Success (200)**:
```json
{
  "success": true,
  "message": "Report deleted successfully"
}
```

**Response Error (403)**:
```json
{
  "success": false,
  "message": "Only Super Admin can delete reports"
}
```

**Backend Logic**:
```php
VERIFY super_admin role
DELETE from maintenance_reports WHERE report_id = ?
DELETE from notifications WHERE report_id = ?
LOG activity 'DELETE_REPORT'
```

---

## 3. Notification Endpoints

### 3.1 Get Unread Notifications
**Endpoint**: `GET /backend/api/notification-api.php?action=unread&limit=10`

**Response Success (200)**:
```json
{
  "success": true,
  "data": [
    {
      "notification_id": 42,
      "report_id": 15,
      "title": "New Maintenance Request Assigned",
      "message": "New aircon issue at Room 201",
      "created_at": "2026-01-28 10:31:00",
      "is_read": false
    }
  ],
  "unread_count": 5
}
```

**Backend Logic**:
```php
QUERY unread notifications for current_user
ORDER BY created_at DESC
LIMIT limit parameter
RETURN list with count
```

---

### 3.2 Mark Notification as Read
**Endpoint**: `POST /backend/api/notification-api.php?action=mark_read`

**Request Body**:
```json
{
  "notification_id": 42
}
```

**Response Success (200)**:
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

### 3.3 Mark All as Read
**Endpoint**: `POST /backend/api/notification-api.php?action=mark_all_read`

**Response Success (200)**:
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

---

## 4. User Management Endpoints

### 4.1 Get All Users (Super Admin Only)
**Endpoint**: `GET /backend/api/user-api.php?action=list&role=department_admin`

**Query Parameters**:
- `role`: super_admin, department_admin, reporter
- `status`: active, inactive, suspended
- `department_id`: 1-3

**Response Success (200)**:
```json
{
  "success": true,
  "data": [
    {
      "user_id": 2,
      "full_name": "Mr. Aircon",
      "email": "aircon@school.edu",
      "role": "department_admin",
      "department_id": 1,
      "department_name": "Air Conditioning",
      "status": "active",
      "created_at": "2025-11-15 08:00:00"
    }
  ],
  "total": 12
}
```

---

### 4.2 Get User Detail
**Endpoint**: `GET /backend/api/user-api.php?action=detail&user_id=2`

**Response Success (200)**:
```json
{
  "success": true,
  "data": {
    "user_id": 2,
    "full_name": "Mr. Aircon",
    "email": "aircon@school.edu",
    "role": "department_admin",
    "department_id": 1,
    "status": "active",
    "assigned_reports": 5,
    "fixed_reports_this_month": 8,
    "created_at": "2025-11-15 08:00:00"
  }
}
```

---

### 4.3 Update User (Super Admin Only)
**Endpoint**: `POST /backend/api/user-api.php?action=update`

**Request Body**:
```json
{
  "user_id": 2,
  "status": "inactive",
  "role": "reporter"
}
```

**Response Success (200)**:
```json
{
  "success": true,
  "message": "User updated successfully"
}
```

---

## 5. Dashboard Statistics Endpoints

### 5.1 Get Super Admin Statistics
**Endpoint**: `GET /backend/api/dashboard-api.php?action=super_admin_stats`

**Response Success (200)**:
```json
{
  "success": true,
  "data": {
    "total_reports": 47,
    "pending_reports": 8,
    "ongoing_reports": 12,
    "fixed_reports": 27,
    "completion_rate": "57.4%",
    "avg_resolution_days": 3.2,
    "reports_by_department": {
      "aircon": 15,
      "electrical": 18,
      "plumbing": 14
    },
    "reports_by_priority": {
      "critical": 2,
      "high": 10,
      "medium": 25,
      "low": 10
    }
  }
}
```

---

### 5.2 Get Department Statistics
**Endpoint**: `GET /backend/api/dashboard-api.php?action=dept_stats`

**Response Success (200)**:
```json
{
  "success": true,
  "data": {
    "assigned_reports": 15,
    "pending": 5,
    "ongoing": 7,
    "fixed_this_month": 8,
    "avg_resolution_days": 2.4
  }
}
```

---

## 6. Activity Log Endpoints

### 6.1 Get Activity Logs
**Endpoint**: `GET /backend/api/activity-api.php?action=list&days=7`

**Query Parameters**:
- `days`: 1-365 (filter by last N days)
- `user_id`: Filter by specific user
- `action`: Filter by action type
- `report_id`: Filter by report

**Response Success (200)**:
```json
{
  "success": true,
  "data": [
    {
      "log_id": 1,
      "timestamp": "2026-01-28 10:30:00",
      "user_id": 5,
      "user_name": "Ms. Teacher",
      "action": "CREATE_REPORT",
      "report_id": 15,
      "description": "Submitted report for aircon at Room 201"
    }
  ],
  "total": 142
}
```

---

## 7. File Upload Endpoints

### 7.1 Upload Report Image
**Endpoint**: `POST /backend/api/upload-api.php?action=upload_report_image`

**Request Body** (multipart/form-data):
```
image: [FILE - max 5MB]
report_id: 15
```

**Response Success (201)**:
```json
{
  "success": true,
  "message": "Image uploaded successfully",
  "image_path": "reports/1706547000_a1b2c3.jpg"
}
```

**Response Failure (400)**:
```json
{
  "success": false,
  "message": "File size exceeds 5MB limit"
}
```

---

## 8. Error Response Format

**All endpoints return standardized error responses:**

**HTTP 400 - Bad Request**:
```json
{
  "success": false,
  "message": "Descriptive error message",
  "field": "field_name" // Optional: which field has error
}
```

**HTTP 401 - Unauthorized (Not Logged In)**:
```json
{
  "success": false,
  "message": "Please log in to continue"
}
```

**HTTP 403 - Forbidden (Permission Denied)**:
```json
{
  "success": false,
  "message": "You do not have permission for this action"
}
```

**HTTP 404 - Not Found**:
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**HTTP 500 - Server Error**:
```json
{
  "success": false,
  "message": "An error occurred while processing your request"
}
```

---

## 9. Authentication & Security Headers

**All API requests should include:**
- Session Cookie (automatically sent by browser)
- CSRF Token (in X-CSRF-Token header for POST requests)

**Example JavaScript**:
```javascript
const token = document.querySelector('meta[name="csrf-token"]').content;

fetch('/backend/api/report-api.php?action=create', {
  method: 'POST',
  headers: {
    'X-CSRF-Token': token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({...})
});
```

---

## 10. Rate Limiting (Optional Enhancement)

Future implementation can add rate limiting:
- 100 requests per minute per user
- 50 report submissions per hour
- 5 login attempts per 15 minutes

---

## 11. API Usage Examples

### Example 1: Submit Report from JavaScript
```javascript
async function submitReport(formData) {
  const response = await fetch('/backend/api/report-api.php?action=create', {
    method: 'POST',
    headers: {
      'X-CSRF-Token': document.querySelector('[name="csrf_token"]').value
    },
    body: formData // FormData with file upload
  });
  
  const result = await response.json();
  
  if (result.success) {
    console.log('Report created:', result.report_id);
    window.location = '/report-detail.php?id=' + result.report_id;
  } else {
    console.error('Error:', result.message);
  }
}
```

### Example 2: Get Notifications with Auto-Refresh
```javascript
function loadNotifications() {
  fetch('/backend/api/notification-api.php?action=unread&limit=5')
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        updateNotificationBadge(data.unread_count);
        displayNotifications(data.data);
      }
    });
}

// Refresh every 30 seconds
setInterval(loadNotifications, 30000);
```

