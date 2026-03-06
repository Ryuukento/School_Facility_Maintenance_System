<?php
/**
 * Buildings API
 * Handles building CRUD operations
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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'list':
        getBuildings();
        break;
    case 'get':
        getBuilding();
        break;
    case 'create':
        createBuilding();
        break;
    case 'update':
        updateBuilding();
        break;
    case 'delete':
        deleteBuilding();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Get list of buildings
 */
function getBuildings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.name, b.description, b.created_at,
                   COUNT(DISTINCT f.id) as floor_count,
                   COUNT(r.id) as room_count
            FROM buildings b
            LEFT JOIN floors f ON b.id = f.building_id
            LEFT JOIN rooms r ON b.id = r.building_id
            GROUP BY b.id, b.name, b.description, b.created_at
            ORDER BY b.created_at DESC
        ");
        $stmt->execute();
        $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Buildings retrieved successfully', ['buildings' => $buildings]);
    } catch (PDOException $e) {
        error_log("Error getting buildings: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve buildings');
    }
}

/**
 * Get single building
 */
function getBuilding() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendResponse(false, 'Building ID is required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.name, b.description, b.created_at,
                   COUNT(DISTINCT f.id) as floor_count,
                   COUNT(r.id) as room_count
            FROM buildings b
            LEFT JOIN floors f ON b.id = f.building_id
            LEFT JOIN rooms r ON b.id = r.building_id
            WHERE b.id = ?
            GROUP BY b.id, b.name, b.description, b.created_at
        ");
        $stmt->execute([$id]);
        $building = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$building) {
            sendResponse(false, 'Building not found');
            return;
        }
        
        sendResponse(true, 'Building retrieved successfully', ['building' => $building]);
    } catch (PDOException $e) {
        error_log("Error getting building: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve building');
    }
}

/**
 * Create new building
 */
function createBuilding() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    
    if (!$name) {
        sendResponse(false, 'Building name is required');
        return;
    }
    
    try {
        // First, check if the buildings table exists
        $checkTable = $pdo->prepare("SHOW TABLES LIKE 'buildings'");
        $checkTable->execute();
        
        if ($checkTable->rowCount() == 0) {
            sendResponse(false, 'Buildings table not found. Please run database setup at /backend/setup.html');
            return;
        }
        
        // Check if building name already exists
        $check = $pdo->prepare("SELECT id FROM buildings WHERE name = ?");
        $check->execute([$name]);
        
        if ($check->fetch()) {
            sendResponse(false, 'Building with this name already exists');
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO buildings (name, description, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$name, $description]);
        $buildingId = $pdo->lastInsertId();
        
        sendResponse(true, 'Building created successfully', ['id' => $buildingId, 'name' => $name]);
    } catch (PDOException $e) {
        error_log("Error creating building: " . $e->getMessage());
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

/**
 * Update building
 */
function updateBuilding() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? null;
    $name = $input['name'] ?? '';
    $description = $input['description'] ?? '';
    
    if (!$id || !$name) {
        sendResponse(false, 'Building ID and name are required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE buildings 
            SET name = ?, description = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$name, $description, $id]);
        
        sendResponse(true, 'Building updated successfully');
    } catch (PDOException $e) {
        error_log("Error updating building: " . $e->getMessage());
        sendResponse(false, 'Failed to update building');
    }
}

/**
 * Delete building
 */
function deleteBuilding() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        sendResponse(false, 'Building ID is required');
        return;
    }
    
    try {
        // Check if building has floors
        $check = $pdo->prepare("SELECT COUNT(*) as count FROM floors WHERE building_id = ?");
        $check->execute([$id]);
        $result = $check->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            sendResponse(false, 'Cannot delete building with existing floors');
            return;
        }
        // Check if building has rooms
        $check = $pdo->prepare("SELECT COUNT(*) as count FROM rooms WHERE building_id = ?");
        $check->execute([$id]);
        $result = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            sendResponse(false, 'Cannot delete building with existing rooms');
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM buildings WHERE id = ?");
        $stmt->execute([$id]);
        
        sendResponse(true, 'Building deleted successfully');
    } catch (PDOException $e) {
        error_log("Error deleting building: " . $e->getMessage());
        sendResponse(false, 'Failed to delete building');
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
    
    if ($data) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}
