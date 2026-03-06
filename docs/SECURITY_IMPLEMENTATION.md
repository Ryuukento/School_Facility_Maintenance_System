# Security Implementation Guide

## 1. Overview

This document outlines all security measures implemented in the School Facility Maintenance Reporting System to protect against common web vulnerabilities.

---

## 2. Authentication Security

### 2.1 Password Hashing

**Algorithm**: bcrypt  
**Cost Factor**: 12 (stronger security, slight performance trade-off)  
**Not Used**: MD5, SHA1, unsalted hashes

```php
// Correct password hashing
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Correct password verification
if (password_verify($input_password, $hashed)) {
    // Valid password
}
```

**Why bcrypt?**
- Automatically salts passwords
- Resistant to rainbow table attacks
- Adaptive: cost factor can increase as computers get faster
- Built into PHP, no external library needed

### 2.2 Password Requirements

**Enforce on Registration:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

**Validation Code**:
```php
function validatePassword($password) {
    $requirements = [
        'length' => strlen($password) >= 8,
        'upper' => preg_match('/[A-Z]/', $password),
        'lower' => preg_match('/[a-z]/', $password),
        'digit' => preg_match('/[0-9]/', $password),
        'special' => preg_match('/[!@#$%^&*]/', $password)
    ];
    
    return array_all($requirements);
}
```

---

## 3. SQL Injection Prevention

### 3.1 Prepared Statements (Required)

**VULNERABLE - DO NOT USE**:
```php
// SQL INJECTION RISK
$query = "SELECT * FROM users WHERE email = '" . $email . "'";
$stmt = $pdo->query($query);
```

**SECURE - ALWAYS USE**:
```php
// Safe: Parameter placeholder
$query = "SELECT * FROM users WHERE email = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch();

// OR using named parameters
$query = "SELECT * FROM users WHERE email = :email";
$stmt = $pdo->prepare($query);
$stmt->execute([':email' => $email]);
```

### 3.2 Implementation Pattern

```php
// ALWAYS use prepared statements for EVERY database query

// Pattern 1: Positional parameters (?)
$query = "INSERT INTO users (email, name, role) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($query);
$stmt->execute([$email, $name, $role]);

// Pattern 2: Named parameters (:name)
$query = "SELECT * FROM reports WHERE status = :status AND priority = :priority";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':status' => $status,
    ':priority' => $priority
]);

// Pattern 3: PDO type specification
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bindParam(1, $id, PDO::PARAM_INT);
$stmt->execute();
```

### 3.3 Common Mistakes to Avoid

```php
// WRONG: String concatenation
$query = "SELECT * FROM reports WHERE id = $id"; // Vulnerable

// WRONG: Variable substitution
$query = "SELECT * FROM reports WHERE id = $id"; // Vulnerable even with quotes

// CORRECT: Prepared statement
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->execute([$id]);
```

---

## 4. Cross-Site Scripting (XSS) Prevention

### 4.1 Output Encoding

**VULNERABLE - DO NOT USE**:
```php
// XSS Risk: User input directly echoed
echo "Welcome " . $_GET['name']; // If name = "<script>alert('hacked')</script>"
```

**SECURE - ALWAYS USE**:
```php
// Safe: HTML-encode output
echo "Welcome " . htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');

// Or using shorthand
echo "Welcome " . htmlesc($_GET['name']);
```

### 4.2 Encoding for Different Contexts

```php
// HTML context: <div>USER_DATA</div>
echo htmlspecialchars($user_data, ENT_QUOTES, 'UTF-8');

// JavaScript context: var x = "USER_DATA";
echo json_encode($user_data);

// URL context: <a href="?id=USER_DATA">
echo urlencode($user_data);

// CSS context: <div style="color: USER_DATA;">
echo preg_replace('/[^a-zA-Z0-9#]/', '', $user_data);
```

### 4.3 Template Implementation

```php
// Create a safe echo function
function htmlesc($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Use in templates
<h1>Welcome <?= htmlesc($user['full_name']) ?></h1>
<p><?= htmlesc($report['description']) ?></p>
<img src="<?= htmlesc($report['image_path']) ?>" />
```

---

## 5. Cross-Site Request Forgery (CSRF) Prevention

### 5.1 CSRF Token Implementation

