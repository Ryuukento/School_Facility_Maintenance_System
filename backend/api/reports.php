<?php
/**
 * Reports API
 * Handles maintenance report CRUD operations
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Establish database connection
$pdo = getDBConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Unauthorized. Please login first.');
    exit;
}

// Ensure role is set (default to admin if not set for backwards compatibility)
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'super_admin';
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'list':
        getReports();
        break;
    case 'get':
        getReport();
        break;
    case 'create':
        createReport();
        break;
    case 'update':
        updateReport();
        break;
    case 'delete':
        deleteReport();
        break;
    case 'stats':
        getStats();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Get list of reports
 */
function getReports() {
    global $pdo;
    
    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? 'super_admin';
    
    // Log the request
    error_log("getReports called - User ID: $userId, Role: $role");
    
    if (!$userId) {
        error_log("getReports error: User ID not found in session");
        sendResponse(false, 'User ID not found in session');
        return;
    }
    
    try {
        // Build query based on role
        $query = "
            SELECT 
                r.*,
                creator.full_name as creator_name,
                creator.email as creator_email,
                assigned.full_name as assigned_name,
                d.name as department_name
            FROM maintenance_reports r
            LEFT JOIN users creator ON r.created_by = creator.user_id
            LEFT JOIN users assigned ON r.assigned_to = assigned.user_id
            LEFT JOIN departments d ON r.department_id = d.department_id
        ";
        
        $stmt = null;
        
        // Filter based on role
        if ($role === 'user' || $role === 'reporter') {
            // Regular users see only their own reports
            $query .= " WHERE r.created_by = ? ORDER BY r.created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId]);
            error_log("getReports: Filtering for user reports, user_id=$userId");
        } elseif ($role === 'maintenance_staff') {
            // Staff sees assigned reports
            $query .= " WHERE r.assigned_to = ? ORDER BY r.created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId]);
            error_log("getReports: Filtering for assigned reports, user_id=$userId");
        } elseif ($role === 'department_admin') {
            // Department admin sees their department's reports
            $deptStmt = $pdo->prepare("SELECT department_id FROM users WHERE user_id = ?");
            $deptStmt->execute([$userId]);
            $deptId = $deptStmt->fetchColumn();
            
            if ($deptId) {
                $query .= " WHERE r.department_id = ? ORDER BY r.created_at DESC";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$deptId]);
                error_log("getReports: Filtering for department reports, dept_id=$deptId");
            } else {
                // If no department found, return empty list
                error_log("getReports: No department found for user_id=$userId");
                sendResponse(true, 'Reports retrieved successfully', ['reports' => [], 'count' => 0]);
                return;
            }
        } else {
            // Super admin sees everything (including reports with null created_by)
            $query .= " ORDER BY r.created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            error_log("getReports: Super admin - fetching all reports");
        }
        
        if (!$stmt) {
            throw new Exception("Statement preparation failed");
        }
        
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure reports is an array
        if (!is_array($reports)) {
            $reports = [];
        }
        
        error_log("getReports: Found " . count($reports) . " reports");
        
        sendResponse(true, 'Reports retrieved successfully', [
            'reports' => $reports,
            'count' => count($reports)
        ]);
        
    } catch (Exception $e) {
        error_log("Get reports error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        sendResponse(false, 'Failed to retrieve reports: ' . $e->getMessage());
    }
}

/**
 * Get single report
 */
function getReport() {
    global $pdo;
    
    $reportId = $_GET['id'] ?? 0;
    
    if (!$reportId) {
        sendResponse(false, 'Report ID required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                r.*,
                creator.full_name as creator_name,
                creator.email as creator_email,
                assigned.full_name as assigned_name,
                d.name as department_name
            FROM maintenance_reports r
            LEFT JOIN users creator ON r.created_by = creator.user_id
            LEFT JOIN users assigned ON r.assigned_to = assigned.user_id
            LEFT JOIN departments d ON r.department_id = d.department_id
            WHERE r.report_id = ?
        ");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
        
        if (!$report) {
            sendResponse(false, 'Report not found');
            return;
        }
        
        sendResponse(true, 'Report retrieved successfully', ['report' => $report]);
        
    } catch (Exception $e) {
        error_log("Get report error: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve report');
    }
}

/**
 * Create new report
 */
function createReport() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }
    
    // CRITICAL: Check that user is logged in (session user_id exists)
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        sendResponse(false, 'Session expired. Please log in again.');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    error_log('createReport payload: ' . var_export($input, true));
    
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $location = $input['location'] ?? '';
    $priority = $input['priority'] ?? 'medium';
    $departmentId = $input['department_id'] ?? null;
    
    // Validate
    if (empty($title) || empty($description) || empty($location)) {
        sendResponse(false, 'Title, description, and location are required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO maintenance_reports (
                title, description, location, priority, 
                status, created_by, department_id
            ) VALUES (?, ?, ?, ?, 'submitted', ?, ?)
        ");
        
        $stmt->execute([
            $title,
            $description,
            $location,
            $priority,
            $_SESSION['user_id'],
            $departmentId
        ]);
        
        $reportId = $pdo->lastInsertId();
        error_log('createReport inserted id: ' . $reportId);
        
        // Log activity
        logActivity($pdo, $_SESSION['user_id'], 'CREATE_REPORT', 'report', $reportId, 
            "Created report: $title");
        
        // fetch inserted report details
        $newStmt = $pdo->prepare("SELECT r.*, u.full_name as creator_name FROM maintenance_reports r LEFT JOIN users u ON r.created_by = u.user_id WHERE r.report_id = ?");
        $newStmt->execute([$reportId]);
        $newReport = $newStmt->fetch(PDO::FETCH_ASSOC);

        // ── Notify all active admin roles (in-app notification + Gmail) ───
        try {
            require_once __DIR__ . '/../models/Notification.php';
            require_once __DIR__ . '/../services/EmailService.php';

            $notification = new Notification($pdo);

            // Get all admin recipients, with super admin fallback safety.
            $adminRecipients = fetchAdminNotificationRecipients($pdo);

            $submitterName = $_SESSION['full_name'] ?? $_SESSION['user']['full_name'] ?? 'A staff member';

            foreach ($adminRecipients as $admin) {
                // 1. In-app bell notification
                $created = $notification->create([
                    'user_id'   => $admin['user_id'],
                    'report_id' => $reportId,
                    'title'     => 'New Maintenance Report Submitted',
                    'message'   => $submitterName . ' submitted a new report: ' . $title
                ]);

                if (!$created) {
                    error_log('[createReport] Failed to insert notification for user_id=' . (int)$admin['user_id']);
                }
            }

            // 2. Gmail email notification (batch)
            if (class_exists('EmailService')) {
                EmailService::sendNewReportNotification(
                    array_merge($newReport ?? [], ['submitted_by' => $submitterName]),
                    $adminRecipients
                );
            }
        } catch (Exception $notifEx) {
            // Never block the report save because of notification failure
            error_log('[createReport] Notification error: ' . $notifEx->getMessage());
        }
        // ───────────────────────────────────────────────────────────────────

        sendResponse(true, 'Report created successfully', ['report_id' => $reportId, 'report' => $newReport]);
        
    } catch (Exception $e) {
        error_log("Create report error: " . $e->getMessage());
        sendResponse(false, 'Failed to create report: ' . $e->getMessage());
    }
}

