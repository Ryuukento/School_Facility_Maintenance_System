<!DOCTYPE html>
<html>
<head>
    <title>SFMS Debug</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .ok { color: green; font-weight: bold; }
        .bad { color: red; font-weight: bold; }
        code { background: #f0f0f0; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>🔍 SFMS System Debug</h1>
    
    <div class="box">
        <h2>PHP Status</h2>
        <p><span class="ok">✅ PHP is executing</span></p>
        <p>Version: <?php echo PHP_VERSION; ?></p>
        <p>Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
    </div>

    <div class="box">
        <h2>File System</h2>
        <p><?php 
            $indexExists = file_exists(__DIR__ . '/index.php');
            echo $indexExists ? '<span class="ok">✅</span>' : '<span class="bad">❌</span>';
            echo " index.php exists\n";
        ?></p>
        <p><?php 
            $frontendExists = file_exists(__DIR__ . '/frontend/pages/index.php');
            echo $frontendExists ? '<span class="ok">✅</span>' : '<span class="bad">❌</span>';
            echo " frontend/pages/index.php exists\n";
        ?></p>
        <p><?php 
            $dashboardExists = file_exists(__DIR__ . '/frontend/pages/dashboard.php');
            echo $dashboardExists ? '<span class="ok">✅</span>' : '<span class="bad">❌</span>';
            echo " frontend/pages/dashboard.php exists\n";
        ?></p>
        <p><?php 
            $headerExists = file_exists(__DIR__ . '/frontend/includes/header.php');
            echo $headerExists ? '<span class="ok">✅</span>' : '<span class="bad">❌</span>';
            echo " frontend/includes/header.php exists\n";
        ?></p>
    </div>

    <div class="box">
        <h2>Database Connection</h2>
        <?php
        try {
            $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
            echo '<p><span class="ok">✅ MySQL server is reachable</span></p>';
            
            $databases = $pdo->query("SHOW DATABASES LIKE 'school_facility_maintenance'")->fetchAll();
            if (count($databases) > 0) {
                echo '<p><span class="ok">✅ Database exists</span></p>';
                $pdo->exec("USE school_facility_maintenance");
                $tables = $pdo->query("SHOW TABLES")->fetchAll();
                echo '<p><span class="ok">✅ Found ' . count($tables) . ' tables</span></p>';
            } else {
                echo '<p><span class="bad">❌ Database does not exist</span></p>';
                echo '<p>⚠️ You need to import <code>database/SINGLE_IMPORT.sql</code></p>';
            }
        } catch (PDOException $e) {
            echo '<p><span class="bad">❌ MySQL Error: ' . $e->getMessage() . '</span></p>';
            echo '<p>Make sure XAMPP MySQL is running!</p>';
        }
        ?>
    </div>

    <div class="box">
        <h2>Session</h2>
        <p>Session Status: <?php echo session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '⚠️ Inactive'; ?></p>
        <p>Session ID: <?php echo session_id(); ?></p>
        <p>User in Session: <?php echo isset($_SESSION['user']) ? '✅ Yes' : '❌ No'; ?></p>
    </div>

    <div class="box">
        <h2>What To Do Next</h2>
        <ol>
            <li>If Database doesn't exist, <strong>import database/SINGLE_IMPORT.sql</strong> in phpMyAdmin</li>
            <li>Then go to: <a href="/School_Facility_Maintenance_System/">http://localhost/School_Facility_Maintenance_System/</a></li>
            <li>Login with: <code>admin@school.edu</code> / <code>admin123</code></li>
        </ol>
    </div>

    <hr>
    <p style="color: gray; font-size: 12px;">Debug page created: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>
