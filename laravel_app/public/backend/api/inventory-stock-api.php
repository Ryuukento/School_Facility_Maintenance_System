<?php
/**
 * Inventory Stock API
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$input = readInventoryStockInput();

try {
    SessionMiddleware::initialize();
    AuthMiddleware::protect();

    switch ($action) {
        case 'list_stock':
            requireInventoryStockReadAccess();
            listInventoryStock($pdo);
            break;

        case 'stock_summary':
            requireInventoryStockReadAccess();
            stockSummary($pdo);
            break;

        case 'get_transaction_history':
            requireInventoryStockReadAccess();
            getTransactionHistory($pdo);
            break;

        case 'add_stock':
            requireInventoryStockWriteAccess();
            addInventoryStock($pdo, $input);
            break;

        case 'update_stock':
            requireInventoryStockWriteAccess();
            updateInventoryStock($pdo, $input);
            break;

        case 'adjust_quantity':
            requireInventoryStockWriteAccess();
            adjustInventoryStockQuantity($pdo, $input);
            break;

        case 'deploy_to_room':
            requireInventoryStockWriteAccess();
            deployInventoryStockToRoom($pdo, $input);
            break;

        default:
            Response::send(['success' => false, 'message' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
    }
} catch (Throwable $e) {
    Logger::error('inventory-stock-api error', [
        'action' => $action,
        'error' => $e->getMessage()
    ]);
    Response::send(['success' => false, 'message' => 'Internal server error'], Response::HTTP_INTERNAL_ERROR);
}

function readInventoryStockInput() {
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

function currentInventoryStockUser(): array {
    return $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
}

function normalizeInventoryStockRole($role): string {
    $normalized = strtolower(trim((string)$role));

    if ($normalized === 'admin_maintenance') {
        return 'maintenance_admin';
    }

    if ($normalized === 'eelab_staff' || $normalized === 'maintenance_personnel') {
        return 'maintenance_staff';
    }

    return $normalized;
}

function requireInventoryStockReadAccess(): void {
    $user = currentInventoryStockUser();
    $role = normalizeInventoryStockRole($user['role'] ?? '');
    $allowed = ['maintenance_staff', 'maintenance_admin', 'super_admin'];

    if (!in_array($role, $allowed, true)) {
        Response::send(['success' => false, 'message' => 'Forbidden: Access denied'], Response::HTTP_FORBIDDEN);
    }
}

function requireInventoryStockWriteAccess(): void {
    $user = currentInventoryStockUser();
    $role = normalizeInventoryStockRole($user['role'] ?? '');
    $allowed = ['maintenance_admin', 'super_admin'];

    if (!in_array($role, $allowed, true)) {
        Response::send(['success' => false, 'message' => 'Forbidden: Access denied'], Response::HTTP_FORBIDDEN);
    }
}

function deriveInventoryStockStatus($quantity, $reorderLevel): string {
    $quantity = (int)$quantity;
    $reorderLevel = max(0, (int)$reorderLevel);

    if ($quantity <= 0) {
        return 'out_of_stock';
    }

    if ($quantity <= $reorderLevel) {
        return 'low_stock';
    }

    return 'available';
}

function normalizeInventoryThresholdInput($value, $fallback = 0): int {
    if ($value === null || $value === '') {
        return max(0, (int)$fallback);
    }

    return max(0, (int)$value);
}

function fetchInventoryStockItemById(PDO $pdo, int $itemId): ?array {
    $stmt = $pdo->prepare(
        "SELECT i.id, i.room_id, i.inventory_room_id, ir.name AS inventory_room_name, i.item_type, i.name, i.category_id, c.name AS category_name,
                i.status, i.quantity, i.reserved_quantity, i.reorder_level, i.description,
                i.low_stock_threshold_override, i.created_at, i.updated_at
         FROM items i
         LEFT JOIN inventory_categories c ON i.category_id = c.id
         LEFT JOIN inventory_rooms ir ON i.inventory_room_id = ir.id
         WHERE i.id = ? AND i.item_type = 'inventory_stock'
         LIMIT 1"
    );
    $stmt->execute([$itemId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    $row['available_quantity'] = max(0, (int)$row['quantity'] - (int)$row['reserved_quantity']);
    return $row;
}

function fetchRoomAssetById(PDO $pdo, int $itemId): ?array {
    $stmt = $pdo->prepare(
        "SELECT i.*, r.name AS room_name
         FROM items i
         LEFT JOIN rooms r ON i.room_id = r.id
         WHERE i.id = ? AND i.item_type = 'room_asset'
         LIMIT 1"
    );
    $stmt->execute([$itemId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function logInventoryStockTransaction(PDO $pdo, int $itemId, ?int $reportId, ?int $roomId, string $type, int $quantity, ?string $note, ?int $performedBy): void {
    $stmt = $pdo->prepare(
        "INSERT INTO inventory_transactions (item_id, report_id, room_id, transaction_type, quantity, reference_note, performed_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->execute([$itemId, $reportId, $roomId, $type, $quantity, $note, $performedBy]);
}

function createOrUpdateRoomAsset(PDO $pdo, array $stockItem, int $roomId, int $quantity, ?string $notes = null): array {
    $stmt = $pdo->prepare(
        "SELECT *
         FROM items
         WHERE room_id = ? AND item_type = 'room_asset' AND LOWER(name) = LOWER(?) AND ((category_id IS NULL AND ? IS NULL) OR category_id = ?)
         LIMIT 1"
    );
    $stmt->execute([$roomId, $stockItem['name'], $stockItem['category_id'], $stockItem['category_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $description = trim((string)($stockItem['description'] ?? ''));
    $notes = trim((string)$notes);
    if ($notes !== '') {
        $description = $description === '' ? $notes : $description . ' | ' . $notes;
    }

    if ($existing) {
        $newQuantity = (int)$existing['quantity'] + $quantity;
        $newStatus = deriveInventoryStockStatus($newQuantity, (int)($existing['reorder_level'] ?? 5));
        $stmt = $pdo->prepare(
            "UPDATE items
             SET quantity = ?, status = ?, description = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$newQuantity, $newStatus, $description, $existing['id']]);

        return fetchRoomAssetById($pdo, (int)$existing['id']);
    }

    $status = deriveInventoryStockStatus($quantity, (int)($stockItem['reorder_level'] ?? 5));
    $stmt = $pdo->prepare(
        "INSERT INTO items (
            room_id, item_type, name, status, quantity, reserved_quantity, reorder_level,
            description, category_id, low_stock_threshold_override, created_at, updated_at
         ) VALUES (?, 'room_asset', ?, ?, ?, 0, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->execute([
        $roomId,
        $stockItem['name'],
        $status,
        $quantity,
        (int)($stockItem['reorder_level'] ?? 5),
        $description !== '' ? $description : null,
        $stockItem['category_id'] !== null ? (int)$stockItem['category_id'] : null,
        $stockItem['low_stock_threshold_override'] !== null ? (int)$stockItem['low_stock_threshold_override'] : null
    ]);

    return fetchRoomAssetById($pdo, (int)$pdo->lastInsertId());
}

function syncDeploymentAllocation(PDO $pdo, int $itemId, int $roomId, int $quantity, ?int $performedBy, ?int $reportId): void {
    if (!$reportId) {
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT *
         FROM report_inventory_allocations
         WHERE report_id = ? AND item_id = ? AND room_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([$reportId, $itemId, $roomId]);
    $allocation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($allocation) {
        $newDeployedQty = (int)$allocation['deployed_qty'] + $quantity;
        $reservedQty = (int)$allocation['reserved_qty'];
        $newStatus = $newDeployedQty >= $reservedQty ? 'deployed' : 'reserved';

        $stmt = $pdo->prepare(
            "UPDATE report_inventory_allocations
             SET deployed_qty = ?, status = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$newDeployedQty, $newStatus, $allocation['id']]);
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO report_inventory_allocations (report_id, item_id, room_id, reserved_qty, deployed_qty, status, created_by, created_at, updated_at)
         VALUES (?, ?, ?, 0, ?, 'deployed', ?, NOW(), NOW())"
    );
    $stmt->execute([$reportId, $itemId, $roomId, $quantity, $performedBy]);
}

function listInventoryStock(PDO $pdo): void {
    $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
    $inventoryRoomId = isset($_GET['inventory_room_id']) && $_GET['inventory_room_id'] !== '' ? (int)$_GET['inventory_room_id'] : null;
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;
    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : null;

    $sql = "SELECT i.id, i.name, i.inventory_room_id, ir.name AS inventory_room_name, i.category_id, c.name AS category_name, i.status, i.quantity,
                   i.reserved_quantity, i.reorder_level, i.description,
                   GREATEST(i.quantity - i.reserved_quantity, 0) AS available_quantity
            FROM items i
            LEFT JOIN inventory_categories c ON i.category_id = c.id
            LEFT JOIN inventory_rooms ir ON i.inventory_room_id = ir.id
            WHERE i.item_type = 'inventory_stock'";
    $params = [];

    if ($inventoryRoomId !== null && $inventoryRoomId > 0) {
        $sql .= " AND i.inventory_room_id = ?";
        $params[] = $inventoryRoomId;
    }

    if ($categoryId !== null && $categoryId > 0) {
        $sql .= " AND i.category_id = ?";
        $params[] = $categoryId;
    }

    if ($status !== null && $status !== '') {
        $sql .= " AND i.status = ?";
        $params[] = $status;
    }

    if ($search !== null && $search !== '') {
        $sql .= " AND i.name LIKE ?";
        $params[] = '%' . $search . '%';
    }

    $sql .= " ORDER BY i.name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::send([
        'success' => true,
        'data' => [
            'items' => $items
        ]
    ]);
}

function addInventoryStock(PDO $pdo, array $input): void {
    $user = currentInventoryStockUser();
    $performedBy = isset($user['user_id']) ? (int)$user['user_id'] : null;
    $name = trim((string)($input['name'] ?? ''));
    $quantity = (int)($input['quantity'] ?? 0);
    $inventoryRoomId = isset($input['inventory_room_id']) && $input['inventory_room_id'] !== '' ? (int)$input['inventory_room_id'] : null;
    $categoryId = isset($input['category_id']) && $input['category_id'] !== '' ? (int)$input['category_id'] : null;
    $reorderLevel = isset($input['reorder_level']) ? normalizeInventoryThresholdInput($input['reorder_level'], 0) : 0;
    $description = trim((string)($input['description'] ?? ''));

    if ($name === '' || $quantity < 0 || $inventoryRoomId === null || $inventoryRoomId <= 0) {
        Response::send(['success' => false, 'message' => 'inventory_room_id, name and non-negative quantity are required'], Response::HTTP_BAD_REQUEST);
    }

    if ($reorderLevel < 0) {
        Response::send(['success' => false, 'message' => 'reorder_level must be zero or greater'], Response::HTTP_BAD_REQUEST);
    }

    try {
        $pdo->beginTransaction();

        if ($categoryId !== null) {
            $stmt = $pdo->prepare("SELECT id FROM inventory_categories WHERE id = ? LIMIT 1");
            $stmt->execute([$categoryId]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('Category not found');
            }
        }

        $stmt = $pdo->prepare("SELECT id FROM inventory_rooms WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$inventoryRoomId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Inventory room not found');
        }

        $stmt = $pdo->prepare(
            "SELECT *
             FROM items
             WHERE item_type = 'inventory_stock'
               AND inventory_room_id = ?
               AND LOWER(name) = LOWER(?)
               AND ((category_id IS NULL AND ? IS NULL) OR category_id = ?)
             LIMIT 1"
        );
        $stmt->execute([
            $inventoryRoomId,
            $name,
            $categoryId,
            $categoryId
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $itemId = (int)$existing['id'];
            $newQuantity = (int)$existing['quantity'] + $quantity;
            $effectiveReorderLevel = normalizeInventoryThresholdInput($input['reorder_level'] ?? null, $existing['reorder_level'] ?? 0);
            $status = deriveInventoryStockStatus($newQuantity, $effectiveReorderLevel);
            $nextDescription = trim((string)($existing['description'] ?? ''));
            if ($nextDescription === '' && $description !== '') {
                $nextDescription = $description;
            }

            $stmt = $pdo->prepare(
                "UPDATE items
                 SET quantity = ?, reorder_level = ?, status = ?, description = ?, updated_at = NOW()
                 WHERE id = ? AND item_type = 'inventory_stock'"
            );
            $stmt->execute([
                $newQuantity,
                $effectiveReorderLevel,
                $status,
                $nextDescription !== '' ? $nextDescription : null,
                $itemId
            ]);

            logInventoryStockTransaction($pdo, $itemId, null, null, 'adjustment', $quantity, 'Additional stock added to existing item', $performedBy);

            $pdo->commit();

            Response::send([
                'success' => true,
                'message' => 'Stock quantity updated successfully',
                'data' => [
                    'item' => fetchInventoryStockItemById($pdo, $itemId)
                ]
            ]);
        }

        $status = deriveInventoryStockStatus($quantity, $reorderLevel);

        $stmt = $pdo->prepare(
            "INSERT INTO items (
                room_id, inventory_room_id, item_type, name, status, quantity, reserved_quantity, reorder_level,
                description, category_id, created_at, updated_at
             ) VALUES (NULL, ?, 'inventory_stock', ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $inventoryRoomId,
            $name,
            $status,
            $quantity,
            $reorderLevel,
            $description !== '' ? $description : null,
            $categoryId
        ]);

        $itemId = (int)$pdo->lastInsertId();
        logInventoryStockTransaction($pdo, $itemId, null, null, 'adjustment', $quantity, 'Initial stock added', $performedBy);

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Stock item created successfully',
            'data' => [
                'item' => fetchInventoryStockItemById($pdo, $itemId)
            ]
        ], Response::HTTP_CREATED);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function updateInventoryStock(PDO $pdo, array $input): void {
    $user = currentInventoryStockUser();
    $performedBy = isset($user['user_id']) ? (int)$user['user_id'] : null;
    $itemId = (int)($input['id'] ?? 0);

    if ($itemId <= 0) {
        Response::send(['success' => false, 'message' => 'id is required'], Response::HTTP_BAD_REQUEST);
    }

    try {
        $pdo->beginTransaction();

        $existing = fetchInventoryStockItemById($pdo, $itemId);
        if (!$existing) {
            throw new RuntimeException('Inventory stock item not found');
        }

        $name = array_key_exists('name', $input) ? trim((string)$input['name']) : (string)$existing['name'];
        $quantity = array_key_exists('quantity', $input) ? (int)$input['quantity'] : (int)$existing['quantity'];
        $reorderLevel = array_key_exists('reorder_level', $input)
            ? normalizeInventoryThresholdInput($input['reorder_level'], $existing['reorder_level'] ?? 0)
            : (int)$existing['reorder_level'];
        $description = array_key_exists('description', $input)
            ? trim((string)$input['description'])
            : (string)($existing['description'] ?? '');
        $inventoryRoomId = array_key_exists('inventory_room_id', $input) && $input['inventory_room_id'] !== ''
            ? (int)$input['inventory_room_id']
            : ($existing['inventory_room_id'] !== null ? (int)$existing['inventory_room_id'] : null);
        $categoryId = array_key_exists('category_id', $input) && $input['category_id'] !== ''
            ? (int)$input['category_id']
            : ($existing['category_id'] !== null ? (int)$existing['category_id'] : null);

        if ($name === '' || $quantity < 0 || $reorderLevel < 0 || $inventoryRoomId === null || $inventoryRoomId <= 0) {
            throw new InvalidArgumentException('Invalid stock update payload');
        }

        if ($quantity < (int)$existing['reserved_quantity']) {
            throw new InvalidArgumentException('quantity cannot be lower than reserved_quantity');
        }

        if ($categoryId !== null) {
            $stmt = $pdo->prepare("SELECT id FROM inventory_categories WHERE id = ? LIMIT 1");
            $stmt->execute([$categoryId]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('Category not found');
            }
        }

        $stmt = $pdo->prepare("SELECT id FROM inventory_rooms WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$inventoryRoomId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Inventory room not found');
        }

        $status = deriveInventoryStockStatus($quantity, $reorderLevel);

        $stmt = $pdo->prepare(
            "UPDATE items
             SET name = ?, quantity = ?, reorder_level = ?, description = ?, inventory_room_id = ?, category_id = ?, status = ?, updated_at = NOW()
             WHERE id = ? AND item_type = 'inventory_stock'"
        );
        $stmt->execute([
            $name,
            $quantity,
            $reorderLevel,
            $description !== '' ? $description : null,
            $inventoryRoomId,
            $categoryId,
            $status,
            $itemId
        ]);

        $difference = $quantity - (int)$existing['quantity'];
        if ($difference !== 0) {
            $note = $difference > 0 ? 'Stock quantity increased via update_stock' : 'Stock quantity decreased via update_stock';
            logInventoryStockTransaction($pdo, $itemId, null, null, 'adjustment', $difference, $note, $performedBy);
        }

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Stock item updated successfully',
            'data' => [
                'item' => fetchInventoryStockItemById($pdo, $itemId)
            ]
        ]);
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        Response::send(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $status = $e->getMessage() === 'Inventory stock item not found' ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST;
        Response::send(['success' => false, 'message' => $e->getMessage()], $status);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function adjustInventoryStockQuantity(PDO $pdo, array $input): void {
    $user = currentInventoryStockUser();
    $performedBy = isset($user['user_id']) ? (int)$user['user_id'] : null;
    $itemId = (int)($input['item_id'] ?? 0);
    $quantityChange = (int)($input['quantity_change'] ?? 0);
    $reason = trim((string)($input['reason'] ?? ''));

    if ($itemId <= 0 || $quantityChange === 0 || $reason === '') {
        Response::send(['success' => false, 'message' => 'item_id, quantity_change, and reason are required'], Response::HTTP_BAD_REQUEST);
    }

    try {
        $pdo->beginTransaction();

        $existing = fetchInventoryStockItemById($pdo, $itemId);
        if (!$existing) {
            throw new RuntimeException('Inventory stock item not found');
        }

        $newQuantity = (int)$existing['quantity'] + $quantityChange;
        if ($newQuantity < 0) {
            throw new InvalidArgumentException('Adjustment would make quantity negative');
        }

        if ($newQuantity < (int)$existing['reserved_quantity']) {
            throw new InvalidArgumentException('Adjustment would reduce quantity below reserved stock');
        }

        $status = deriveInventoryStockStatus($newQuantity, (int)$existing['reorder_level']);

        $stmt = $pdo->prepare(
            "UPDATE items
             SET quantity = ?, status = ?, updated_at = NOW()
             WHERE id = ? AND item_type = 'inventory_stock'"
        );
        $stmt->execute([$newQuantity, $status, $itemId]);

        logInventoryStockTransaction($pdo, $itemId, null, null, 'adjustment', $quantityChange, $reason, $performedBy);

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Stock quantity adjusted successfully',
            'data' => [
                'item' => fetchInventoryStockItemById($pdo, $itemId)
            ]
        ]);
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        Response::send(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        Response::send(['success' => false, 'message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function deployInventoryStockToRoom(PDO $pdo, array $input): void {
    $user = currentInventoryStockUser();
    $performedBy = isset($user['user_id']) ? (int)$user['user_id'] : null;
    $itemId = (int)($input['item_id'] ?? 0);
    $roomId = (int)($input['room_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 0);
    $reportId = isset($input['report_id']) && $input['report_id'] !== '' ? (int)$input['report_id'] : null;
    $notes = trim((string)($input['notes'] ?? ''));

    if ($itemId <= 0 || $roomId <= 0 || $quantity <= 0) {
        Response::send(['success' => false, 'message' => 'item_id, room_id, and quantity are required'], Response::HTTP_BAD_REQUEST);
    }

    try {
        $pdo->beginTransaction();

        $stockItem = fetchInventoryStockItemById($pdo, $itemId);
        if (!$stockItem) {
            throw new RuntimeException('Inventory stock item not found');
        }

        $stmt = $pdo->prepare("SELECT id, name FROM rooms WHERE id = ? LIMIT 1");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$room) {
            throw new RuntimeException('Room not found');
        }

        if ((int)$stockItem['available_quantity'] < $quantity) {
            throw new InvalidArgumentException('Insufficient available stock');
        }

        $newQuantity = (int)$stockItem['quantity'] - $quantity;
        $newStatus = deriveInventoryStockStatus($newQuantity, (int)$stockItem['reorder_level']);

        $stmt = $pdo->prepare(
            "UPDATE items
             SET quantity = ?, status = ?, updated_at = NOW()
             WHERE id = ? AND item_type = 'inventory_stock'"
        );
        $stmt->execute([$newQuantity, $newStatus, $itemId]);

        $roomAsset = createOrUpdateRoomAsset($pdo, $stockItem, $roomId, $quantity, $notes);

        $referenceNote = $notes !== ''
            ? $notes
            : 'Deployed to room #' . $roomId;
        logInventoryStockTransaction($pdo, $itemId, $reportId, $roomId, 'deploy', $quantity, $referenceNote, $performedBy);

        syncDeploymentAllocation($pdo, $itemId, $roomId, $quantity, $performedBy, $reportId);

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Stock deployed successfully',
            'data' => [
                'stock_item' => fetchInventoryStockItemById($pdo, $itemId),
                'room_asset' => $roomAsset
            ]
        ]);
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        Response::send(['success' => false, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        Response::send(['success' => false, 'message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function getTransactionHistory(PDO $pdo): void {
    $itemId = isset($_GET['item_id']) && $_GET['item_id'] !== '' ? (int)$_GET['item_id'] : null;
    $roomId = isset($_GET['room_id']) && $_GET['room_id'] !== '' ? (int)$_GET['room_id'] : null;

    if (($itemId === null || $itemId <= 0) && ($roomId === null || $roomId <= 0)) {
        Response::send(['success' => false, 'message' => 'item_id or room_id is required'], Response::HTTP_BAD_REQUEST);
    }

    $sql = "SELECT it.*, u.full_name AS performed_by_name, i.name AS item_name, r.name AS room_name
            FROM inventory_transactions it
            LEFT JOIN users u ON it.performed_by = u.user_id
            LEFT JOIN items i ON it.item_id = i.id
            LEFT JOIN rooms r ON it.room_id = r.id
            WHERE 1=1";
    $params = [];

    if ($itemId !== null && $itemId > 0) {
        $sql .= " AND it.item_id = ?";
        $params[] = $itemId;
    }

    if ($roomId !== null && $roomId > 0) {
        $sql .= " AND it.room_id = ?";
        $params[] = $roomId;
    }

    $sql .= " ORDER BY it.created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::send([
        'success' => true,
        'data' => [
            'transactions' => $transactions
        ]
    ]);
}

function stockSummary(PDO $pdo): void {
    $summaryStmt = $pdo->query(
        "SELECT
            COUNT(*) AS total_stock_items,
            COALESCE(SUM(GREATEST(quantity - reserved_quantity, 0)), 0) AS total_units_available,
            SUM(CASE WHEN status = 'low_stock' THEN 1 ELSE 0 END) AS low_stock_count,
            SUM(CASE WHEN status = 'out_of_stock' THEN 1 ELSE 0 END) AS out_of_stock_count
         FROM items
         WHERE item_type = 'inventory_stock'"
    );
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_stock_items' => 0,
        'total_units_available' => 0,
        'low_stock_count' => 0,
        'out_of_stock_count' => 0,
    ];

    $recentDeploymentsStmt = $pdo->query(
        "SELECT it.item_id, i.name AS item_name, r.name AS room_name, it.quantity, it.created_at
         FROM inventory_transactions it
         LEFT JOIN items i ON it.item_id = i.id
         LEFT JOIN rooms r ON it.room_id = r.id
         WHERE it.transaction_type = 'deploy'
         ORDER BY it.created_at DESC
         LIMIT 5"
    );
    $recentDeployments = $recentDeploymentsStmt->fetchAll(PDO::FETCH_ASSOC);

    $restockStmt = $pdo->query(
        "SELECT COUNT(*) AS pending_restock_requests
         FROM restock_requests
         WHERE status IN ('open', 'approved')"
    );
    $pendingRestockRequests = (int)($restockStmt->fetch(PDO::FETCH_ASSOC)['pending_restock_requests'] ?? 0);

    Response::send([
        'success' => true,
        'data' => [
            'total_stock_items' => (int)$summary['total_stock_items'],
            'total_units_available' => (int)$summary['total_units_available'],
            'low_stock_count' => (int)$summary['low_stock_count'],
            'out_of_stock_count' => (int)$summary['out_of_stock_count'],
            'recent_deployments' => $recentDeployments,
            'pending_restock_requests' => $pendingRestockRequests
        ]
    ]);
}
