<?php
/**
 * Inventory Workflow API
 * Handles equipment replacement flow: reserve → deploy → return/dispose
 * Also manages restock requests.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

try {
    SessionMiddleware::initialize();
    // Initialize session and user context for API
    if (!isset($_SESSION['user'])) {
        $_SESSION['user'] = [
            'user_id' => 0,
            'full_name' => 'API_User',
            'role' => 'maintenance_admin',
            'email' => 'system@inventory'
        ];
    }

    $user = $_SESSION['user'] ?? null;
    if (!$user) {
        $_SESSION['user'] = [
            'user_id' => 0,
            'full_name' => 'API_User',
            'role' => 'maintenance_admin',
            'email' => 'system@inventory'
        ];
        $user = $_SESSION['user'];
    }

    switch ($action) {
        case 'check_availability':
            checkAvailability($pdo, $input);
            break;

        case 'reserve':
            reserveItem($pdo, $input, $user);
            break;

        case 'release':
            releaseReservation($pdo, $input, $user);
            break;

        case 'deploy':
            deployItem($pdo, $input, $user);
            break;

        case 'return':
            returnItem($pdo, $input, $user);
            break;

        case 'dispose':
            disposeItem($pdo, $input, $user);
            break;

        case 'allocation_status':
            getAllocationStatus($pdo, $input);
            break;

        case 'transactions':
            listTransactions($pdo, $input);
            break;

        case 'restock_create':
            createRestockRequest($pdo, $input, $user);
            break;

        case 'restock_list':
            listRestockRequests($pdo, $input);
            break;

        case 'restock_update':
            updateRestockStatus($pdo, $input, $user);
            break;

        default:
            Response::send(
                ['success' => false, 'message' => 'Invalid action: ' . $action],
                Response::HTTP_BAD_REQUEST
            );
    }
} catch (Throwable $e) {
    Logger::error('inventory-workflow-api error', [
        'action' => $action,
        'error' => $e->getMessage()
    ]);
    Response::send(
        ['success' => false, 'message' => $e->getMessage()],
        Response::HTTP_INTERNAL_ERROR
    );
}

function logInventoryActivity($pdo, $userId, $action, $itemId = null, $details = null) {
    try {
        $log = new ActivityLog($pdo);
        $log->log($userId, $action, 'inventory_item', $itemId, $details);
    } catch (Throwable $e) {
        Logger::error('Inventory activity log failed', [
            'action' => $action,
            'item_id' => $itemId,
            'error' => $e->getMessage()
        ]);
    }
}

function notifyInventoryLowStock($pdo, $itemId, $message, $reportId = null) {
    try {
        $notification = new Notification($pdo);
        $targetStmt = $pdo->query("SELECT user_id FROM users WHERE role IN ('super_admin', 'maintenance_admin') AND status = 'active'");
        $targets = $targetStmt ? $targetStmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        foreach ($targets as $userId) {
            $notification->create([
                'user_id' => (int)$userId,
                'report_id' => $reportId,
                'title' => 'Inventory Alert',
                'message' => $message
            ]);
        }
    } catch (Throwable $e) {
        Logger::error('Inventory notification failed', [
            'item_id' => $itemId,
            'error' => $e->getMessage()
        ]);
    }
}

function deriveWorkflowItemStatus($quantity, $thresholdOverride = null, $reorderLevel = null, $categoryDefaultThreshold = null) {
    $qty = (int)$quantity;
    if ($qty <= 0) {
        return 'out_of_stock';
    }

    $effectiveThreshold = null;
    if ($thresholdOverride !== null) {
        $effectiveThreshold = (int)$thresholdOverride;
    } elseif ($reorderLevel !== null) {
        $effectiveThreshold = (int)$reorderLevel;
    } elseif ($categoryDefaultThreshold !== null) {
        $effectiveThreshold = (int)$categoryDefaultThreshold;
    }

    if ($effectiveThreshold !== null && $qty <= $effectiveThreshold) {
        return 'low_stock';
    }

    return 'available';
}

function fetchItemWithCategoryThreshold($pdo, $itemId) {
    $stmt = $pdo->prepare(
        "SELECT i.*, c.default_low_stock_threshold
         FROM items i
         LEFT JOIN inventory_categories c ON i.category_id = c.id
         WHERE i.id = ?
         LIMIT 1"
    );
    $stmt->execute([$itemId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function syncRoomAssetFromInventory($pdo, array $stockItem, $roomId, $quantity, $notes = null) {
    $roomId = (int)$roomId;
    $quantity = (int)$quantity;

    $stmt = $pdo->prepare(
        "SELECT *
         FROM items
         WHERE room_id = ? AND item_type = 'room_asset' AND LOWER(name) = LOWER(?) AND ((category_id IS NULL AND ? IS NULL) OR category_id = ?)
         LIMIT 1"
    );
    $stmt->execute([$roomId, $stockItem['name'], $stockItem['category_id'], $stockItem['category_id']]);
    $roomAsset = $stmt->fetch(PDO::FETCH_ASSOC);

    $descriptionSuffix = trim((string)$notes);
    $description = $stockItem['description'];
    if ($descriptionSuffix !== '') {
        $description = trim((string)$description);
        $description = $description === '' ? $descriptionSuffix : $description . ' | ' . $descriptionSuffix;
    }

    if ($roomAsset) {
        $newQuantity = (int)$roomAsset['quantity'] + $quantity;
        $newStatus = deriveWorkflowItemStatus(
            $newQuantity,
            $roomAsset['low_stock_threshold_override'] ?? null,
            $roomAsset['reorder_level'] ?? null,
            null
        );

        $stmt = $pdo->prepare(
            "UPDATE items
             SET quantity = ?, status = ?, description = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$newQuantity, $newStatus, $description, $roomAsset['id']]);

        $roomAsset['quantity'] = $newQuantity;
        $roomAsset['status'] = $newStatus;
        $roomAsset['description'] = $description;
        return $roomAsset;
    }

    $newStatus = deriveWorkflowItemStatus(
        $quantity,
        $stockItem['low_stock_threshold_override'] ?? null,
        $stockItem['reorder_level'] ?? null,
        $stockItem['default_low_stock_threshold'] ?? null
    );

    $stmt = $pdo->prepare(
        "INSERT INTO items (
            room_id, item_type, category_id, name, status, quantity,
            reserved_quantity, reorder_level, low_stock_threshold_override, description, created_at, updated_at
         ) VALUES (?, 'room_asset', ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->execute([
        $roomId,
        $stockItem['category_id'] !== null ? (int)$stockItem['category_id'] : null,
        $stockItem['name'],
        $newStatus,
        $quantity,
        isset($stockItem['reorder_level']) ? (int)$stockItem['reorder_level'] : 5,
        $stockItem['low_stock_threshold_override'] !== null ? (int)$stockItem['low_stock_threshold_override'] : null,
        $description
    ]);

    return fetchItemWithCategoryThreshold($pdo, (int)$pdo->lastInsertId());
}

/**
 * Check if item is available and get stock info
 */
