<?php
/**
 * Maintenance Dashboard API
 * Provides statistics, charts, and dashboard data
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? null;

try {
    switch ($action) {
        case 'stats':
            getDashboardStats();
            break;
        
        case 'charts':
            getChartData();
            break;
        
        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('Dashboard API error', ['action' => $action, 'error' => $e->getMessage()]);
    Response::error('Internal server error', [], Response::HTTP_INTERNAL_ERROR);
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    // Get statistics based on user role
    $isAdmin = in_array($_SESSION['user']['role'], ['super_admin', 'maintenance_admin']);
    
    try {
        $sql = "SELECT 
                    COUNT(DISTINCT CASE WHEN created_by = ? THEN report_id END) as total_reports,
                    COUNT(DISTINCT CASE WHEN status = 'submitted' AND assigned_to = ? THEN report_id END) as pending,
                    COUNT(DISTINCT CASE WHEN status = 'in_progress' AND assigned_to = ? THEN report_id END) as in_progress,
                    COUNT(DISTINCT CASE WHEN status = 'completed' 
                        AND MONTH(completed_date) = MONTH(CURRENT_DATE)
                        AND YEAR(completed_date) = YEAR(CURRENT_DATE)
                        AND assigned_to = ? THEN report_id END) as completed_this_month,
                    COUNT(DISTINCT CASE WHEN due_date < CURRENT_DATE 
                        AND status NOT IN ('completed', 'closed') 
                        AND assigned_to = ? THEN report_id END) as overdue,
                    AVG(CASE WHEN status IN ('completed', 'closed') 
                        THEN TIMESTAMPDIFF(DAY, created_at, COALESCE(completed_date, updated_at)) END) as avg_completion_days
                FROM maintenance_reports
                WHERE 1=1";
        
        if (!$isAdmin) {
            $sql .= " AND (created_by = ? OR assigned_to = ?)";
        }
        
        $stmt = $pdo->prepare($sql);
        
        if ($isAdmin) {
            $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
        } else {
            $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
        }
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        Response::success('Dashboard stats retrieved', $stats);
        
    } catch (Exception $e) {
        Logger::error('Error fetching dashboard stats', ['error' => $e->getMessage()]);
        Response::error('Failed to fetch dashboard statistics', [], Response::HTTP_INTERNAL_ERROR);
    }
}

/**
 * Get chart data
 */
function getChartData() {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();
    
    $userId = $_SESSION['user_id'];
    global $pdo;
    
    $isAdmin = in_array($_SESSION['user']['role'], ['super_admin', 'maintenance_admin']);
    
    try {
        $chartData = [];
        
        // Status distribution
        $statusSql = "SELECT status, COUNT(*) as count 
                      FROM maintenance_reports 
                      WHERE created_by = ?" . (!$isAdmin ? " OR assigned_to = ?" : "") . "
                      GROUP BY status 
                      ORDER BY count DESC";
        
        $stmt = $pdo->prepare($statusSql);
        $isAdmin ? $stmt->execute([$userId]) : $stmt->execute([$userId, $userId]);
        $statusResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $statusLabels = [];
        $statusValues = [];
        foreach ($statusResults as $row) {
            $statusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
            $statusValues[] = (int)$row['count'];
        }
        
        $chartData['status_data'] = [
            'labels' => $statusLabels,
            'values' => $statusValues
        ];
        
        // Priority distribution
        $prioritySql = "SELECT priority, COUNT(*) as count 
                        FROM maintenance_reports 
                        WHERE created_by = ?" . (!$isAdmin ? " OR assigned_to = ?" : "") . "
                        GROUP BY priority 
                        ORDER BY FIELD(priority, 'low', 'medium', 'high', 'urgent', 'critical')";
        
        $stmt = $pdo->prepare($prioritySql);
        $isAdmin ? $stmt->execute([$userId]) : $stmt->execute([$userId, $userId]);
        $priorityResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $priorityLabels = [];
        $priorityValues = [];
        foreach ($priorityResults as $row) {
            $priorityLabels[] = ucfirst($row['priority']);
            $priorityValues[] = (int)$row['count'];
        }
        
        $chartData['priority_data'] = [
            'labels' => $priorityLabels,
            'values' => $priorityValues
        ];
        
        // Monthly trend (last 6 months)
        $trendSql = "SELECT 
                        DATE_FORMAT(created_at, '%b %y') as month,
                        COUNT(*) as created,
                        SUM(CASE WHEN status IN ('completed', 'closed') THEN 1 ELSE 0 END) as completed
                    FROM maintenance_reports
                    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                    AND (created_by = ?" . (!$isAdmin ? " OR assigned_to = ?" : "") . ")
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY created_at";
        
        $stmt = $pdo->prepare($trendSql);
        $isAdmin ? $stmt->execute([$userId]) : $stmt->execute([$userId, $userId]);
        $trendResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $trendLabels = [];
        $trendCreated = [];
        $trendCompleted = [];
        foreach ($trendResults as $row) {
            $trendLabels[] = $row['month'];
            $trendCreated[] = (int)$row['created'];
            $trendCompleted[] = (int)$row['completed'];
        }
        
        $chartData['trend_data'] = [
            'labels' => $trendLabels,
            'created' => $trendCreated,
            'completed' => $trendCompleted
        ];
        
        Response::success('Chart data retrieved', $chartData);
        
    } catch (Exception $e) {
        Logger::error('Error fetching chart data', ['error' => $e->getMessage()]);
        Response::error('Failed to fetch chart data', [], Response::HTTP_INTERNAL_ERROR);
    }
}
