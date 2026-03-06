<?php
// Super Admin Dashboard API
// Provides system-wide statistics and overview data

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../backend/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../backend/middleware/RoleMiddleware.php';

// Check authentication
AuthMiddleware::check();

// Check if user is super_admin
RoleMiddleware::check(['super_admin']);

header('Content-Type: application/json');

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

try {
    switch ($action) {
        case 'getDashboardStats':
            echo json_encode(getDashboardStats());
            break;

        case 'getChartData':
            echo json_encode(getChartData());
            break;

        case 'getSystemOverview':
            echo json_encode(getSystemOverview());
            break;

        case 'getRecentActivity':
            echo json_encode(getRecentActivity());
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getDashboardStats() {
    global $pdo;

    // Total Users
    $users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch();
    $totalUsers = $users['count'];

    // Users by role
    $roleStats = $pdo->query("
        SELECT role, COUNT(*) as count 
        FROM users 
        WHERE status = 'active'
        GROUP BY role
    ")->fetchAll();
    
    $roleBreakdown = [];
    foreach ($roleStats as $stat) {
        $roleBreakdown[$stat['role']] = $stat['count'];
    }

    // Total Reports
    $reports = $pdo->query("SELECT COUNT(*) as count FROM maintenance_reports")->fetch();
    $totalReports = $reports['count'];

    // Reports by status
    $statusStats = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM maintenance_reports 
        GROUP BY status
    ")->fetchAll();
    
    $statusBreakdown = [];
    foreach ($statusStats as $stat) {
        $statusBreakdown[$stat['status']] = $stat['count'];
    }

    // Completed this month
    $completedThisMonth = $pdo->query("
        SELECT COUNT(*) as count 
        FROM maintenance_reports 
        WHERE MONTH(completed_date) = MONTH(CURDATE()) 
        AND YEAR(completed_date) = YEAR(CURDATE())
        AND status = 'completed'
    ")->fetch();

    // Total departments
    $departments = $pdo->query("SELECT COUNT(*) as count FROM departments")->fetch();
    $totalDepartments = $departments['count'];

    // Pending reports
    $pending = $pdo->query("
        SELECT COUNT(*) as count 
        FROM maintenance_reports 
        WHERE status IN ('submitted', 'assigned')
    ")->fetch();
    $pendingReports = $pending['count'];

    // Overdue reports
    $overdue = $pdo->query("
        SELECT COUNT(*) as count 
        FROM maintenance_reports 
        WHERE due_date < CURDATE() 
        AND status NOT IN ('completed', 'cancelled')
    ")->fetch();
    $overdueReports = $overdue['count'];

    return [
        'success' => true,
        'totalUsers' => (int)$totalUsers,
        'totalReports' => (int)$totalReports,
        'totalDepartments' => (int)$totalDepartments,
        'completedThisMonth' => (int)$completedThisMonth['count'],
        'pendingReports' => (int)$pendingReports,
        'overdueReports' => (int)$overdueReports,
        'roleBreakdown' => $roleBreakdown,
        'statusBreakdown' => $statusBreakdown
    ];
}

function getChartData() {
    global $pdo;

    // 1. Reports by Status Chart
    $statusData = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM maintenance_reports 
        GROUP BY status
    ")->fetchAll();

    $statusLabels = [];
    $statusCounts = [];
    $statusColors = [
        'submitted' => '#3b82f6',
        'assigned' => '#f59e0b',
        'in_progress' => '#8b5cf6',
        'completed' => '#10b981',
        'cancelled' => '#ef4444'
    ];

    foreach ($statusData as $row) {
        $statusLabels[] = ucfirst($row['status']);
        $statusCounts[] = (int)$row['count'];
    }

    // 2. Reports by Priority Chart
    $priorityData = $pdo->query("
        SELECT priority, COUNT(*) as count 
        FROM maintenance_reports 
        GROUP BY priority
        ORDER BY FIELD(priority, 'urgent', 'high', 'medium', 'low')
    ")->fetchAll();

    $priorityLabels = [];
    $priorityCounts = [];
    $priorityColors = [
        'urgent' => '#ef4444',
        'high' => '#f97316',
        'medium' => '#f59e0b',
        'low' => '#10b981'
    ];

    foreach ($priorityData as $row) {
        $priorityLabels[] = ucfirst($row['priority']);
        $priorityCounts[] = (int)$row['count'];
    }

    // 3. Department Report Volume Chart
    $deptData = $pdo->query("
        SELECT d.name, COUNT(mr.id) as count 
        FROM departments d 
        LEFT JOIN maintenance_reports mr ON d.id = mr.department_id 
        GROUP BY d.id 
        ORDER BY count DESC 
        LIMIT 6
    ")->fetchAll();

    $deptLabels = [];
    $deptCounts = [];

    foreach ($deptData as $row) {
        $deptLabels[] = $row['name'];
        $deptCounts[] = (int)$row['count'];
    }

    // 4. Monthly trend (last 6 months)
    $trendData = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m-01', strtotime("-$i month"));
        $nextDate = date('Y-m-01', strtotime('-' . ($i - 1) . ' month'));
        
        $count = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM maintenance_reports 
            WHERE created_at >= ? AND created_at < ?
        ");
        $count->execute([$date, $nextDate]);
        $result = $count->fetch();
        
        $trendData[] = [
            'month' => date('M Y', strtotime($date)),
            'count' => (int)$result['count']
        ];
    }

    $trendLabels = array_map(function($item) { return $item['month']; }, $trendData);
    $trendCounts = array_map(function($item) { return $item['count']; }, $trendData);

    return [
        'success' => true,
        'statusChart' => [
            'labels' => $statusLabels,
            'data' => $statusCounts,
            'colors' => array_map(function($label) use ($statusColors) {
                $key = strtolower(str_replace(' ', '_', $label));
                return $statusColors[$key] ?? '#6b7280';
            }, $statusLabels)
        ],
        'priorityChart' => [
            'labels' => $priorityLabels,
            'data' => $priorityCounts,
            'colors' => array_map(function($label) use ($priorityColors) {
                $key = strtolower($label);
                return $priorityColors[$key] ?? '#6b7280';
            }, $priorityLabels)
        ],
        'departmentChart' => [
            'labels' => $deptLabels,
            'data' => $deptCounts
        ],
        'trendChart' => [
            'labels' => $trendLabels,
            'data' => $trendCounts
        ]
    ];
}

function getSystemOverview() {
    global $pdo;

    // Users by department
    $deptUsers = $pdo->query("
        SELECT d.name, COUNT(u.id) as count 
        FROM departments d 
        LEFT JOIN users u ON d.id = u.department_id AND u.status = 'active'
        GROUP BY d.id
    ")->fetchAll();

    // Most active maintenance staff
    $activeStaff = $pdo->query("
        SELECT u.full_name, COUNT(mr.id) as assigned_count
        FROM users u
        LEFT JOIN maintenance_reports mr ON u.id = mr.assigned_to
        WHERE u.role = 'maintenance_staff' AND u.status = 'active'
        GROUP BY u.id
        ORDER BY assigned_count DESC
        LIMIT 5
    ")->fetchAll();

    return [
        'success' => true,
        'departmentUsers' => $deptUsers,
        'activeStaff' => $activeStaff
    ];
}

function getRecentActivity() {
    global $pdo;

    $activity = $pdo->query("
        SELECT 
            al.id, 
            al.action, 
            al.entity_type, 
            al.entity_id, 
            al.details,
            al.created_at,
            u.full_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ")->fetchAll();

    return [
        'success' => true,
        'activities' => $activity
    ];
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
