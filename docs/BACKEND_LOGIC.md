# Backend Business Logic - PHP Implementation Guide

## Table of Contents
1. Authentication Flow
2. Authorization & Access Control
3. Report Management Logic
4. Notification System
5. Activity Logging
6. Auto-Assignment Logic
7. Pseudo-code Examples
8. Service Layer Implementation

---

## 1. Authentication Flow

### 1.1 Login Process

```php
// File: backend/controllers/AuthController.php

class AuthController {
    
    /**
     * Handle user login
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'message' => string]
     */
    public function login($email, $password) {
        // Step 1: Validate input
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password required'];
        }
        
        // Step 2: Sanitize email input
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Step 3: Query user by email (prepared statement)
        $query = "SELECT user_id, full_name, email, password, role, 
                         department_id, status FROM users WHERE email = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Step 4: Verify user exists
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Step 5: Check account status
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is inactive or suspended'];
        }
        
        // Step 6: Verify password using bcrypt
        if (!password_verify($password, $user['password'])) {
            // Log failed attempt
            $this->logFailedLogin($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Step 7: Create PHP session
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['last_activity'] = time();
        $_SESSION['session_token'] = bin2hex(random_bytes(32)); // CSRF token
        
        // Step 8: Set session cookie options
        session_set_cookie_params([
            'lifetime' => 0,        // Until browser closes
            'path' => '/',
            'domain' => '',
            'secure' => true,       // HTTPS only
            'httponly' => true,     // JS cannot access
            'samesite' => 'Strict'  // CSRF protection
        ]);
        
        // Step 9: Regenerate session ID (prevent fixation)
        session_regenerate_id(true);
        
        // Step 10: Log successful login
        $this->logActivity($user['user_id'], 'LOGIN', null);
        
        return ['success' => true, 'message' => 'Login successful'];
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Log logout activity
            $this->logActivity($_SESSION['user_id'], 'LOGOUT', null);
        }
        
        // Destroy session
        $_SESSION = [];
        session_destroy();
        
        // Redirect to login
        header('Location: /index.php');
        exit;
    }
    
    /**
     * Register new user (Super Admin only)
     * @param array $data
     */
    public function register($data) {
        // Check authorization
        if (!$this->isSuperAdmin()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        // Validate input
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $full_name = trim($data['full_name'] ?? '');
        $role = $data['role'] ?? 'reporter';
        $department_id = $data['department_id'] ?? null;
        
        if (!$this->validateRegistrationData($email, $password, $full_name)) {
            return ['success' => false, 'message' => 'Invalid input data'];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Insert user
        $query = "INSERT INTO users (full_name, email, password, role, department_id, status) 
                  VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($query);
        
        try {
            $stmt->execute([$full_name, $email, $hashed_password, $role, $department_id]);
            $user_id = $pdo->lastInsertId();
            
            // Log activity
            $this->logActivity($_SESSION['user_id'], 'CREATE_USER', null, "Created user: $email");
            
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $user_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error'];
        }
    }
}
```

### 1.2 Session Validation Middleware

```php
// File: backend/middleware/AuthMiddleware.php

class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     * Must be called on every protected page
     */
    public static function requireLogin() {
        session_start();
        
        // Check if session exists
        if (!isset($_SESSION['user_id'])) {
            header('Location: /index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        // Check session timeout (30 minutes of inactivity)
        $timeout = 30 * 60; // 30 minutes in seconds
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            session_destroy();
            header('Location: /index.php?message=Session expired');
            exit;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID every 5 minutes (prevent fixation)
        if (!isset($_SESSION['session_generated']) || (time() - $_SESSION['session_generated']) > 300) {
            session_regenerate_id(true);
            $_SESSION['session_generated'] = time();
        }
    }
    
    /**
     * Check if user has specific role
     */
    public static function requireRole($allowed_roles) {
        self::requireLogin();
        
        $user_role = $_SESSION['role'] ?? null;
        
        if (!in_array($user_role, (array)$allowed_roles)) {
            http_response_code(403);
            die('Access Denied: Insufficient permissions');
        }
    }
    
    /**
     * Get current user ID from session
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role from session
     */
    public static function getCurrentUserRole() {
        return $_SESSION['role'] ?? null;
    }
}
```

---

## 2. Authorization & Access Control

### 2.1 Role-Based Access Control (RBAC)

