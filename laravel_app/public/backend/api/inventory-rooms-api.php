<?php
/**
 * Inventory Rooms API
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$input = readInventoryRoomInput();

try {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();

    switch ($action) {
        case 'list':
            requireInventoryRoomReadAccess();
            listInventoryRooms($pdo);
            break;

        case 'create':
            requireInventoryRoomWriteAccess();
            createInventoryRoom($pdo, $input);
            break;

        default:
            Response::send(['success' => false, 'message' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
    }
} catch (Throwable $e) {
    Logger::error('inventory-rooms-api error', [
        'action' => $action,
        'error' => $e->getMessage()
    ]);
    Response::send(['success' => false, 'message' => 'Internal server error'], Response::HTTP_INTERNAL_ERROR);
}

function readInventoryRoomInput() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        return $json;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    return [];
}

function normalizeInventoryRoomRole($role): string {
    $normalized = strtolower(trim((string)$role));
    if ($normalized === 'admin_maintenance') {
        return 'maintenance_admin';
    }
    return $normalized;
}

function requireInventoryRoomReadAccess(): void {
    $role = normalizeInventoryRoomRole($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['maintenance_staff', 'maintenance_admin', 'super_admin'], true)) {
        Response::send(['success' => false, 'message' => 'Forbidden: Access denied'], Response::HTTP_FORBIDDEN);
    }
}

function requireInventoryRoomWriteAccess(): void {
    $role = normalizeInventoryRoomRole($_SESSION['user']['role'] ?? '');
    if (!in_array($role, ['maintenance_admin', 'super_admin'], true)) {
        Response::send(['success' => false, 'message' => 'Forbidden: Access denied'], Response::HTTP_FORBIDDEN);
    }
}

function listInventoryRooms(PDO $pdo): void {
    $stmt = $pdo->query(
        "SELECT ir.id, ir.name, ir.code, ir.description, ir.is_active, ir.sort_order,
                COUNT(i.id) AS item_count,
                COALESCE(SUM(i.quantity), 0) AS total_quantity
         FROM inventory_rooms ir
         LEFT JOIN items i ON i.inventory_room_id = ir.id AND i.item_type = 'inventory_stock'
         WHERE ir.is_active = 1
         GROUP BY ir.id, ir.name, ir.code, ir.description, ir.is_active, ir.sort_order
         ORDER BY ir.sort_order ASC, ir.name ASC"
    );

    Response::send([
        'success' => true,
        'data' => [
            'rooms' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]
    ]);
}

function createInventoryRoom(PDO $pdo, array $input): void {
    $name = trim((string)($input['name'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $codeRaw = trim((string)($input['code'] ?? ''));
    $code = $codeRaw !== '' ? strtolower($codeRaw) : null;

    if ($name === '') {
        Response::send(['success' => false, 'message' => 'Inventory room name is required'], Response::HTTP_BAD_REQUEST);
    }

    $stmt = $pdo->prepare("SELECT id FROM inventory_rooms WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt->execute([$name]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        Response::send(['success' => false, 'message' => 'Inventory room already exists'], Response::HTTP_CONFLICT);
    }

    if ($code !== null) {
        $stmt = $pdo->prepare("SELECT id FROM inventory_rooms WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            Response::send(['success' => false, 'message' => 'Inventory room code already exists'], Response::HTTP_CONFLICT);
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO inventory_rooms (name, code, description, is_active, sort_order, created_at, updated_at)
         VALUES (?, ?, ?, 1, 0, NOW(), NOW())"
    );
    $stmt->execute([
        $name,
        $code,
        $description !== '' ? $description : null
    ]);

    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare(
        "SELECT id, name, code, description, is_active, sort_order
         FROM inventory_rooms
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->execute([$id]);

    Response::send([
        'success' => true,
        'message' => 'Inventory room created successfully',
        'data' => [
            'room' => $stmt->fetch(PDO::FETCH_ASSOC)
        ]
    ], Response::HTTP_CREATED);
}