**Generate Token**:
```php
// On session start
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

**Include in Forms**:
```html
<form method="POST" action="/backend/api/report-api.php">
    <input type="hidden" name="csrf_token" value="<?= htmlesc($_SESSION['csrf_token']) ?>">
    
    <input type="text" name="location" required>
    <textarea name="description" required></textarea>
    <button type="submit">Submit</button>
</form>
```

**Verify Token on Submission**:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $token_from_form = $_POST['csrf_token'] ?? '';
    $token_from_session = $_SESSION['csrf_token'] ?? '';
    
    if (!hash_equals($token_from_form, $token_from_session)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
    
    // Safe to process form
    // ...
}
```

### 5.2 SameSite Cookie Attribute

```php
// Set SameSite on session cookie
session_set_cookie_params([
    'samesite' => 'Strict'  // Prevent cross-site requests
]);

// Strict: Cookie never sent on cross-site requests
// Lax: Cookie sent on safe cross-site requests (GET)
// None: Cookie sent on all cross-site requests (requires Secure flag)
```

---

## 6. Session Security

### 6.1 Session Configuration

```php
// File: config/session.php

// Use secure settings
ini_set('session.cookie_httponly', 1);     // Prevent JS access
ini_set('session.cookie_secure', 1);       // HTTPS only
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.use_only_cookies', 1);    // Disable URL-based sessions
ini_set('session.use_strict_mode', 1);     // Prevent fixation

session_set_cookie_params([
    'lifetime' => 0,           // Until browser closes
    'path' => '/',
    'domain' => '',
    'secure' => true,          // HTTPS only
    'httponly' => true,        // No JS access
    'samesite' => 'Strict'
]);
```

### 6.2 Session Timeout

```php
// Check session expiry on each request
$timeout = 30 * 60; // 30 minutes

if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity']) > $timeout) {
    
    // Session expired
    session_destroy();
    header('Location: /index.php?message=Session expired');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();
```

### 6.3 Session Fixation Prevention

```php
// Regenerate session ID regularly
if (!isset($_SESSION['regenerated']) || 
    (time() - $_SESSION['regenerated']) > 300) {
    
    // Regenerate every 5 minutes
    session_regenerate_id(true); // Delete old session file
    $_SESSION['regenerated'] = time();
}
```

---

## 7. Input Validation & Sanitization

### 7.1 Server-Side Validation (Required)

**NEVER trust browser validation alone**

```php
function validateReportInput($data) {
    $errors = [];
    
    // Facility type
    $valid_types = ['aircon', 'electrical', 'plumbing', 'other'];
    if (!in_array($data['facility_type'] ?? '', $valid_types)) {
        $errors[] = 'Invalid facility type';
    }
    
    // Location
    $location = trim($data['location'] ?? '');
    if (empty($location) || strlen($location) < 3 || strlen($location) > 255) {
        $errors[] = 'Location must be 3-255 characters';
    }
    
    // Description
    $description = trim($data['description'] ?? '');
    if (empty($description) || strlen($description) < 10 || strlen($description) > 1000) {
        $errors[] = 'Description must be 10-1000 characters';
    }
    
    // Priority
    $valid_priorities = ['low', 'medium', 'high', 'critical'];
    if (!in_array($data['priority'] ?? 'medium', $valid_priorities)) {
        $errors[] = 'Invalid priority level';
    }
    
    return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
}
```

### 7.2 Whitelist Approach

```php
// Define allowed values
$allowed_statuses = ['pending', 'ongoing', 'fixed', 'cancelled'];
$allowed_departments = ['aircon', 'electrical', 'plumbing'];

// Validate against whitelist
if (!in_array($user_input, $allowed_statuses)) {
    throw new InvalidArgumentException('Invalid status');
}
```

### 7.3 Input Sanitization

```php
// Trim whitespace
$input = trim($user_input);

// Remove dangerous characters
$safe_input = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $input);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Invalid email format');
}

// Cast to type
$id = (int) $_GET['id'];
```

---

## 8. File Upload Security

### 8.1 Validation

```php
function validateFileUpload($file) {
    // Check if file was uploaded
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Invalid file upload'];
    }
    
    // Allowed MIME types
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_mimes)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    // Validate MIME type with finfo (not just file extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_mimes)) {
        return ['valid' => false, 'error' => 'File MIME type does not match'];
    }
    
    // Maximum file size: 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['valid' => false, 'error' => 'File size exceeds 5MB'];
    }
    
    return ['valid' => true];
}
```