```php
// File: backend/middleware/RoleMiddleware.php

class RoleMiddleware {
    
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_DEPT_ADMIN = 'department_admin';
    const ROLE_REPORTER = 'reporter';
    
    /**
     * Define permissions for each role
     */
    private static $permissions = [
        self::ROLE_SUPER_ADMIN => [
            'view_all_reports',
            'edit_all_reports',
            'delete_reports',
            'view_activity_logs',
            'manage_users',
            'view_statistics',
            'override_assignments'
        ],
        self::ROLE_DEPT_ADMIN => [
            'view_assigned_reports',
            'update_assigned_reports',
            'add_remarks',
            'view_department_statistics'
        ],
        self::ROLE_REPORTER => [
            'submit_reports',
            'view_own_reports',
            'view_notifications'
        ]
    ];
    
    /**
     * Check if user can perform action
     */
    public static function can($action) {
        $user_role = $_SESSION['role'] ?? null;
        
        if (!$user_role) {
            return false;
        }
        
        return in_array($action, self::$permissions[$user_role] ?? []);
    }
    
    /**
     * Enforce permission (die if not allowed)
     */
    public static function enforce($action) {
        if (!self::can($action)) {
            http_response_code(403);
            die('Access Denied: You do not have permission for this action');
        }
    }
    
    /**
     * Check if Super Admin
     */
    public static function isSuperAdmin() {
        return ($_SESSION['role'] ?? null) === self::ROLE_SUPER_ADMIN;
    }
    
    /**
     * Check if Department Admin
     */
    public static function isDepartmentAdmin() {
        return ($_SESSION['role'] ?? null) === self::ROLE_DEPT_ADMIN;
    }
    
    /**
     * Check if Reporter
     */
    public static function isReporter() {
        return ($_SESSION['role'] ?? null) === self::ROLE_REPORTER;
    }
}
```

### 2.2 Resource-Level Authorization

```php
// File: backend/middleware/ResourceMiddleware.php

class ResourceMiddleware {
    
    /**
     * Check if user can view this report
     * - Super Admin: can view all
     * - Dept Admin: can view assigned reports
     * - Reporter: can view own reports
     */
    public static function canViewReport($user_id, $report_id, $pdo) {
        $user_role = $_SESSION['role'] ?? null;
        
        // Get report details
        $query = "SELECT reported_by, assigned_admin FROM maintenance_reports WHERE report_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            return false; // Report doesn't exist
        }
        
        // Super Admin can view all
        if ($user_role === 'super_admin') {
            return true;
        }
        
        // Dept Admin can view assigned reports
        if ($user_role === 'department_admin' && $report['assigned_admin'] == $user_id) {
            return true;
        }
        
        // Reporter can view own reports
        if ($user_role === 'reporter' && $report['reported_by'] == $user_id) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can update this report
     * - Super Admin: can update all
     * - Dept Admin: can update assigned reports
     * - Reporter: cannot update
     */
    public static function canUpdateReport($user_id, $report_id, $pdo) {
        $user_role = $_SESSION['role'] ?? null;
        
        if ($user_role === 'reporter') {
            return false; // Reporters cannot update
        }
        
        // Get report details
        $query = "SELECT assigned_admin FROM maintenance_reports WHERE report_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            return false;
        }
        
        // Super Admin can update all
        if ($user_role === 'super_admin') {
            return true;
        }
        
        // Dept Admin can update assigned reports only
        if ($user_role === 'department_admin' && $report['assigned_admin'] == $user_id) {
            return true;
        }
        
        return false;
    }
}
```

---

## 3. Report Management Logic

### 3.1 Create Report

