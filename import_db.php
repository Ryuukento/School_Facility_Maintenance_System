<?php
/**
 * Database Import Script
 */

try {
    // Try to connect to MySQL
    $conn = new mysqli('localhost', 'root', '', '');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Prevent accidental re-import: abort if DB already exists with tables
    $checkConn = new mysqli('localhost', 'root', '', '');
    if ($checkConn->connect_error) {
        die("Connection failed: " . $checkConn->connect_error);
    }
    $dbExists = $checkConn->select_db('school_facility_maintenance');
    if ($dbExists) {
        $res = $checkConn->query("SHOW TABLES FROM school_facility_maintenance");
        if ($res && $res->num_rows > 0) {
            die("Refusing to import: database 'school_facility_maintenance' already exists and contains tables. Remove existing DB or edit this script to force import.\n");
        }
    }

    // Read the SQL file
    $sqlFile = __DIR__ . '/database/SINGLE_IMPORT.sql';

    if (!file_exists($sqlFile)) {
        die("SQL file not found: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Use multi_query to execute multiple statements
    if ($conn->multi_query($sql)) {
        echo "Importing database...\n";
        
        // Consume all results from multi_query
        $queryCount = 0;
        do {
            $queryCount++;
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        
        echo "Database imported successfully!\n";
        echo "Queries executed: " . $queryCount . "\n";
        
        // Verify the import
        $checkDb = $conn->query("SHOW TABLES FROM school_facility_maintenance");
        if ($checkDb) {
            $tableCount = $checkDb->num_rows;
            echo "Tables created: " . $tableCount . "\n";
            
            if ($tableCount > 0) {
                echo "\nTables in database:\n";
                while ($row = $checkDb->fetch_row()) {
                    echo "- " . $row[0] . "\n";
                }
            }
        }
    } else {
        die("Error importing database: " . $conn->error);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    die("Exception: " . $e->getMessage());
}