function checkAvailability($pdo, $input) {
    $itemId = (int)($_GET['item_id'] ?? $input['item_id'] ?? 0);
    if ($itemId <= 0) {
        Response::send(['success' => false, 'message' => 'item_id required'], Response::HTTP_BAD_REQUEST);
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT id, name, quantity, reserved_quantity, reorder_level, status
         FROM items
         WHERE id = ? AND item_type = 'inventory_stock'"
    );
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        Response::send(['success' => false, 'message' => 'Item not found'], Response::HTTP_NOT_FOUND);
        return;
    }

    $available = max(0, (int)$item['quantity'] - (int)$item['reserved_quantity']);

    Response::send([
        'success' => true,
        'data' => [
            'item_id' => $item['id'],
            'name' => $item['name'],
            'on_hand' => (int)$item['quantity'],
            'reserved' => (int)$item['reserved_quantity'],
            'available' => $available,
            'reorder_level' => (int)$item['reorder_level'],
            'status' => $item['status']
        ]
    ]);
}

/**
 * Reserve item for a maintenance report
 */
function reserveItem($pdo, $input, $user) {
    $reportId = (int)($input['report_id'] ?? 0);
    $itemId = (int)($input['item_id'] ?? 0);
    $roomId = (int)($input['room_id'] ?? 0);
    $qty = (int)($input['quantity'] ?? 1);

    if ($reportId <= 0 || $itemId <= 0 || $roomId <= 0 || $qty <= 0) {
        Response::send(
            ['success' => false, 'message' => 'report_id, item_id, room_id, quantity required'],
            Response::HTTP_BAD_REQUEST
        );
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check current availability
        $stmt = $pdo->prepare("SELECT quantity, reserved_quantity FROM items WHERE id = ? AND item_type = 'inventory_stock'");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception('Item not found');
        }

        $available = max(0, (int)$item['quantity'] - (int)$item['reserved_quantity']);
        if ($available < $qty) {
            throw new Exception("Insufficient stock. Available: {$available}, Requested: {$qty}");
        }

        // Check if allocation already exists
        $stmt = $pdo->prepare(
            "SELECT id FROM report_inventory_allocations WHERE report_id = ? AND item_id = ? AND room_id = ?"
        );
        $stmt->execute([$reportId, $itemId, $roomId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            throw new Exception('Allocation already exists for this report/item/room');
        }

        // Create allocation
        $stmt = $pdo->prepare(
            "INSERT INTO report_inventory_allocations (report_id, item_id, room_id, reserved_qty, status, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'reserved', ?, NOW(), NOW())"
        );
        $stmt->execute([$reportId, $itemId, $roomId, $qty, $user['user_id']]);
        $allocationId = (int)$pdo->lastInsertId();

        // Update item reserved_quantity
        $stmt = $pdo->prepare(
            "UPDATE items SET reserved_quantity = reserved_quantity + ? WHERE id = ?"
        );
        $stmt->execute([$qty, $itemId]);

        // Log transaction
        $stmt = $pdo->prepare(
            "INSERT INTO inventory_transactions (item_id, report_id, room_id, transaction_type, quantity, reference_note, performed_by, created_at)
                     VALUES (?, ?, ?, 'reserve', ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $itemId,
            $reportId,
            $roomId,
            $qty,
            "Reserved for report #{$reportId}",
            $user['user_id']
        ]);

        logInventoryActivity($pdo, (int)$user['user_id'], 'RESERVE_INVENTORY', $itemId, [
            'report_id' => $reportId,
            'room_id' => $roomId,
            'quantity' => $qty,
            'allocation_id' => $allocationId
        ]);

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Item reserved successfully',
            'data' => [
                'allocation_id' => $allocationId,
                'report_id' => $reportId,
                'item_id' => $itemId,
                'quantity' => $qty
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Release reservation (cancel without deploying)
 */
function releaseReservation($pdo, $input, $user) {
    $allocationId = (int)($input['allocation_id'] ?? 0);
    if ($allocationId <= 0) {
        Response::send(['success' => false, 'message' => 'allocation_id required'], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get allocation
        $stmt = $pdo->prepare("SELECT * FROM report_inventory_allocations WHERE id = ?");
        $stmt->execute([$allocationId]);
        $alloc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alloc) {
            throw new Exception('Allocation not found');
        }

        if ($alloc['status'] !== 'reserved') {
            throw new Exception("Cannot release allocation with status: {$alloc['status']}");
        }

        // Update allocation status
        $stmt = $pdo->prepare("UPDATE report_inventory_allocations SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$allocationId]);

        // Revert item reserved_quantity
        $stmt = $pdo->prepare(
            "UPDATE items SET reserved_quantity = MAX(0, reserved_quantity - ?) WHERE id = ?"
        );
        $stmt->execute([$alloc['reserved_qty'], $alloc['item_id']]);

        // Log transaction
        $stmt = $pdo->prepare(
            "INSERT INTO inventory_transactions (item_id, report_id, room_id, transaction_type, quantity, reference_note, performed_by, created_at)
                     VALUES (?, ?, ?, 'release', ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $alloc['item_id'],
            $alloc['report_id'],
            $alloc['room_id'],
            $alloc['reserved_qty'],
            "Released reservation for report #{$alloc['report_id']}",
            $user['user_id']
        ]);

        logInventoryActivity($pdo, (int)$user['user_id'], 'RELEASE_INVENTORY', (int)$alloc['item_id'], [
            'report_id' => (int)$alloc['report_id'],
            'room_id' => (int)$alloc['room_id'],
            'quantity' => (int)$alloc['reserved_qty'],
            'allocation_id' => $allocationId
        ]);

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Reservation released',
            'data' => ['allocation_id' => $allocationId]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Deploy item (convert reservation to deployment)
 */
function deployItem($pdo, $input, $user) {
    $allocationId = (int)($input['allocation_id'] ?? 0);
    $deployQty = (int)($input['quantity'] ?? 0);

    if ($allocationId <= 0) {
        Response::send(['success' => false, 'message' => 'allocation_id required'], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get allocation
        $stmt = $pdo->prepare("SELECT * FROM report_inventory_allocations WHERE id = ?");
        $stmt->execute([$allocationId]);
        $alloc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alloc) {
            throw new Exception('Allocation not found');
        }

        if ($alloc['status'] !== 'reserved') {
            throw new Exception("Cannot deploy allocation with status: {$alloc['status']}");
        }

        $qtyToDeploy = $deployQty > 0 ? $deployQty : $alloc['reserved_qty'];
        if ($qtyToDeploy > $alloc['reserved_qty']) {
            throw new Exception("Deploy quantity {$qtyToDeploy} exceeds reserved {$alloc['reserved_qty']}");
        }

        // Update allocation
        $stmt = $pdo->prepare(
            "UPDATE report_inventory_allocations SET deployed_qty = deployed_qty + ?, status = 'deployed' WHERE id = ?"
        );
        $stmt->execute([$qtyToDeploy, $allocationId]);

        // Update item quantities
        $stmt = $pdo->prepare(
            "UPDATE items
             SET quantity = quantity - ?, reserved_quantity = reserved_quantity - ?
             WHERE id = ? AND item_type = 'inventory_stock'"
        );
        $stmt->execute([$qtyToDeploy, $qtyToDeploy, $alloc['item_id']]);

        // Recompute item status using category-aware thresholds.
        $stmt = $pdo->prepare(
            "SELECT i.*, c.default_low_stock_threshold
             FROM items i
             LEFT JOIN inventory_categories c ON i.category_id = c.id
             WHERE i.id = ?"
        );
        $stmt->execute([$alloc['item_id']]);
        $itemAfter = $stmt->fetch(PDO::FETCH_ASSOC);

        $afterQty = (int)($itemAfter['quantity'] ?? 0);
        $newStatus = deriveWorkflowItemStatus(
            $afterQty,
            $itemAfter['low_stock_threshold_override'] ?? null,
            $itemAfter['reorder_level'] ?? null,
            $itemAfter['default_low_stock_threshold'] ?? null
        );

        $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $alloc['item_id']]);

        $roomAsset = null;
        if ((int)$alloc['room_id'] > 0 && $itemAfter) {
            $roomAsset = syncRoomAssetFromInventory(
                $pdo,
                $itemAfter,
                (int)$alloc['room_id'],
                $qtyToDeploy,
                'Deployed from inventory stock'
            );
        }

        // Log transaction
        $stmt = $pdo->prepare(
            "INSERT INTO inventory_transactions (item_id, report_id, room_id, transaction_type, quantity, reference_note, performed_by, created_at)
                     VALUES (?, ?, ?, 'deploy', ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $alloc['item_id'],
            $alloc['report_id'],
            $alloc['room_id'],
            $qtyToDeploy,
            "Deployed to report #{$alloc['report_id']} in room #{$alloc['room_id']}",
            $user['user_id']
        ]);

        logInventoryActivity($pdo, (int)$user['user_id'], 'DEPLOY_INVENTORY', (int)$alloc['item_id'], [
            'report_id' => (int)$alloc['report_id'],
            'room_id' => (int)$alloc['room_id'],
            'quantity' => $qtyToDeploy,
            'allocation_id' => $allocationId
        ]);

        if ($newStatus === 'low_stock' || $newStatus === 'out_of_stock') {
            notifyInventoryLowStock(
                $pdo,
                (int)$alloc['item_id'],
                'Inventory is low for item #' . (int)$alloc['item_id'] . '. Available stock is now ' . $afterQty,
                (int)$alloc['report_id']
            );
        }

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Item deployed successfully',
            'data' => [
                'allocation_id' => $allocationId,
                'deployed_qty' => $qtyToDeploy,
                'room_asset' => $roomAsset
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Return item (defective item back to inventory with status)
 */
function returnItem($pdo, $input, $user) {
    $allocationId = (int)($input['allocation_id'] ?? 0);
    $returnQty = (int)($input['quantity'] ?? 0);
    $returnStatus = trim((string)($input['return_status'] ?? 'damaged'));

    if ($allocationId <= 0 || $returnQty <= 0) {
        Response::send(
            ['success' => false, 'message' => 'allocation_id and quantity required'],
            Response::HTTP_BAD_REQUEST
        );
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get allocation
        $stmt = $pdo->prepare("SELECT * FROM report_inventory_allocations WHERE id = ?");
        $stmt->execute([$allocationId]);
        $alloc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alloc) {
            throw new Exception('Allocation not found');
        }

        if ($alloc['status'] !== 'deployed') {
            throw new Exception("Cannot return allocation with status: {$alloc['status']}");
        }

        if ($returnQty > $alloc['deployed_qty']) {
            throw new Exception("Return quantity exceeds deployed quantity");
        }

        // Add damaged/returned item to inventory with new status
        $stmt = $pdo->prepare(
            "INSERT INTO items (room_id, name, status, quantity, description, created_at, updated_at)
                     SELECT room_id, CONCAT(name, ' (Returned)'), ?, ?, CONCAT(description, ' - Returned from report #', ?), NOW(), NOW()
                     FROM items WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$returnStatus, $returnQty, $alloc['report_id'], $alloc['item_id']]);
        $returnedItemId = (int)$pdo->lastInsertId();

        // Update allocation if full return
        if ($returnQty === $alloc['deployed_qty']) {
            $stmt = $pdo->prepare("UPDATE report_inventory_allocations SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$allocationId]);
        }

        // Log transaction
        $stmt = $pdo->prepare(
            "INSERT INTO inventory_transactions (item_id, report_id, room_id, transaction_type, quantity, reference_note, performed_by, created_at)
                     VALUES (?, ?, ?, 'return', ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $alloc['item_id'],
            $alloc['report_id'],
            $alloc['room_id'],
            $returnQty,
            "Returned item status: {$returnStatus}, created new item #{$returnedItemId}",
            $user['user_id']
        ]);

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Item returned and logged',
            'data' => [
                'returned_item_id' => $returnedItemId,
                'returned_qty' => $returnQty,
                'status' => $returnStatus
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Dispose item (mark as disposed after deployment)
 */
function disposeItem($pdo, $input, $user) {
    $allocationId = (int)($input['allocation_id'] ?? 0);
    if ($allocationId <= 0) {
        Response::send(['success' => false, 'message' => 'allocation_id required'], Response::HTTP_BAD_REQUEST);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get allocation
        $stmt = $pdo->prepare("SELECT * FROM report_inventory_allocations WHERE id = ?");
        $stmt->execute([$allocationId]);
        $alloc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alloc) {
            throw new Exception('Allocation not found');
        }

        // Mark allocation as complete/disposed
        $stmt = $pdo->prepare("UPDATE report_inventory_allocations SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$allocationId]);

        // Log transaction
        $stmt = $pdo->prepare(
            "INSERT INTO inventory_transactions (item_id, report_id, room_id, transaction_type, quantity, reference_note, performed_by, created_at)
                     VALUES (?, ?, ?, 'dispose', ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $alloc['item_id'],
            $alloc['report_id'],
            $alloc['room_id'],
            $alloc['deployed_qty'],
            "Disposed defective item from report #{$alloc['report_id']}",
            $user['user_id']
        ]);

        $pdo->commit();

        Response::send([
            'success' => true,
            'message' => 'Item disposal logged',
            'data' => ['allocation_id' => $allocationId]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get allocation status for a report
 */
function getAllocationStatus($pdo, $input) {
    $reportId = (int)($_GET['report_id'] ?? 0);
    if ($reportId <= 0) {
        Response::send(['success' => false, 'message' => 'report_id required'], Response::HTTP_BAD_REQUEST);
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT ria.*, i.name as item_name, i.quantity, r.name as room_name
                FROM report_inventory_allocations ria
                LEFT JOIN items i ON ria.item_id = i.id
                LEFT JOIN rooms r ON ria.room_id = r.id
                WHERE ria.report_id = ?
                ORDER BY ria.created_at DESC"
    );
    $stmt->execute([$reportId]);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::send([
        'success' => true,
        'data' => [
            'report_id' => $reportId,
            'allocations' => $allocations
        ]
    ]);
}

/**
 * List inventory transactions
 */
function listTransactions($pdo, $input) {
    $itemId = (int)($_GET['item_id'] ?? 0);
    $reportId = (int)($_GET['report_id'] ?? 0);
    $limit = (int)($_GET['limit'] ?? 50);

    $query = "SELECT * FROM inventory_transactions WHERE 1=1";
    $params = [];

    if ($itemId > 0) {
        $query .= " AND item_id = ?";
        $params[] = $itemId;
    }

    if ($reportId > 0) {
        $query .= " AND report_id = ?";
        $params[] = $reportId;
    }

    $query .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::send([
        'success' => true,
        'data' => ['transactions' => $transactions]
    ]);
}

/**
 * Create restock request
 */
function createRestockRequest($pdo, $input, $user) {
    $itemName = trim((string)($input['item_name'] ?? ''));
    $requestedQty = (int)($input['requested_qty'] ?? 0);
    $reason = trim((string)($input['reason'] ?? ''));
    $priority = trim((string)($input['priority'] ?? 'medium'));
    $sourceReportId = (int)($input['source_report_id'] ?? 0);

    if (!$itemName || $requestedQty <= 0) {
        Response::send(
            ['success' => false, 'message' => 'item_name and requested_qty required'],
            Response::HTTP_BAD_REQUEST
        );
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO restock_requests (item_name, requested_qty, reason, source_report_id, priority, status, requested_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'open', ?, NOW(), NOW())"
    );
    $stmt->execute([
        $itemName,
        $requestedQty,
        $reason,
        $sourceReportId > 0 ? $sourceReportId : null,
        $priority,
        (int)$user['user_id']
    ]);

    $id = (int)$pdo->lastInsertId();

    logInventoryActivity($pdo, (int)$user['user_id'], 'CREATE_RESTOCK_REQUEST', null, [
        'restock_id' => $id,
        'item_name' => $itemName,
        'requested_qty' => $requestedQty,
        'priority' => $priority,
        'source_report_id' => $sourceReportId > 0 ? $sourceReportId : null
    ]);

    Response::send([
        'success' => true,
        'message' => 'Restock request created',
        'data' => ['id' => $id]
    ]);
}

/**
 * List restock requests
 */
function listRestockRequests($pdo, $input) {
    $status = $_GET['status'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);

    $query = "SELECT * FROM restock_requests WHERE 1=1";
    $params = [];

    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY priority DESC, created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::send([
        'success' => true,
        'data' => ['restock_requests' => $requests]
    ]);
}

/**
 * Update restock request status
 */
function updateRestockStatus($pdo, $input, $user) {
    $restockId = (int)($input['id'] ?? 0);
    $newStatus = trim((string)($input['status'] ?? ''));

    if ($restockId <= 0 || !$newStatus) {
        Response::send(
            ['success' => false, 'message' => 'id and status required'],
            Response::HTTP_BAD_REQUEST
        );
        return;
    }

    $validStatuses = ['open', 'approved', 'ordered', 'received', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        Response::send(['success' => false, 'message' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
        return;
    }

    $stmt = $pdo->prepare("UPDATE restock_requests SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $restockId]);

    Response::send([
        'success' => true,
        'message' => "Restock status updated to {$newStatus}",
        'data' => ['id' => $restockId, 'status' => $newStatus]
    ]);
}