```php
// File: backend/services/ReportService.php

class ReportService {
    
    private $pdo;
    private $notificationService;
    private $assignmentService;
    private $activityService;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->notificationService = new NotificationService($pdo);
        $this->assignmentService = new AssignmentService($pdo);
        $this->activityService = new ActivityService($pdo);
    }
    
    /**
     * Create new maintenance report
     * @param array $data
     * @param int $reporter_id
     * @return array ['success' => bool, 'report_id' => int, 'message' => string]
     */
    public function createReport($data, $reporter_id) {
        // Step 1: Validate input
        $validation = $this->validateReportInput($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['error']];
        }
        
        // Step 2: Sanitize input
        $facility_type = $data['facility_type'];
        $location = trim($data['location']);
        $description = trim($data['description']);
        $priority = $data['priority'] ?? 'medium';
        $image_path = null;
        
        // Step 3: Handle image upload
        if (!empty($_FILES['image'])) {
            $image_path = $this->handleImageUpload($_FILES['image']);
            if (!$image_path) {
                return ['success' => false, 'message' => 'Image upload failed'];
            }
        }
        
        // Step 4: Start transaction
        try {
            $this->pdo->beginTransaction();
            
            // Step 5: Insert report
            $query = "INSERT INTO maintenance_reports 
                      (reported_by, facility_type, location, description, 
                       image_path, priority, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $reporter_id,
                $facility_type,
                $location,
                $description,
                $image_path,
                $priority
            ]);
            
            $report_id = $this->pdo->lastInsertId();
            
            // Step 6: Auto-assign to department admin
            $assigned_admin_id = $this->assignmentService->assignToAdmin($report_id, $facility_type);
            
            if ($assigned_admin_id) {
                $update_query = "UPDATE maintenance_reports SET assigned_admin = ? WHERE report_id = ?";
                $stmt = $this->pdo->prepare($update_query);
                $stmt->execute([$assigned_admin_id, $report_id]);
            }
            
            // Step 7: Create notifications
            $this->notificationService->notifyDepartmentAdmin($report_id, $assigned_admin_id);
            $this->notificationService->notifySuperAdmin($report_id);
            
            // Step 8: Log activity
            $this->activityService->log($reporter_id, 'CREATE_REPORT', $report_id, 
                                       "Submitted report for $facility_type at $location");
            
            // Step 9: Commit transaction
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Report submitted successfully',
                'report_id' => $report_id
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to create report'];
        }
    }
    
    /**
     * Validate report input
     */
    private function validateReportInput($data) {
        $facility_type = $data['facility_type'] ?? '';
        $location = trim($data['location'] ?? '');
        $description = trim($data['description'] ?? '');
        
        if (empty($facility_type)) {
            return ['valid' => false, 'error' => 'Facility type is required'];
        }
        
        if (!in_array($facility_type, ['aircon', 'electrical', 'plumbing', 'other'])) {
            return ['valid' => false, 'error' => 'Invalid facility type'];
        }
        
        if (empty($location)) {
            return ['valid' => false, 'error' => 'Location is required'];
        }
        
        if (strlen($location) < 3 || strlen($location) > 255) {
            return ['valid' => false, 'error' => 'Location must be 3-255 characters'];
        }
        
        if (empty($description)) {
            return ['valid' => false, 'error' => 'Description is required'];
        }
        
        if (strlen($description) < 10 || strlen($description) > 1000) {
            return ['valid' => false, 'error' => 'Description must be 10-1000 characters'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Handle image upload
     */
    private function handleImageUpload($file) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate MIME type
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            return false;
        }
        
        // Generate unique filename
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Store in secure directory (outside webroot)
        $upload_dir = __DIR__ . '/../../uploads/reports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $filepath = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return false;
        }
        
        return 'reports/' . $filename;
    }
}
```

### 3.2 Update Report Status

```php
// File: backend/services/ReportService.php (continued)

    /**
     * Update report status
     * @param int $report_id
     * @param string $new_status
     * @param int $updated_by
     * @param string $remarks
     * @return array
     */
    public function updateReportStatus($report_id, $new_status, $updated_by, $remarks = null) {
        // Step 1: Validate status transition
        if (!$this->isValidStatusTransition($report_id, $new_status)) {
            return ['success' => false, 'message' => 'Invalid status transition'];
        }
        
        // Step 2: Check authorization
        if (!ResourceMiddleware::canUpdateReport($updated_by, $report_id, $this->pdo)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        try {
            // Step 3: Start transaction
            $this->pdo->beginTransaction();
            
            // Step 4: Get report details before update
            $query = "SELECT reported_by, status FROM maintenance_reports WHERE report_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$report_id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Step 5: Update status
            $fixed_at = ($new_status === 'fixed') ? date('Y-m-d H:i:s') : NULL;
            
            $update_query = "UPDATE maintenance_reports 
                            SET status = ?, remarks = ?, fixed_at = ? 
                            WHERE report_id = ?";
            
            $stmt = $this->pdo->prepare($update_query);
            $stmt->execute([$new_status, $remarks, $fixed_at, $report_id]);
            
            // Step 6: Create notifications based on status
            if ($new_status === 'ongoing') {
                // Notify reporter that work started
                $this->notificationService->createNotification(
                    $report['reported_by'],
                    $report_id,
                    'Repair Started',
                    'Your maintenance request is now being worked on.'
                );
            } elseif ($new_status === 'fixed') {
                // Notify reporter and super admin
                $this->notificationService->createNotification(
                    $report['reported_by'],
                    $report_id,
                    'Issue Fixed',
                    'Your maintenance request has been completed.'
                );
                
                $this->notificationService->notifySuperAdmin($report_id);
            }
            
            // Step 7: Log activity
            $this->activityService->log($updated_by, 'UPDATE_REPORT_STATUS', $report_id,
                                       "Changed status from {$report['status']} to $new_status");
            
            // Step 8: Commit transaction
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Status updated successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }
    
    /**
     * Validate status transition
     * pending -> ongoing -> fixed
     * Any -> cancelled
     */
    private function isValidStatusTransition($report_id, $new_status) {
        $valid_statuses = ['pending', 'ongoing', 'fixed', 'cancelled'];
        
        if (!in_array($new_status, $valid_statuses)) {
            return false;
        }
        
        // Get current status
        $query = "SELECT status FROM maintenance_reports WHERE report_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            return false;
        }
        
        $current_status = $report['status'];
        
        // Define allowed transitions
        $transitions = [
            'pending' => ['ongoing', 'cancelled'],
            'ongoing' => ['fixed', 'pending', 'cancelled'],
            'fixed' => [], // Cannot change from fixed
            'cancelled' => [] // Cannot change from cancelled
        ];
        
        return in_array($new_status, $transitions[$current_status] ?? []);
    }
}
```

