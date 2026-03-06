<?php
/**
 * Maintenance Reports API
 * CRUD operations for maintenance reports
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Load Notification model
require_once dirname(__DIR__) . '/models/Notification.php';

$action = $_GET['action'] ?? null;
$reportId = $_GET['report_id'] ?? $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'recent':
            getRecentReports();
            break;
        
        case 'list':
            listReports();
            break;
        
        case 'get':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            getReport($reportId);
            break;
        
        case 'create':
            createReport($input);
            break;
        
        case 'update':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            updateReport($reportId, $input);
            break;
        
        case 'delete':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            deleteReport($reportId);
            break;
        
        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('Maintenance Reports API error', ['action' => $action, 'error' => $e->getMessage()]);
    Response::error('Internal server error', [], Response::HTTP_INTERNAL_ERROR);
}

/**
 * Get recent reports
 */
function getRecentReports() {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $sql = "SELECT r.report_id, r.title, r.priority, r.status, r.location, 
                       r.created_at, r.assigned_to, u.full_name as assigned_name
                FROM maintenance_reports r
                LEFT JOIN users u ON r.assigned_to = u.user_id
                WHERE r.created_by = ? OR r.assigned_to = ?
                ORDER BY r.created_at DESC
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        $reports = array_map(function($report) {
            return formatReportDates($report);
        }, $reports);
        
        Response::success('Recent reports retrieved', ['reports' => $reports]);
        
    } catch (Exception $e) {
        Logger::error('Error fetching recent reports', ['error' => $e->getMessage()]);
        Response::error('Failed to fetch recent reports', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * List all reports with filters
 */
function listReports() {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $status = $_GET['status'] ?? null;
        $priority = $_GET['priority'] ?? null;
        $search = $_GET['search'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT r.report_id, r.title, r.priority, r.status, r.location, 
                       r.created_at, r.assigned_to, u.full_name as assigned_name
                FROM maintenance_reports r
                LEFT JOIN users u ON r.assigned_to = u.user_id
                WHERE (r.created_by = ? OR r.assigned_to = ?)";
        
        $params = [$userId, $userId];
        
        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        
        if ($priority) {
            $sql .= " AND r.priority = ?";
            $params[] = $priority;
        }
        
        if ($search) {
            $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($dateFrom) {
            $sql .= " AND DATE(r.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(r.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reports = array_map(function($report) {
            return formatReportDates($report);
        }, $reports);
        
        Response::success('Reports retrieved', ['reports' => $reports, 'page' => $page, 'per_page' => $perPage]);
        
    } catch (Exception $e) {
        Logger::error('Error listing reports', ['error' => $e->getMessage()]);
        Response::error('Failed to list reports', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Get single report details
 */
function getReport($reportId) {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    try {
        $sql = "SELECT r.*, u.full_name as creator_name, u.email as creator_email,
                       a.full_name as assigned_name, a.email as assigned_email,
                       d.name as department_name
                FROM maintenance_reports r
                LEFT JOIN users u ON r.created_by = u.user_id
                LEFT JOIN users a ON r.assigned_to = a.user_id
                LEFT JOIN departments d ON r.department_id = d.department_id
                WHERE r.report_id = ? AND (r.created_by = ? OR r.assigned_to = ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reportId, $userId, $userId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            Response::error('Report not found', [], Response::HTTP_NOT_FOUND);
            return;
        }
        
        $report = formatReportDates($report);
        
        Response::success('Report retrieved', ['report' => $report]);
        
    } catch (Exception $e) {
        Logger::error('Error fetching report', ['error' => $e->getMessage()]);
        Response::error('Failed to fetch report', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Create new report
 */
function createReport($data) {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    try {
        // Validate input
        if (empty($data['title']) || empty($data['description']) || empty($data['location'])) {
            Response::error('Required fields missing', [], Response::HTTP_BAD_REQUEST);
            return;
        }
        
        $sql = "INSERT INTO maintenance_reports 
                (title, description, location, priority, status, created_by, 
                 department_id, due_date, assigned_to, created_at)
                VALUES (?, ?, ?, ?, 'submitted', ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['location'],
            $data['priority'] ?? 'medium',
            $userId,
            $data['department_id'] ?? null,
            $data['due_date'] ?? null,
            $data['assigned_to'] ?? null
        ]);
        
        $reportId = $pdo->lastInsertId();
        
        Logger::info('Report created', ['report_id' => $reportId, 'user_id' => $userId]);
        
        // ── Notify super admins for ANY role that creates a report ────────
        try {
            require_once dirname(__DIR__) . '/services/EmailService.php';

            $notification = new Notification($pdo);

            // Fetch full report row (with creator name) for email body
            $rStmt = $pdo->prepare("SELECT r.*, u.full_name as creator_name FROM maintenance_reports r LEFT JOIN users u ON r.created_by = u.user_id WHERE r.report_id = ?");
            $rStmt->execute([$reportId]);
            $fullReport = $rStmt->fetch(PDO::FETCH_ASSOC);

            // Get all active super admins (email included for Gmail)
            $stmt = $pdo->prepare("SELECT user_id, email, full_name FROM users WHERE role = 'super_admin' AND status = 'active'");
            $stmt->execute();
            $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $submitterName = $_SESSION['user']['full_name'] ?? 'Staff';

            foreach ($superAdmins as $admin) {
                // 1. In-app bell notification
                $notification->create([
                    'user_id'   => $admin['user_id'],
                    'report_id' => $reportId,
                    'title'     => 'New Maintenance Report Submitted',
                    'message'   => $submitterName . ' submitted a new report: ' . $data['title']
                ]);
            }

            // 2. Gmail email notification
            if (class_exists('EmailService') && $fullReport) {
                EmailService::sendNewReportNotification(
                    array_merge($fullReport, ['submitted_by' => $submitterName]),
                    $superAdmins
                );
            }
        } catch (Exception $notifEx) {
            Logger::error('Notification error (non-fatal)', ['error' => $notifEx->getMessage()]);
        }
        // ──────────────────────────────────────────────────────────────────

        Response::success('Report created successfully', ['report_id' => $reportId], Response::HTTP_CREATED);
        
    } catch (Exception $e) {
        Logger::error('Error creating report', ['error' => $e->getMessage()]);
        Response::error('Failed to create report', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Update report
 */
function updateReport($reportId, $data) {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT created_by FROM maintenance_reports WHERE report_id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
        
        if (!$report || $report['created_by'] != $userId) {
            Response::error('Unauthorized', [], Response::HTTP_FORBIDDEN);
            return;
        }
        
        $updates = [];
        $values = [];
        $allowedFields = ['title', 'description', 'location', 'priority', 'due_date', 'assigned_to', 'department_id'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            Response::error('No valid fields to update', [], Response::HTTP_BAD_REQUEST);
            return;
        }
        
        $values[] = $reportId;
        $sql = "UPDATE maintenance_reports SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE report_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        Logger::info('Report updated', ['report_id' => $reportId, 'user_id' => $userId]);
        
        Response::success('Report updated successfully');
        
    } catch (Exception $e) {
        Logger::error('Error updating report', ['error' => $e->getMessage()]);
        Response::error('Failed to update report', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Delete report
 */
function deleteReport($reportId) {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    try {
        // Verify ownership or super admin
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $stmt = $pdo->prepare("SELECT created_by FROM maintenance_reports WHERE report_id = ?");
            $stmt->execute([$reportId]);
            $report = $stmt->fetch();
            
            if (!$report || $report['created_by'] != $userId) {
                Response::error('Unauthorized', [], Response::HTTP_FORBIDDEN);
                return;
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM maintenance_reports WHERE report_id = ?");
        $stmt->execute([$reportId]);
        
        Logger::info('Report deleted', ['report_id' => $reportId, 'user_id' => $userId]);
        
        Response::success('Report deleted successfully');
        
    } catch (Exception $e) {
        Logger::error('Error deleting report', ['error' => $e->getMessage()]);
        Response::error('Failed to delete report', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Format report dates to readable format
 */
function formatReportDates($report) {
    if (!is_array($report)) {
        $report = (array)$report;
    }
    
    if (!empty($report['created_at'])) {
        try {
            $date = new DateTime($report['created_at']);
            $report['created_at_formatted'] = $date->format('M d, Y');
            $report['created_at_full'] = $date->format('M d, Y h:i A');
        } catch (Exception $e) {
            // Keep original value
        }
    }
    
    if (!empty($report['due_date'])) {
        try {
            $date = new DateTime($report['due_date']);
            $report['due_date_formatted'] = $date->format('M d, Y');
        } catch (Exception $e) {
            // Keep original value
        }
    }
    
    if (!empty($report['completed_date'])) {
        try {
            $date = new DateTime($report['completed_date']);
            $report['completed_date_formatted'] = $date->format('M d, Y h:i A');
        } catch (Exception $e) {
            // Keep original value
        }
    }
    
    if (!empty($report['updated_at'])) {
        try {
            $date = new DateTime($report['updated_at']);
            $report['updated_at_formatted'] = $date->format('M d, Y h:i A');
        } catch (Exception $e) {
            // Keep original value
        }
    }
    
    return $report;
}
