<?php
/**
 * Database Configuration
 * FIXED VERSION - Ready for localhost
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');  // Empty for XAMPP/Laragon default
define('DB_NAME', 'school_facility_maintenance');
define('DB_PORT', 3306);

// Create PDO connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    // Check if this is an API request
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $isApiRequest = strpos($requestUri, '/backend/api/') !== false;
    
    if ($isApiRequest) {
        // For API calls, return JSON error
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please check your configuration.'
        ]));
    } else {
        // For page requests, show user-friendly HTML error
        http_response_code(500);
        die('<!DOCTYPE html>
        ... [yung long HTML code]
    }
}
```
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Required - SFMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            max-width: 700px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .error-header {
            background: #dc3545;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .error-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .error-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .error-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        .error-body {
            padding: 40px;
        }
        .error-message {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .error-message strong {
            display: block;
            margin-bottom: 5px;
            color: #856404;
        }
        .setup-steps {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .setup-steps h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .setup-steps ol {
            margin-left: 20px;
        }
        .setup-steps li {
            margin: 15px 0;
            line-height: 1.6;
            color: #555;
        }
        .setup-steps code {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 3px;
            font-family: "Courier New", monospace;
            color: #d63384;
            font-size: 14px;
        }
        .setup-steps a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .setup-steps a:hover {
            text-decoration: underline;
        }
        .btn-container {
            text-align: center;
            padding-top: 10px;
        }
        .btn-retry {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 14px 40px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        .btn-retry:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,123,255,0.4);
        }
        .tech-details {
            margin-top: 25px;
            padding: 15px;
            background: #e7f3ff;
            border-radius: 6px;
            font-size: 13px;
            color: #666;
        }
        .tech-details strong {
            color: #333;
        }
        @media (max-width: 600px) {
            .error-header h1 { font-size: 22px; }
            .error-body { padding: 25px; }
            .setup-steps { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">⚠️</div>
            <h1>Database Setup Required</h1>
            <p>The School Facility Maintenance System needs to be configured</p>
        </div>
        
        <div class="error-body">
            <div class="error-message">
                <strong>⚡ Connection Error</strong>
                Cannot connect to database. The system is not set up yet.
            </div>
            
            <div class="setup-steps">
                <h2>🔧 Setup Instructions</h2>
                <ol>
                    <li>
                        <strong>Open XAMPP Control Panel</strong><br>
                        Make sure the <code>MySQL</code> service is running (green indicator)
                    </li>
                    <li>
                        <strong>Open phpMyAdmin</strong><br>
                        Go to <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>
                    </li>
                    <li>
                        <strong>Create Database</strong><br>
                        Click "New" → Database name: <code>school_facility_maintenance</code><br>
                        Collation: <code>utf8mb4_unicode_ci</code> → Click "Create"
                    </li>
                    <li>
                        <strong>Import Database Schema</strong><br>
                        Select the database → Click "Import" tab<br>
                        Choose file: <code>database/SINGLE_IMPORT.sql</code><br>
                        Click "Go" and wait for completion
                    </li>
                    <li>
                        <strong>Verify Import</strong><br>
                        You should see 7 tables created (users, reports, departments, etc.)
                    </li>
                    <li>
                        <strong>Refresh This Page</strong><br>
                        Click the "Try Again" button below
                    </li>
                </ol>
            </div>
            
            <div class="tech-details">
                <strong>Technical Details:</strong><br>
                • Database Host: <code>localhost:3306</code><br>
                • Database Name: <code>school_facility_maintenance</code><br>
                • Database User: <code>root</code> (XAMPP default)<br>
                • Database Password: <em>(empty for XAMPP)</em>
            </div>
            
            <div class="btn-container">
                <a href="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" class="btn-retry">
                    🔄 Try Again
                </a>
            </div>
        </div>
    </div>
</body>
</html>');
        }
    }
}

// Get global PDO instance
$pdo = getDBConnection();

return $pdo;