---

## 4. Notification System

### 4.1 Notification Service

```php
// File: backend/services/NotificationService.php

class NotificationService {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a notification record
     */
    public function createNotification($user_id, $report_id, $title, $message) {
        $query = "INSERT INTO notifications (user_id, report_id, title, message, is_read) 
                  VALUES (?, ?, ?, ?, FALSE)";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$user_id, $report_id, $title, $message]);
    }
    
    /**
     * Notify department admin of new report
     */
    public function notifyDepartmentAdmin($report_id, $admin_id = null) {
        if (!$admin_id) {
            return false;
        }
        
        $query = "SELECT facility_type, location FROM maintenance_reports WHERE report_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $title = "New Maintenance Request Assigned";
        $message = "New {$report['facility_type']} issue at {$report['location']}. Report ID: #$report_id";
        
        return $this->createNotification($admin_id, $report_id, $title, $message);
    }
    
    /**
     * Notify super admin of new report
     */
    public function notifySuperAdmin($report_id) {
        // Get super admin user ID
        $query = "SELECT user_id FROM users WHERE role = 'super_admin' AND status = 'active' LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            return false;
        }
        
        $query = "SELECT facility_type FROM maintenance_reports WHERE report_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $title = "New Maintenance Report";
        $message = "New {$report['facility_type']} maintenance request submitted. Report ID: #$report_id";
        
        return $this->createNotification($admin['user_id'], $report_id, $title, $message);
    }
    
    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications($user_id, $limit = 10) {
        $query = "SELECT notification_id, report_id, title, message, created_at 
                  FROM notifications 
                  WHERE user_id = ? AND is_read = FALSE 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id) {
        $query = "UPDATE notifications 
                  SET is_read = TRUE, read_at = NOW() 
                  WHERE notification_id = ?";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$notification_id]);
    }
    
    /**
     * Get notification count for user
     */
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM notifications 
                  WHERE user_id = ? AND is_read = FALSE";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }
}
```

---

## 5. Activity Logging

### 5.1 Activity Service

```php
// File: backend/services/ActivityService.php

class ActivityService {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Log an activity/action
     */
    public function log($user_id, $action, $report_id = null, $description = null) {
        $query = "INSERT INTO activity_logs (user_id, action, report_id, description) 
                  VALUES (?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$user_id, $action, $report_id, $description]);
    }
    
    /**
     * Get activity logs for audit trail
     */
    public function getActivityLogs($filters = [], $limit = 100, $offset = 0) {
        $query = "SELECT l.log_id, l.action, l.description, l.timestamp, 
                         u.full_name, r.report_id, r.facility_type
                  FROM activity_logs l
                  JOIN users u ON l.user_id = u.user_id
                  LEFT JOIN maintenance_reports r ON l.report_id = r.report_id
                  WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $query .= " AND l.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $query .= " AND l.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['report_id'])) {
            $query .= " AND l.report_id = ?";
            $params[] = $filters['report_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(l.timestamp) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(l.timestamp) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $query .= " ORDER BY l.timestamp DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

---

## 6. Auto-Assignment Logic

### 6.1 Assignment Service

```php
// File: backend/services/AssignmentService.php

class AssignmentService {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Auto-assign report to department admin
     * @param int $report_id
     * @param string $facility_type
     * @return int|null admin_id
     */
    public function assignToAdmin($report_id, $facility_type) {
        // Step 1: Map facility type to department
        $department_map = [
            'aircon' => 'aircon',
            'electrical' => 'electrical',
            'plumbing' => 'plumbing',
            'other' => null // No specific admin for 'other'
        ];
        
        $department = $department_map[$facility_type] ?? null;
        
        if (!$department) {
            return null; // No specific department for 'other'
        }
        
        // Step 2: Get department ID
        $query = "SELECT department_id FROM departments WHERE department_name = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$department]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dept) {
            return null;
        }
        
