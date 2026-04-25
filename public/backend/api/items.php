<?php
/**
 * Items API (MVC Router)
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$input = readInput();

try {
    $controller = new FacilityController($pdo);

        switch ($action) {
        case 'list':
            $itemType = $_GET['item_type'] ?? null;
            if ($itemType !== null) {
                $itemType = trim((string)$itemType);
                if (!in_array($itemType, ['room_asset', 'inventory_stock'], true)) {
                    Response::send(['success' => false, 'message' => 'Invalid item_type'], Response::HTTP_BAD_REQUEST);
                }
            }
            $controller->listItems($itemType);
            break;

        case 'getByRoom':
            $controller->listItemsByRoom($_GET['room_id'] ?? null);
            break;

        case 'get':
            $controller->getItem($_GET['id'] ?? null);
            break;

        case 'create':
            $controller->createItem($input);
            break;

        case 'update':
            $controller->updateItem($input);
            break;

        case 'delete':
            $controller->deleteItem($input['id'] ?? $_GET['id'] ?? null);
            break;

        default:
            Response::send(['success' => false, 'message' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('items.php router error', ['action' => $action, 'error' => $e->getMessage()]);
    Response::send(['success' => false, 'message' => 'Internal server error'], Response::HTTP_INTERNAL_ERROR);
}

function readInput() {
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
