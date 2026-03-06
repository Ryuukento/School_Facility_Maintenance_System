<?php
/**
 * Items API
 * Handles item/equipment CRUD operations within rooms
 */

require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Unauthorized. Please login first.');
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        getItems();
        break;
    case 'getByRoom':
        getItemsByRoom();
        break;
    case 'get':
        getItem();
        break;
    case 'create':
        createItem();
        break;
    case 'update':
        updateItem();
        break;
    case 'delete':
        deleteItem();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

function getItems() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT i.*, r.name as room_name FROM items i
                              LEFT JOIN rooms r ON i.room_id = r.id
                              ORDER BY i.name");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(true, 'Items retrieved successfully', ['items' => $items]);
    } catch (PDOException $e) {
        error_log("Error getting items: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve items');
    }
}

function getItemsByRoom() {
    global $pdo;
    $roomId = $_GET['room_id'] ?? null;
    if (!$roomId) {
        sendResponse(false, 'Room ID is required');
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE room_id = ? ORDER BY name");
        $stmt->execute([$roomId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(true, 'Items retrieved successfully', ['items' => $items]);
    } catch (PDOException $e) {
        error_log("Error getting items by room: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve items');
    }
}

function getItem() {
    global $pdo;
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendResponse(false, 'Item ID is required');
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            sendResponse(false, 'Item not found');
            return;
        }
        sendResponse(true, 'Item retrieved successfully', ['item' => $item]);
    } catch (PDOException $e) {
        error_log("Error getting item: " . $e->getMessage());
        sendResponse(false, 'Failed to retrieve item');
    }
}

function createItem() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $roomId = $input['room_id'] ?? null;
    $name = $input['name'] ?? '';
    $status = $input['status'] ?? 'available';
    $quantity = $input['quantity'] ?? 1;
    $description = $input['description'] ?? '';

    if (!$roomId || !$name) {
        sendResponse(false, 'Room ID and item name are required');
        return;
    }
    try {
        // verify room exists
        $check = $pdo->prepare("SELECT id FROM rooms WHERE id = ?");
        $check->execute([$roomId]);
        if (!$check->fetch()) {
            sendResponse(false, 'Room not found');
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO items (room_id, name, status, quantity, description, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$roomId, $name, $status, $quantity, $description]);
        $itemId = $pdo->lastInsertId();
        sendResponse(true, 'Item created successfully', ['id' => $itemId, 'name' => $name]);
    } catch (PDOException $e) {
        error_log("Error creating item: " . $e->getMessage());
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function updateItem() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $roomId = $input['room_id'] ?? null;
    $name = $input['name'] ?? '';
    $status = $input['status'] ?? null;
    $quantity = $input['quantity'] ?? null;
    $description = $input['description'] ?? null;
    if (!$id || !$roomId || !$name) {
        sendResponse(false, 'Item ID, room ID and name are required');
        return;
    }
    try {
        $stmt = $pdo->prepare("UPDATE items SET room_id = ?, name = ?, status = ?, quantity = ?, description = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$roomId, $name, $status, $quantity, $description, $id]);
        sendResponse(true, 'Item updated successfully');
    } catch (PDOException $e) {
        error_log("Error updating item: " . $e->getMessage());
        sendResponse(false, 'Failed to update item');
    }
}

function deleteItem() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'POST method required');
        return;
    }
    $id = $_POST['id'] ?? null;
    if (!$id) {
        sendResponse(false, 'Item ID is required');
        return;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$id]);
        sendResponse(true, 'Item deleted successfully');
    } catch (PDOException $e) {
        error_log("Error deleting item: " . $e->getMessage());
        sendResponse(false, 'Failed to delete item');
    }
}