        // Step 3: Get active admin for this department
        $query = "SELECT user_id FROM users 
                  WHERE role = 'department_admin' 
                  AND department_id = ? 
                  AND status = 'active'
                  ORDER BY created_at ASC 
                  LIMIT 1";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$dept['department_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $admin['user_id'] ?? null;
    }
    
    /**
     * Get workload of admin (number of active reports)
     */
    public function getAdminWorkload($admin_id) {
        $query = "SELECT COUNT(*) as count FROM maintenance_reports 
                  WHERE assigned_admin = ? AND status IN ('pending', 'ongoing')";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$admin_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }
    
    /**
     * Reassign report to different admin (Super Admin only)
     */
    public function reassignReport($report_id, $new_admin_id) {
        $query = "UPDATE maintenance_reports SET assigned_admin = ? WHERE report_id = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$new_admin_id, $report_id]);
    }
}
```

---

## 7. Pseudo-code Summary

### Page Flow: Login Page (index.php)

```
DISPLAY login form
IF form submitted THEN
    GET email and password from POST
    CALL AuthController.login(email, password)
    IF login successful THEN
        REDIRECT to dashboard.php
    ELSE
        DISPLAY error message
    END IF
END IF
```

### Page Flow: Dashboard (dashboard.php)

```
CALL AuthMiddleware.requireLogin()
GET user_role from session

IF user_role = 'super_admin' THEN
    CALL DashboardController.getSuperAdminDashboard()
    DISPLAY all reports with statistics
    DISPLAY filter controls
ELSE IF user_role = 'department_admin' THEN
    CALL DashboardController.getDepartmentDashboard()
    DISPLAY assigned reports
    DISPLAY filter controls
ELSE IF user_role = 'reporter' THEN
    CALL DashboardController.getReporterDashboard()
    DISPLAY own reports
    DISPLAY submit report button
END IF

GET unread notifications
DISPLAY notification count and list
```

### Page Flow: Submit Report (report-submit.php)

```
CALL AuthMiddleware.requireRole(['reporter'])
DISPLAY report submission form

IF form submitted THEN
    GET form data
    CALL ReportService.createReport(data, current_user_id)
    IF report created successfully THEN
        DISPLAY success message
        DISPLAY report ID
        REDIRECT to report detail page
    ELSE
        DISPLAY error message
    END IF
END IF
```

### Page Flow: Update Report Status (report-detail.php)

```
CALL AuthMiddleware.requireLogin()
GET report_id from URL parameter
CALL ReportService.getReportDetail(report_id)

CHECK authorization with ResourceMiddleware
IF not authorized THEN
    DISPLAY error and exit
END IF

DISPLAY report details
IF user can update report THEN
    DISPLAY status update form
    
    IF form submitted THEN
        GET new_status and remarks
        CALL ReportService.updateReportStatus(report_id, new_status, current_user_id, remarks)
        IF successful THEN
            DISPLAY success message
            REFRESH report details
        ELSE
            DISPLAY error message
        END IF
    END IF
END IF
```

---

## 8. Database Transaction Example

```php
// Safe multi-step operation with rollback

try {
    $pdo->beginTransaction();
    
    // Step 1
    $stmt = $pdo->prepare("INSERT INTO maintenance_reports ...");
    $stmt->execute([...]);
    $report_id = $pdo->lastInsertId();
    
    // Step 2
    $stmt = $pdo->prepare("UPDATE maintenance_reports SET assigned_admin = ?");
    $stmt->execute([$admin_id]);
    
    // Step 3
    $stmt = $pdo->prepare("INSERT INTO notifications ...");
    $stmt->execute([...]);
    
    // Step 4
    $stmt = $pdo->prepare("INSERT INTO activity_logs ...");
    $stmt->execute([...]);
    
    // All succeeded - commit
    $pdo->commit();
    
    return ['success' => true, 'report_id' => $report_id];
    
} catch (Exception $e) {
    // Something failed - rollback all
    $pdo->rollBack();
    return ['success' => false, 'message' => 'Operation failed'];
}
```

---

## Summary of Key Services

| Service | Responsibility |
|---------|-----------------|
| `AuthController` | Login/logout/registration |
| `AuthMiddleware` | Session validation |
| `RoleMiddleware` | Permission checks |
| `ReportService` | Report CRUD & logic |
| `NotificationService` | Create/manage notifications |
| `AssignmentService` | Auto-assign reports |
| `ActivityService` | Audit logging |
| `ValidationService` | Input validation |

