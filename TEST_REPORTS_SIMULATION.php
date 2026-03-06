<?php
/**
 * Simple Reports Test with Session Simulation
 */

// Start fresh session
session_start();
session_destroy();
session_start();

// Simulate login
require_once __DIR__ . '/backend/config/settings.php';
require_once __DIR__ . '/backend/config/database.php';

try {
    $pdo = getDBConnection();
    
    // Get the admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['admin@school.edu']);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<h1>❌ Admin user not found!</h1>";
        echo "<p>Please run FRESH_SETUP.php first to import sample data.</p>";
        exit;
    }
    
    // Set session
    $_SESSION['user'] = $user;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Reports Test Simulation</title>
    <style>
        body { font-family: monospace; background: #0d1117; color: #e6edf3; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1, h2 { color: #58a6ff; }
        .section { background: #161b22; border: 1px solid #30363d; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0d1117; padding: 10px; text-align: left; border-bottom: 1px solid #30363d; color: #58a6ff; }
        td { padding: 10px; border-bottom: 1px solid #30363d; }
        tr:hover { background: rgba(255, 255, 255, 0.01); }
        .success { color: #3fb950; }
        .error { color: #f85149; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✅ Reports Test (Simulated Login)</h1>
        
        <div class='section'>
            <h2>Session Info</h2>
            <p><strong>User ID:</strong> {$_SESSION['user_id']}</p>
            <p><strong>Role:</strong> {$_SESSION['role']}</p>
            <p><strong>Email:</strong> {$_SESSION['user']['email']}</p>
            <p><strong>Name:</strong> {$_SESSION['user']['full_name']}</p>
        </div>";
    
    // Test the same query that the API uses
    echo "<div class='section'>
        <h2>Database Query Test (Super Admin - ALL REPORTS)</h2>";
    
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
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong class='success'>Found: " . count($reports) . " reports</strong></p>";
    
    if (count($reports) > 0) {
        echo "<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>";
        
        foreach ($reports as $report) {
            echo "<tr>
                <td>#" . htmlspecialchars($report['report_id']) . "</td>
                <td>" . htmlspecialchars($report['title']) . "</td>
                <td>" . strtoupper($report['priority']) . "</td>
                <td>" . strtoupper(str_replace('_', ' ', $report['status'])) . "</td>
                <td>" . htmlspecialchars($report['creator_name'] ?? 'N/A') . "</td>
                <td>" . $report['created_at'] . "</td>
            </tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p class='error'>⚠️ NO REPORTS FOUND IN DATABASE!</p>";
    }
    
    echo "</div>";
    
    // Show what API would return
    echo "<div class='section'>
        <h2>API Response (JSON)</h2>
        <pre>";
    
    $apiResponse = [
        'success' => true,
        'message' => 'Reports retrieved successfully',
        'data' => [
            'reports' => $reports,
            'count' => count($reports)
        ]
    ];
    
    echo json_encode($apiResponse, JSON_PRETTY_PRINT);
    echo "</pre>
    </div>";
    
    echo "</div></body></html>";
    
} catch (Exception $e) {
    echo "<h1 class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h1>";
}
?>
