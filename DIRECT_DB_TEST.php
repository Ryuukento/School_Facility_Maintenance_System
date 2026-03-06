<?php
/**
 * Direct Database Query Test
 * Shows what's actually in the database
 */

header('Content-Type: text/html; charset=utf-8');

?><!DOCTYPE html>
<html>
<head>
    <title>Direct Database Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0d1117; color: #e6edf3; font-family: monospace; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #58a6ff; margin-bottom: 20px; }
        .section { background: #161b22; border: 1px solid #30363d; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
        h2 { color: #79c0ff; border-bottom: 1px solid #30363d; padding-bottom: 10px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0d1117; padding: 10px; text-align: left; border-bottom: 1px solid #30363d; color: #58a6ff; }
        td { padding: 10px; border-bottom: 1px solid #30363d; }
        tr:hover { background: rgba(255, 255, 255, 0.01); }
        .count { font-size: 24px; color: #3fb950; font-weight: bold; margin: 10px 0; }
        .error { color: #f85149; }
        pre { background: #0d1117; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #30363d; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗄️ Direct Database Test</h1>

<?php
try {
    require_once __DIR__ . '/backend/config/database.php';
    
    $pdo = getDBConnection();
    
    // ensure our new schema exists (buildings -> floors -> rooms -> items)
    $setupSql = <<<SQL
-- buildings
CREATE TABLE IF NOT EXISTS buildings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- floors
CREATE TABLE IF NOT EXISTS floors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    building_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_floor_per_building (building_id, name),
    INDEX idx_building (building_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- rooms
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    building_id INT NOT NULL,
    floor_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    capacity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    FOREIGN KEY (floor_id)   REFERENCES floors(id)     ON DELETE CASCADE,
    UNIQUE KEY unique_room_per_building (building_id, name),
    UNIQUE KEY unique_room_per_floor (floor_id, name),
    INDEX idx_building (building_id),
    INDEX idx_floor (floor_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- items
CREATE TABLE IF NOT EXISTS items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('available','damaged','low_stock','out_of_stock','maintenance') DEFAULT 'available',
    quantity INT DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room (room_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    
    // run the schema setup statements
    $pdo->exec($setupSql);

    echo "<div class='section'>
        <h2>✅ Database Connection</h2>
        <p>Connected successfully to <code>school_facility_maintenance</code></p>
    </div>";
    
    // Users
    echo "<div class='section'>
        <h2>👥 Users Table</h2>";
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch()['count'];
    echo "<div class='count'>$count users</div>";
    
    $result = $pdo->query("SELECT user_id, full_name, email, role FROM users LIMIT 10");
    $users = $result->fetchAll();
    
    if ($users) {
        echo "<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>";
        
        foreach ($users as $user) {
            echo "<tr>
                <td>" . $user['user_id'] . "</td>
                <td>" . htmlspecialchars($user['full_name']) . "</td>
                <td>" . htmlspecialchars($user['email']) . "</td>
                <td>" . htmlspecialchars($user['role']) . "</td>
            </tr>";
        }
        
        echo "</tbody></table>";
    }
    echo "</div>";
    
    // Reports
    echo "<div class='section'>
        <h2>📋 Maintenance Reports Table</h2>";
    $result = $pdo->query("SELECT COUNT(*) as count FROM maintenance_reports");
    $count = $result->fetch()['count'];
    echo "<div class='count'>$count reports</div>";
    
    $result = $pdo->query("
        SELECT 
            r.report_id, 
            r.title, 
            r.priority, 
            r.status, 
            r.created_by,
            u.full_name as creator_name,
            r.created_at
        FROM maintenance_reports r
        LEFT JOIN users u ON r.created_by = u.user_id
        LIMIT 10
    ");
    $reports = $result->fetchAll();
    
    if ($reports) {
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
                <td>#" . $report['report_id'] . "</td>
                <td>" . htmlspecialchars($report['title']) . "</td>
                <td>" . strtoupper($report['priority']) . "</td>
                <td>" . strtoupper(str_replace('_', ' ', $report['status'])) . "</td>
                <td>" . htmlspecialchars($report['creator_name'] ?? 'N/A') . "</td>
                <td>" . $report['created_at'] . "</td>
            </tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p style='color: #d29922;'>⚠️ No reports found in database!</p>";
    }
    echo "</div>";
    
    // Departments
    echo "<div class='section'>
        <h2>🏢 Departments Table</h2>";
    $result = $pdo->query("SELECT COUNT(*) as count FROM departments");
    $count = $result->fetch()['count'];
    echo "<div class='count'>$count departments</div>";
    
    $result = $pdo->query("SELECT department_id, name FROM departments LIMIT 10");
    $depts = $result->fetchAll();
    
    if ($depts) {
        echo "<table>
            <thead>
                <tr><th>ID</th><th>Name</th></tr>
            </thead>
            <tbody>";
        
        foreach ($depts as $dept) {
            echo "<tr>
                <td>" . $dept['department_id'] . "</td>
                <td>" . htmlspecialchars($dept['name']) . "</td>
            </tr>";
        }
        
        echo "</tbody></table>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section' style='border-color: #f85149;'>
        <h2 class='error'>❌ Database Error</h2>
        <p class='error'>" . $e->getMessage() . "</p>
        <p>Make sure:</p>
        <ul style='margin-left: 20px; margin-top: 10px;'>
            <li>MySQL is running (XAMPP Control Panel)</li>
            <li>Database 'school_facility_maintenance' exists</li>
            <li>Run FRESH_SETUP.php to import data</li>
        </ul>
    </div>";
}

?>
    </div>
</body>
</html>