/**
 * Update report
 */
function updateReport() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $reportId = $input['report_id'] ?? 0;
    
    if (!$reportId) {
        sendResponse(false, 'Report ID required');
        return;
    }
    
    try {
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'location', 'priority', 'status', 'assigned_to'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            sendResponse(false, 'No fields to update');
            return;
        }
        
        $params[] = $reportId;
        
        $query = "UPDATE maintenance_reports SET " . implode(', ', $updates) . " WHERE report_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        // Log activity
        logActivity($pdo, $_SESSION['user_id'], 'UPDATE_REPORT', 'report', $reportId, 
            "Updated report #$reportId");
        
        sendResponse(true, 'Report updated successfully');
        
    } catch (Exception $e) {
        error_log("Update report error: " . $e->getMessage());
        sendResponse(false, 'Failed to update report');
    }
}

/**
 * Delete report
 */
function deleteReport() {
    global $pdo;
    
    $reportId = $_GET['id'] ?? 0;
    
    if (!$reportId) {
        sendResponse(false, 'Report ID required');
        return;
    }
    
    // Only super admin can delete
    if ($_SESSION['role'] !== 'super_admin') {
        sendResponse(false, 'Unauthorized to delete reports');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM maintenance_reports WHERE report_id = ?");
        $stmt->execute([$reportId]);
        
        logActivity($pdo, $_SESSION['user_id'], 'DELETE_REPORT', 'report', $reportId, 
            "Deleted report #$reportId");
        
        sendResponse(true, 'Report deleted successfully');
        
    } catch (Exception $e) {
        error_log("Delete report error: " . $e->getMessage());
        sendResponse(false, 'Failed to delete report');
    }
}

/**
 * Get statistics
 */
function getStats() {
    global $pdo;
    
    try {
        $stats = [
            'total' => 0,
            'submitted' => 0,
            'assigned' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'by_priority' => [
                'low' => 0,
                'medium' => 0,
                'high' => 0,
                'urgent' => 0
            ]
        ];
        
        // Get counts by status
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM maintenance_reports 
            GROUP BY status
        ");
        
        while ($row = $stmt->fetch()) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }
        
        // Get counts by priority
        $stmt = $pdo->query("
            SELECT priority, COUNT(*) as count 
            FROM maintenance_reports 
            GROUP BY priority
        ");
        
        while ($row = $stmt->fetch()) {
            $stats['by_priority'][$row['priority']] = (int)$row['count'];
        }
        
        sendResponse(true, 'Statistics retrieved successfully', ['stats' => $stats]);
        
    } catch (Exception $e) {
        error_log("Get stats error: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve statistics');
    }
}

/**
 * Log activity
 */
function logActivity($pdo, $userId, $action, $entityType = null, $entityId = null, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Resolve notification recipients for report events.
 * Prefers active admin roles and always includes super admins as fallback.
 */
function fetchAdminNotificationRecipients(PDO $pdo): array {
    $recipientsById = [];

    $primaryStmt = $pdo->prepare(
        "SELECT user_id, email, full_name
         FROM users
                 WHERE role IN ('super_admin', 'admin', 'maintenance_admin', 'department_admin')
           AND status = 'active'"
    );
    $primaryStmt->execute();

    foreach ($primaryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $uid = (int)($row['user_id'] ?? 0);
        if ($uid > 0) {
            $recipientsById[$uid] = $row;
        }
    }

    $superStmt = $pdo->prepare(
        "SELECT user_id, email, full_name
         FROM users
            WHERE role IN ('super_admin', 'admin')"
    );
    $superStmt->execute();

    foreach ($superStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $uid = (int)($row['user_id'] ?? 0);
        if ($uid > 0) {
            $recipientsById[$uid] = $row;
        }
    }

    return array_values($recipientsById);
}

/**
 * Normalize incoming date filter to Y-m-d.
 */

