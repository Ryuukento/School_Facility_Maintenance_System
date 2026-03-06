<?php
/**
 * Rooms API
 * Handles room CRUD operations
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
        getRooms();
        break;
    case 'getByBuilding':
        getRoomsByBuilding();
        break;
    case 'getByFloor':
        getRoomsByFloor();
        break;
    case 'get':
        getRoom();
        break;
    case 'create':
        createRoom();
        break;
    case 'update':
        updateRoom();
        break;
    case 'delete':
        deleteRoom();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Get list of rooms
 */
function getRooms() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.capacity, r.building_id, r.floor_id,
                   b.name as building_name, f.name as floor_name, r.created_at
            FROM rooms r
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN floors f ON r.floor_id = f.id
            ORDER BY b.name, f.name, r.name
        ");
        $stmt->execute();
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Rooms retrieved successfully', ['rooms' => $rooms]);
    } catch (PDOException $e) {
        error_log("Error getting rooms: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve rooms');
    }
}

/**
 * Get rooms by building
 */
function getRoomsByBuilding() {
    global $pdo;
    
    $buildingId = $_GET['building_id'] ?? null;
    
    if (!$buildingId) {
        sendResponse(false, 'Building ID is required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.capacity, r.floor_id,
                   f.name as floor_name
            FROM rooms r
            LEFT JOIN floors f ON r.floor_id = f.id
            WHERE r.building_id = ?
            ORDER BY r.name
        ");
        $stmt->execute([$buildingId]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Rooms retrieved successfully', ['rooms' => $rooms]);
    } catch (PDOException $e) {
        error_log("Error getting rooms: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve rooms');
    }
}

function getRoomsByFloor() {
    global $pdo;
    
    $floorId = $_GET['floor_id'] ?? null;
    
    if (!$floorId) {
        sendResponse(false, 'Floor ID is required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, capacity, building_id
            FROM rooms
            WHERE floor_id = ?
            ORDER BY name
        ");
        $stmt->execute([$floorId]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Rooms retrieved successfully', ['rooms' => $rooms]);
    } catch (PDOException $e) {
        error_log("Error getting rooms by floor: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve rooms');
    }
}

/**
 * Get single room
 */
function getRoom() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendResponse(false, 'Room ID is required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.capacity, r.building_id, r.floor_id,
                   b.name as building_name, f.name as floor_name, r.created_at
            FROM rooms r
            LEFT JOIN buildings b ON r.building_id = b.id
            LEFT JOIN floors f ON r.floor_id = f.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            sendResponse(false, 'Room not found');
            return;
        }
        
        sendResponse(true, 'Room retrieved successfully', ['room' => $room]);
    } catch (PDOException $e) {
        error_log("Error getting room: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve room');
    }
}

/**
 * Create new room
 */
function createRoom() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $buildingId = $input['building_id'] ?? null;
    $floorId = $input['floor_id'] ?? null;
    $name = $input['name'] ?? '';
    $capacity = $input['capacity'] ?? null;
    
    if (!$buildingId || !$floorId || !$name) {
        sendResponse(false, 'Building, floor and room name are required');
        return;
    }
    
    try {
        // Check if rooms table exists
        $checkTable = $pdo->prepare("SHOW TABLES LIKE 'rooms'");
        $checkTable->execute();
        
        if ($checkTable->rowCount() == 0) {
            sendResponse(false, 'Rooms table not found. Please run database setup at /backend/setup.html');
            return;
        }
        
        // Verify building exists
        $check = $pdo->prepare("SELECT id FROM buildings WHERE id = ?");
        $check->execute([$buildingId]);
        
        if (!$check->fetch()) {
            sendResponse(false, 'Building not found');
            return;
        }
        // Verify floor belongs to building
        $check = $pdo->prepare("SELECT id FROM floors WHERE id = ? AND building_id = ?");
        $check->execute([$floorId, $buildingId]);
        if (!$check->fetch()) {
            sendResponse(false, 'Floor not found or does not belong to building');
            return;
        }
        
        // Check if room name already exists on this floor
        $check = $pdo->prepare("SELECT id FROM rooms WHERE floor_id = ? AND name = ?");
        $check->execute([$floorId, $name]);
        
        if ($check->fetch()) {
            sendResponse(false, 'Room with this name already exists on this floor');
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO rooms (building_id, floor_id, name, capacity, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$buildingId, $floorId, $name, $capacity]);
        $roomId = $pdo->lastInsertId();
        
        sendResponse(true, 'Room created successfully', ['id' => $roomId, 'name' => $name]);
    } catch (PDOException $e) {
        error_log("Error creating room: " . $e->getMessage());
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

/**
 * Update room
 */
function updateRoom() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'] ?? null;
    $buildingId = $input['building_id'] ?? null;
    $floorId = $input['floor_id'] ?? null;
    $name = $input['name'] ?? '';
    $capacity = $input['capacity'] ?? null;
    
    if (!$id || !$buildingId || !$floorId || !$name) {
        sendResponse(false, 'Room ID, building ID, floor ID and name are required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE rooms 
            SET building_id = ?, floor_id = ?, name = ?, capacity = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$buildingId, $floorId, $name, $capacity, $id]);
        
        sendResponse(true, 'Room updated successfully');
    } catch (PDOException $e) {
        error_log("Error updating room: " . $e->getMessage());
        sendResponse(false, 'Failed to update room');
    }
}

/**
 * Delete room
 */
function deleteRoom() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if (!$id) {
        sendResponse(false, 'Room ID is required');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        
        sendResponse(true, 'Room deleted successfully');
    } catch (PDOException $e) {
        error_log("Error deleting room: " . $e->getMessage());
        sendResponse(false, 'Failed to delete room');
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
