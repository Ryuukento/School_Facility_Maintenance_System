<?php
/**
 * Floors API
 * Handles floor CRUD operations
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

// Handle actions
switch ($action) {
    case 'list':
        getFloors();
        break;
    case 'getByBuilding':
        getFloorsByBuilding();
        break;
    case 'get':
        getFloor();
        break;
    case 'create':
        createFloor();
        break;
    case 'update':
        updateFloor();
        break;
    case 'delete':
        deleteFloor();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

function getFloors() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT f.id, f.name, f.building_id, b.name as building_name, f.created_at
                              FROM floors f
                              LEFT JOIN buildings b ON f.building_id = b.id
                              ORDER BY b.name, f.name");
        $stmt->execute();
        $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(true, 'Floors retrieved successfully', ['floors' => $floors]);
    } catch (PDOException $e) {
        error_log("Error getting floors: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve floors');
    }
}

function getFloorsByBuilding() {
    global $pdo;
    $buildingId = $_GET['building_id'] ?? null;
    if (!$buildingId) {
        sendResponse(false, 'Building ID is required');
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM floors WHERE building_id = ? ORDER BY name");
        $stmt->execute([$buildingId]);
        $floors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(true, 'Floors retrieved successfully', ['floors' => $floors]);
    } catch (PDOException $e) {
        error_log("Error getting floors by building: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve floors');
    }
}

function getFloor() {
    global $pdo;
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendResponse(false, 'Floor ID is required');
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT f.id, f.name, f.building_id, b.name as building_name, f.created_at
                              FROM floors f
                              LEFT JOIN buildings b ON f.building_id = b.id
                              WHERE f.id = ?");
        $stmt->execute([$id]);
        $floor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$floor) {
            sendResponse(false, 'Floor not found');
            return;
        }
        sendResponse(true, 'Floor retrieved successfully', ['floor' => $floor]);
    } catch (PDOException $e) {
        error_log("Error getting floor: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve floor');
    }
}

function createFloor() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $buildingId = $input['building_id'] ?? null;
    $name = $input['name'] ?? '';
    if (!$buildingId || !$name) {
        sendResponse(false, 'Building ID and floor name are required');
        return;
    }
    try {
        // verify building exists
        $check = $pdo->prepare("SELECT id FROM buildings WHERE id = ?");
        $check->execute([$buildingId]);
        if (!$check->fetch()) {
            sendResponse(false, 'Building not found');
            return;
        }
        // unique floor name per building
        $check = $pdo->prepare("SELECT id FROM floors WHERE building_id = ? AND name = ?");
        $check->execute([$buildingId, $name]);
        if ($check->fetch()) {
            sendResponse(false, 'Floor with this name already exists in this building');
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO floors (building_id, name, created_at, updated_at)
                              VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$buildingId, $name]);
        $floorId = $pdo->lastInsertId();
        sendResponse(true, 'Floor created successfully', ['id' => $floorId, 'name' => $name]);
    } catch (PDOException $e) {
        error_log("Error creating floor: " . $e->getMessage());
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function updateFloor() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $buildingId = $input['building_id'] ?? null;
    $name = $input['name'] ?? '';
    if (!$id || !$buildingId || !$name) {
        sendResponse(false, 'Floor ID, building ID and name are required');
        return;
    }
    try {
        $stmt = $pdo->prepare("UPDATE floors SET building_id = ?, name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$buildingId, $name, $id]);
        sendResponse(true, 'Floor updated successfully');
    } catch (PDOException $e) {
        error_log("Error updating floor: " . $e->getMessage());
        sendResponse(false, 'Failed to update floor');
    }
}

function deleteFloor() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    $id = $_POST['id'] ?? null;
    if (!$id) {
        sendResponse(false, 'Floor ID is required');
        return;
    }
    try {
        // check rooms exist
        $check = $pdo->prepare("SELECT COUNT(*) as count FROM rooms WHERE floor_id = ?");
        $check->execute([$id]);
        $count = $check->fetch(PDO::FETCH_ASSOC)['count'];
        if ($count > 0) {
            sendResponse(false, 'Cannot delete floor with existing rooms');
            return;
        }
        $stmt = $pdo->prepare("DELETE FROM floors WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(true, 'Floor deleted successfully');
    } catch (PDOException $e) {
        error_log("Error deleting floor: " . $e->getMessage());
        sendResponse(false, 'Failed to delete floor');
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