### 8.2 Secure Storage

```php
function storeUploadedFile($file) {
    // Generate unique filename (prevent overwrite/guess)
    $filename = time() . '_' . bin2hex(random_bytes(16)) . '.' . 
                pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Store OUTSIDE webroot if possible
    $upload_dir = __DIR__ . '/../../secure-uploads/';
    
    // Create directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0700, true); // 0700: Only owner can read/write
    }
    
    $filepath = $upload_dir . $filename;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }
    
    // Set proper permissions
    chmod($filepath, 0600); // Only owner can read
    
    return $filename;
}
```

### 8.3 File Retrieval

```php
function serveUploadedFile($filename) {
    // Prevent directory traversal
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
        http_response_code(403);
        die('Access denied');
    }
    
    $filepath = __DIR__ . '/../../secure-uploads/' . $filename;
    
    // Verify file exists
    if (!file_exists($filepath)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Verify permission to access
    // (Would check if user is report owner, admin, etc.)
    
    // Serve file
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: inline; filename="' . htmlspecialchars($filename) . '"');
    readfile($filepath);
}
```

---

## 9. Access Control Enforcement

### 9.1 Page-Level Access

```php
// ALWAYS check on every protected page

require_once 'backend/middleware/AuthMiddleware.php';

// Require user to be logged in
AuthMiddleware::requireLogin();

// Optionally require specific role
AuthMiddleware::requireRole(['super_admin', 'department_admin']);
```

### 9.2 Resource-Level Access

```php
// Verify user can access specific resource

$report_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

if (!canAccessReport($user_id, $report_id, $pdo)) {
    http_response_code(403);
    die('Access denied');
}

// Safe to proceed
```

---

## 10. Database Connection Security

### 10.1 Connection String

```php
// File: config/database.php

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Force real prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    // Log error (don't expose details to user)
    error_log($e->getMessage());
    die('Database connection failed');
}
```

### 10.2 Credentials Management

```php
// File: config/.env (NOT in version control)

DB_HOST=localhost
DB_USER=school_maintenance_user
DB_PASS=SecurePasswordHere123!@#
DB_NAME=school_maintenance_system

// Load in PHP
$config = parse_ini_file(__DIR__ . '/.env');
```

**Never hardcode credentials in code**

---

## 11. Error Handling & Logging

### 11.1 Error Handling

```php
// Don't expose errors to users
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log to file
ini_set('error_log', '/var/log/php-errors.log');

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] in $errfile:$errline - $errstr");
    // Don't expose details to user
    http_response_code(500);
    die('An error occurred. Please try again later.');
});
```

### 11.2 Logging Activities

```php
function logActivity($user_id, $action, $report_id, $description) {
    global $pdo;
    
    $query = "INSERT INTO activity_logs (user_id, action, report_id, description, timestamp) 
              VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $action, $report_id, $description]);
    
    // Also log to file for critical actions
    if (in_array($action, ['LOGIN', 'DELETE_REPORT', 'CHANGE_USER_ROLE'])) {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] USER_ID:$user_id ACTION:$action REPORT_ID:$report_id");
    }
}
```

---

## 12. HTTPS/TLS Requirements

### 12.1 Force HTTPS

```php
// Redirect HTTP to HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirect);
    exit;
}

// Or use htaccess
/*
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
*/
```

### 12.2 Security Headers

```php
// Add security headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'');
```

---

## 13. Security Checklist

Before deployment:

- [ ] All passwords hashed with bcrypt
- [ ] All database queries use prepared statements
- [ ] All user output HTML-encoded
- [ ] CSRF tokens on all forms
- [ ] Session timeout implemented (30 min)
- [ ] Password requirements enforced
- [ ] File uploads validated and stored securely
- [ ] Error messages don't expose system details
- [ ] Database credentials in .env (not in code)
- [ ] HTTPS enabled and enforced
- [ ] Security headers set
- [ ] Activity logging for audit trail
- [ ] Prepared statements for ALL queries
- [ ] Input validation on server side
- [ ] Authorization checks on every protected page
- [ ] Log files permissions restricted

---

## 14. Regular Security Maintenance

- **Update PHP & MySQL** to latest stable versions
- **Audit logs** weekly for suspicious activity
- **Review failed login** attempts
- **Monitor file permissions** monthly
- **Update dependencies** quarterly
- **Security code review** before major releases

